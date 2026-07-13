<?php

use App\Enums\DemandeResidenceEnum;
use App\Events\DemandeResidenceSoumise;
use App\Models\Abonnement;
use App\Models\DemandeResidence;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('un client peut soumettre une demande de résidence', function () {
    Event::fake([DemandeResidenceSoumise::class]);
    $client = User::factory()->client()->create();
    Sanctum::actingAs($client);

    $response = $this->postJson('/api/v1/demandes-residence', [
        'carte_identite' => 'CNI123456789',
        'residence' => 'Gorée Centre',
        'photo' => 'photo.png',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('demande_residences', [
        'user_id' => $client->id,
        'statut' => DemandeResidenceEnum::EN_COURS->value,
    ]);
    Event::assertDispatched(DemandeResidenceSoumise::class);
});

test('la soumission valide les champs requis', function () {
    Sanctum::actingAs(User::factory()->client()->create());

    $this->postJson('/api/v1/demandes-residence', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['carte_identite', 'residence', 'photo']);
});

test('un client ne voit que ses propres demandes', function () {
    $client = User::factory()->client()->create();
    DemandeResidence::factory()->create(['user_id' => $client->id]);
    DemandeResidence::factory()->create(); // celle d'un autre

    Sanctum::actingAs($client);

    $response = $this->getJson('/api/v1/demandes-residence')->assertOk();
    expect($response->json('meta.total'))->toBe(1);
});

test('un admin voit toutes les demandes', function () {
    DemandeResidence::factory()->count(3)->create();
    Sanctum::actingAs(User::factory()->admin()->create());

    $response = $this->getJson('/api/v1/demandes-residence')->assertOk();
    expect($response->json('meta.total'))->toBe(3);
});

test('un client ne peut pas voir la demande d\'un autre', function () {
    $autre = DemandeResidence::factory()->create();
    Sanctum::actingAs(User::factory()->client()->create());

    $this->getJson("/api/v1/demandes-residence/{$autre->id}")->assertForbidden();
});

test('un admin valide une demande, ce qui active le résident et crée un abonnement', function () {
    $demande = DemandeResidence::factory()->create();
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->postJson("/api/v1/demandes-residence/{$demande->id}/valider")
        ->assertOk();

    $this->assertDatabaseHas('demande_residences', [
        'id' => $demande->id,
        'statut' => DemandeResidenceEnum::ACCEPTEE->value,
    ]);

    $resident = Resident::where('user_id', $demande->user_id)->first();
    expect($resident)->not->toBeNull();
    expect((bool) $resident->active)->toBeTrue();
    expect($demande->user->fresh()->est_resident)->toBeTrue();
    expect(Abonnement::where('resident_id', $resident->id)->exists())->toBeTrue();
});

test('un client ne peut pas valider une demande', function () {
    $demande = DemandeResidence::factory()->create();
    Sanctum::actingAs(User::factory()->client()->create());

    $this->postJson("/api/v1/demandes-residence/{$demande->id}/valider")
        ->assertForbidden();

    $this->assertDatabaseHas('demande_residences', [
        'id' => $demande->id,
        'statut' => DemandeResidenceEnum::EN_COURS->value,
    ]);
});

test('un agent ne peut pas valider une demande (réservé aux admins)', function () {
    $demande = DemandeResidence::factory()->create();
    Sanctum::actingAs(User::factory()->agent()->create());

    $this->postJson("/api/v1/demandes-residence/{$demande->id}/valider")
        ->assertForbidden();
});

test('un admin refuse une demande avec un motif', function () {
    $demande = DemandeResidence::factory()->create();
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->postJson("/api/v1/demandes-residence/{$demande->id}/refuser", [
        'motif_refus' => 'Documents illisibles.',
    ])->assertOk();

    $this->assertDatabaseHas('demande_residences', [
        'id' => $demande->id,
        'statut' => DemandeResidenceEnum::REFUSEE->value,
        'motif_refus' => 'Documents illisibles.',
    ]);
});

test('le refus exige un motif', function () {
    $demande = DemandeResidence::factory()->create();
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->postJson("/api/v1/demandes-residence/{$demande->id}/refuser", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['motif_refus']);
});
