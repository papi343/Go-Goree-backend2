<?php

use App\Enums\ModePayementEnum;
use App\Enums\StatutBilletEnum;
use App\Enums\TypeTransactionPayDunyaEnum;
use App\Models\Abonnement;
use App\Models\Billet;
use App\Models\Payement;
use App\Models\Portefeuille;
use App\Models\Resident;
use App\Models\Tarif;
use App\Models\User;
use App\Models\Voyage;
use App\Services\Paiements\PayDunya\PayDunyaClientInterface;
use App\Services\Paiements\PayDunyaPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('un client non-résident achète un billet avec son portefeuille (tarif étranger)', function () {
    $client = User::factory()->client()->create();
    Portefeuille::factory()->solde(5000)->create(['user_id' => $client->id]);
    Tarif::factory()->etranger(2500)->create();
    $voyage = Voyage::factory()->placesRestantes(10)->create();

    Sanctum::actingAs($client);

    $this->postJson('/api/v1/billets', [
        'voyage_id' => $voyage->id,
        'payment_mode' => ModePayementEnum::PORTEFEUILLE->value,
    ])->assertCreated();

    $billet = Billet::where('user_id', $client->id)->firstOrFail();
    expect($billet->statut)->toBe(StatutBilletEnum::PAYE);
    expect((float) $billet->montant)->toBe(2500.0);
    expect((float) Portefeuille::where('user_id', $client->id)->first()->solde)->toBe(2500.0);
    expect($voyage->fresh()->places_restantes)->toBe(9);
});

test('un résident avec abonnement actif génère un billet GRATUIT', function () {
    $client = User::factory()->client()->resident()->create();
    $resident = Resident::factory()->create(['user_id' => $client->id, 'active' => true]);
    Abonnement::factory()->actif()->create(['resident_id' => $resident->id]);
    Tarif::factory()->resident(500)->create();
    Tarif::factory()->adulte(1500)->create();
    Portefeuille::factory()->solde(5000)->create(['user_id' => $client->id]);
    $voyage = Voyage::factory()->placesRestantes(10)->create();

    Sanctum::actingAs($client);

    $this->postJson('/api/v1/billets', [
        'voyage_id' => $voyage->id,
        'payment_mode' => ModePayementEnum::PORTEFEUILLE->value,
    ])->assertCreated();

    $billet = Billet::where('user_id', $client->id)->firstOrFail();
    expect($billet->statut)->toBe(StatutBilletEnum::PAYE);
    expect((float) $billet->montant)->toBe(0.0);
    // Aucun débit : l'abonnement couvre le trajet.
    expect((float) Portefeuille::where('user_id', $client->id)->first()->solde)->toBe(5000.0);
    expect($voyage->fresh()->places_restantes)->toBe(9);
});

test('un résident SANS abonnement paie le tarif réduit résident', function () {
    $client = User::factory()->client()->resident()->create();
    Resident::factory()->create(['user_id' => $client->id, 'active' => true]);
    // Pas d'abonnement actif.
    Tarif::factory()->resident(500)->create();
    Tarif::factory()->adulte(1500)->create();
    Portefeuille::factory()->solde(5000)->create(['user_id' => $client->id]);
    $voyage = Voyage::factory()->placesRestantes(10)->create();

    Sanctum::actingAs($client);

    $this->postJson('/api/v1/billets', [
        'voyage_id' => $voyage->id,
        'payment_mode' => ModePayementEnum::PORTEFEUILLE->value,
    ])->assertCreated();

    $billet = Billet::where('user_id', $client->id)->firstOrFail();
    expect((float) $billet->montant)->toBe(500.0); // tarif réduit RESIDENT
    expect((float) Portefeuille::where('user_id', $client->id)->first()->solde)->toBe(4500.0);
});

test('impossible de générer deux billets pour le même voyage (fraude signalée)', function () {
    $client = User::factory()->client()->create();
    Portefeuille::factory()->solde(10000)->create(['user_id' => $client->id]);
    Tarif::factory()->etranger(2500)->create();
    $voyage = Voyage::factory()->placesRestantes(10)->create();

    Sanctum::actingAs($client);

    $payload = ['voyage_id' => $voyage->id, 'payment_mode' => ModePayementEnum::PORTEFEUILLE->value];

    $this->postJson('/api/v1/billets', $payload)->assertCreated();
    $this->postJson('/api/v1/billets', $payload)
        ->assertStatus(400)
        ->assertJsonPath('message', 'Vous avez déjà un billet pour ce voyage.');

    // Un seul billet, et une alerte de fraude a été enregistrée.
    expect(Billet::where('user_id', $client->id)->where('voyage_id', $voyage->id)->count())->toBe(1);
    $this->assertDatabaseHas('alerte_fraudes', ['regle_declenchee' => 'double_billet_voyage']);
});

test('l\'achat échoue si le solde du portefeuille est insuffisant', function () {
    $client = User::factory()->client()->create();
    Portefeuille::factory()->solde(100)->create(['user_id' => $client->id]);
    Tarif::factory()->etranger(2500)->create();
    $voyage = Voyage::factory()->placesRestantes(10)->create();

    Sanctum::actingAs($client);

    $this->postJson('/api/v1/billets', [
        'voyage_id' => $voyage->id,
        'payment_mode' => ModePayementEnum::PORTEFEUILLE->value,
    ])->assertStatus(400)
        ->assertJsonPath('message', 'Solde insuffisant.');

    // Rien ne doit être débité, le billet ne doit pas rester PAYE.
    expect((float) Portefeuille::where('user_id', $client->id)->first()->solde)->toBe(100.0);
    expect(Billet::where('statut', StatutBilletEnum::PAYE->value)->exists())->toBeFalse();
});

test('l\'achat échoue quand le voyage est complet', function () {
    $client = User::factory()->client()->create();
    Portefeuille::factory()->solde(5000)->create(['user_id' => $client->id]);
    Tarif::factory()->etranger(2500)->create();
    $voyage = Voyage::factory()->complet()->create();

    Sanctum::actingAs($client);

    $this->postJson('/api/v1/billets', [
        'voyage_id' => $voyage->id,
        'payment_mode' => ModePayementEnum::PORTEFEUILLE->value,
    ])->assertStatus(400)
        ->assertJsonPath('message', 'Pas de places disponibles pour ce voyage.');
});

test('l\'achat par PayDunya renvoie une URL de redirection sans débiter le portefeuille', function () {
    config()->set('paydunya.driver', 'fake');
    app()->forgetInstance(PayDunyaClientInterface::class);
    app()->forgetInstance(PayDunyaPaymentService::class);

    $client = User::factory()->client()->create();
    Tarif::factory()->etranger(2500)->create();
    $voyage = Voyage::factory()->placesRestantes(10)->create();

    Sanctum::actingAs($client);

    $response = $this->postJson('/api/v1/billets', [
        'voyage_id' => $voyage->id,
        'payment_mode' => ModePayementEnum::PAYDUNYA->value,
    ])->assertCreated();

    expect($response->json('redirect_url'))->toBeString()->not->toBeEmpty();

    $billet = Billet::where('user_id', $client->id)->firstOrFail();
    expect($billet->statut)->toBe(StatutBilletEnum::EN_ATTENTE_PAIEMENT);

    $payement = Payement::where('user_id', $client->id)->firstOrFail();
    expect($payement->type_transaction)->toBe(TypeTransactionPayDunyaEnum::ACHAT_BILLET);
    expect($payement->paydunya_token)->toStartWith('fake_');
});

test('l\'achat valide les données envoyées', function () {
    Sanctum::actingAs(User::factory()->client()->create());

    $this->postJson('/api/v1/billets', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['voyage_id', 'payment_mode']);
});

test('l\'achat refuse un voyage inexistant', function () {
    Sanctum::actingAs(User::factory()->client()->create());

    $this->postJson('/api/v1/billets', [
        'voyage_id' => Str::uuid()->toString(),
        'payment_mode' => ModePayementEnum::PORTEFEUILLE->value,
    ])->assertStatus(422)->assertJsonValidationErrors(['voyage_id']);
});

test('un client ne voit que ses propres billets', function () {
    $client = User::factory()->client()->create();
    Billet::factory()->count(2)->create(['user_id' => $client->id]);
    Billet::factory()->create(); // billet d'un autre

    Sanctum::actingAs($client);

    $response = $this->getJson('/api/v1/billets')->assertOk();
    expect($response->json('meta.total'))->toBe(2);
});

test('un admin voit tous les billets', function () {
    Billet::factory()->count(3)->create();
    Sanctum::actingAs(User::factory()->admin()->create());

    $response = $this->getJson('/api/v1/billets')->assertOk();
    expect($response->json('meta.total'))->toBe(3);
});

test('un client ne peut pas consulter le billet d\'un autre', function () {
    $autre = Billet::factory()->create();
    Sanctum::actingAs(User::factory()->client()->create());

    $this->getJson("/api/v1/billets/{$autre->id}")->assertForbidden();
});
