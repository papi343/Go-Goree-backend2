<?php

use App\Enums\ModePayementEnum;
use App\Enums\StatutPayementEnum;
use App\Events\PaiementAccepte;
use App\Models\Abonnement;
use App\Models\Payement;
use App\Models\Plan;
use App\Models\Portefeuille;
use App\Models\Resident;
use App\Models\User;
use App\Services\Paiements\PayDunya\PayDunyaClientInterface;
use App\Services\Paiements\PayDunyaPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function residentConnecte(): User
{
    $user = User::factory()->client()->resident()->create();
    Resident::factory()->create(['user_id' => $user->id, 'active' => true]);
    Sanctum::actingAs($user);

    return $user;
}

test('la liste des plans actifs est accessible', function () {
    Plan::factory()->duree(1, 5000)->create();
    Plan::factory()->duree(12, 50000)->create();
    Plan::factory()->create(['actif' => false]);

    Sanctum::actingAs(User::factory()->client()->create());

    $response = $this->getJson('/api/v1/plans')->assertOk();
    expect($response->json())->toHaveCount(2);
});

test('un résident souscrit un abonnement via son portefeuille (activation immédiate)', function () {
    $user = residentConnecte();
    Portefeuille::factory()->solde(10000)->create(['user_id' => $user->id]);
    $plan = Plan::factory()->duree(1, 5000)->create();

    $this->postJson('/api/v1/abonnements/souscrire', [
        'plan_id' => $plan->id,
        'payment_mode' => ModePayementEnum::PORTEFEUILLE->value,
    ])->assertCreated()->assertJsonPath('message', 'Abonnement activé avec succès.');

    $resident = Resident::where('user_id', $user->id)->first();
    $abonnement = Abonnement::where('resident_id', $resident->id)->first();
    expect($abonnement)->not->toBeNull();
    expect($abonnement->plan_id)->toBe($plan->id);
    expect($abonnement->date_fin->isFuture())->toBeTrue();
    expect((float) Portefeuille::where('user_id', $user->id)->first()->solde)->toBe(5000.0);
});

test('un résident souscrit via PayDunya : lien de paiement puis activation au webhook', function () {
    config()->set('paydunya.driver', 'fake');
    app()->forgetInstance(PayDunyaClientInterface::class);
    app()->forgetInstance(PayDunyaPaymentService::class);

    $user = residentConnecte();
    $plan = Plan::factory()->duree(6, 27000)->create();

    $response = $this->postJson('/api/v1/abonnements/souscrire', [
        'plan_id' => $plan->id,
        'payment_mode' => ModePayementEnum::PAYDUNYA->value,
    ])->assertCreated();

    expect($response->json('redirect_url'))->toBeString()->not->toBeEmpty();
    expect($response->json('abonnement'))->toBeNull();

    // Pas encore d'abonnement tant que le paiement n'est pas confirmé.
    $resident = Resident::where('user_id', $user->id)->first();
    expect(Abonnement::where('resident_id', $resident->id)->exists())->toBeFalse();

    // Simulation de la confirmation PayDunya.
    $payement = Payement::where('user_id', $user->id)->firstOrFail();
    $payement->update(['statut' => StatutPayementEnum::ACCEPTE]);
    event(new PaiementAccepte($payement));

    expect(Abonnement::where('resident_id', $resident->id)->exists())->toBeTrue();
});

test('un non-résident ne peut pas souscrire', function () {
    Sanctum::actingAs(User::factory()->client()->create()); // est_resident = false
    $plan = Plan::factory()->duree(1, 5000)->create();

    $this->postJson('/api/v1/abonnements/souscrire', [
        'plan_id' => $plan->id,
        'payment_mode' => ModePayementEnum::PORTEFEUILLE->value,
    ])->assertStatus(400);
});

test('la souscription valide le plan et le mode de paiement', function () {
    residentConnecte();

    $this->postJson('/api/v1/abonnements/souscrire', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['plan_id', 'payment_mode']);
});

test('un admin peut créer un plan, un client non', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $this->postJson('/api/v1/plans', ['nom' => 'Mensuel', 'duree_mois' => 1, 'prix' => 5000])
        ->assertCreated();

    Sanctum::actingAs(User::factory()->client()->create());
    $this->postJson('/api/v1/plans', ['nom' => 'X', 'duree_mois' => 1, 'prix' => 1])
        ->assertForbidden();
});
