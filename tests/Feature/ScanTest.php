<?php

use App\Enums\ResultatScanEnum;
use App\Enums\StatutBilletEnum;
use App\Models\Billet;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('un billet payé est scanné avec succès et passe à UTILISE', function () {
    $billet = Billet::factory()->paye()->create();
    Sanctum::actingAs(User::factory()->agent()->create());

    $this->postJson('/api/v1/scans', ['qr_token' => $billet->qr_token])
        ->assertOk()
        ->assertJsonPath('resultat', ResultatScanEnum::VALIDE->value);

    expect($billet->fresh()->statut)->toBe(StatutBilletEnum::UTILISE);
    $this->assertDatabaseHas('scans', [
        'billet_id' => $billet->id,
        'resultat' => ResultatScanEnum::VALIDE->value,
    ]);
});

test('un billet déjà utilisé est refusé (DEJA_SCANNE)', function () {
    $billet = Billet::factory()->utilise()->create();
    Sanctum::actingAs(User::factory()->agent()->create());

    $this->postJson('/api/v1/scans', ['qr_token' => $billet->qr_token])
        ->assertStatus(422)
        ->assertJsonPath('resultat', ResultatScanEnum::DEJA_SCANNE->value);

    // Le statut ne change pas et un scan est tout de même enregistré.
    expect($billet->fresh()->statut)->toBe(StatutBilletEnum::UTILISE);
    expect(Scan::where('billet_id', $billet->id)->count())->toBe(1);
});

test('un billet expiré est refusé (EXPIRE)', function () {
    $billet = Billet::factory()->expire()->create();
    Sanctum::actingAs(User::factory()->agent()->create());

    $this->postJson('/api/v1/scans', ['qr_token' => $billet->qr_token])
        ->assertStatus(422)
        ->assertJsonPath('resultat', ResultatScanEnum::EXPIRE->value);
});

test('un billet non payé est refusé (NON_EMBARQUE)', function () {
    $billet = Billet::factory()->enAttente()->create();
    Sanctum::actingAs(User::factory()->agent()->create());

    $this->postJson('/api/v1/scans', ['qr_token' => $billet->qr_token])
        ->assertStatus(422)
        ->assertJsonPath('resultat', ResultatScanEnum::NON_EMBARQUE->value);
});

test('un QR code inconnu renvoie 404', function () {
    Sanctum::actingAs(User::factory()->agent()->create());

    $this->postJson('/api/v1/scans', ['qr_token' => 'QR_inexistant'])
        ->assertNotFound()
        ->assertJsonPath('resultat', ResultatScanEnum::NON_EMBARQUE->value);
});

test('le scan exige un qr_token', function () {
    Sanctum::actingAs(User::factory()->agent()->create());

    $this->postJson('/api/v1/scans', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['qr_token']);
});

test('le scan est protégé par authentification', function () {
    $billet = Billet::factory()->paye()->create();

    $this->postJson('/api/v1/scans', ['qr_token' => $billet->qr_token])
        ->assertUnauthorized();
});
