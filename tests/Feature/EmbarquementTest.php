<?php

use App\Enums\StatutEmbarquementEnum;
use App\Models\Embarquement;
use App\Models\User;
use App\Models\Voyage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('un contrôleur ouvre l\'embarcation d\'un voyage', function () {
    $voyage = Voyage::factory()->create();
    Sanctum::actingAs(User::factory()->agent()->create());

    $this->postJson('/api/v1/embarquements/ouvrir', ['voyage_id' => $voyage->id])
        ->assertOk()
        ->assertJsonPath('statut', StatutEmbarquementEnum::OUVERT->value)
        ->assertJsonPath('voyage.id', $voyage->id);

    $this->assertDatabaseHas('embarquements', [
        'voyage_id' => $voyage->id,
        'statut' => StatutEmbarquementEnum::OUVERT->value,
    ]);
});

test('ouvrir deux fois le même voyage renvoie la même session (partagée)', function () {
    $voyage = Voyage::factory()->create();
    Sanctum::actingAs(User::factory()->agent()->create());

    $id1 = $this->postJson('/api/v1/embarquements/ouvrir', ['voyage_id' => $voyage->id])->json('id');
    $id2 = $this->postJson('/api/v1/embarquements/ouvrir', ['voyage_id' => $voyage->id])->json('id');

    expect($id1)->toBe($id2);
    expect(Embarquement::where('voyage_id', $voyage->id)->count())->toBe(1);
});

test('un contrôleur ferme une session d\'embarquement', function () {
    $embarquement = Embarquement::factory()->create();
    Sanctum::actingAs(User::factory()->agent()->create());

    $this->postJson("/api/v1/embarquements/{$embarquement->id}/fermer")
        ->assertOk()
        ->assertJsonPath('statut', StatutEmbarquementEnum::FERME->value);
});

test('un client ne peut pas ouvrir d\'embarcation', function () {
    $voyage = Voyage::factory()->create();
    Sanctum::actingAs(User::factory()->client()->create());

    $this->postJson('/api/v1/embarquements/ouvrir', ['voyage_id' => $voyage->id])->assertForbidden();
});

test('l\'ouverture valide le voyage', function () {
    Sanctum::actingAs(User::factory()->agent()->create());

    $this->postJson('/api/v1/embarquements/ouvrir', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['voyage_id']);
});
