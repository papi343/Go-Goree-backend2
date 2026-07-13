<?php

use App\Enums\ResultatScanEnum;
use App\Enums\StatutBilletEnum;
use App\Models\Billet;
use App\Models\Embarquement;
use App\Models\Scan;
use App\Models\User;
use App\Models\Voyage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * Prépare un voyage, une session d'embarquement ouverte et un billet du voyage.
 *
 * @return array{0: Embarquement, 1: Billet}
 */
function scenarioScan(StatutBilletEnum $statut): array
{
    $voyage = Voyage::factory()->create();
    $embarquement = Embarquement::factory()->create(['voyage_id' => $voyage->id]);
    $billet = Billet::factory()->statut($statut)->create(['voyage_id' => $voyage->id]);

    return [$embarquement, $billet];
}

test('un billet payé du bon voyage est scanné avec succès (VALIDE → UTILISE)', function () {
    [$embarquement, $billet] = scenarioScan(StatutBilletEnum::PAYE);
    Sanctum::actingAs(User::factory()->agent()->create());

    $this->postJson('/api/v1/scans', ['qr_token' => $billet->qr_token, 'embarquement_id' => $embarquement->id])
        ->assertOk()
        ->assertJsonPath('resultat', ResultatScanEnum::VALIDE->value);

    expect($billet->fresh()->statut)->toBe(StatutBilletEnum::UTILISE);
    $this->assertDatabaseHas('scans', ['billet_id' => $billet->id, 'embarquement_id' => $embarquement->id]);
});

test('un billet d\'un autre voyage est refusé (MAUVAIS_VOYAGE)', function () {
    $embarquement = Embarquement::factory()->create(); // voyage A
    $billet = Billet::factory()->paye()->create(); // voyage B (autre)
    Sanctum::actingAs(User::factory()->agent()->create());

    $this->postJson('/api/v1/scans', ['qr_token' => $billet->qr_token, 'embarquement_id' => $embarquement->id])
        ->assertStatus(422)
        ->assertJsonPath('resultat', ResultatScanEnum::MAUVAIS_VOYAGE->value);

    // Non consommé.
    expect($billet->fresh()->statut)->toBe(StatutBilletEnum::PAYE);
});

test('un billet déjà utilisé est refusé (DEJA_SCANNE) et signale une fraude', function () {
    [$embarquement, $billet] = scenarioScan(StatutBilletEnum::UTILISE);
    Sanctum::actingAs(User::factory()->agent()->create());

    $this->postJson('/api/v1/scans', ['qr_token' => $billet->qr_token, 'embarquement_id' => $embarquement->id])
        ->assertStatus(422)
        ->assertJsonPath('resultat', ResultatScanEnum::DEJA_SCANNE->value);

    $this->assertDatabaseHas('alerte_fraudes', ['regle_declenchee' => 'double_scan_billet']);
});

test('un billet expiré est refusé (EXPIRE)', function () {
    [$embarquement, $billet] = scenarioScan(StatutBilletEnum::EXPIRE);
    Sanctum::actingAs(User::factory()->agent()->create());

    $this->postJson('/api/v1/scans', ['qr_token' => $billet->qr_token, 'embarquement_id' => $embarquement->id])
        ->assertStatus(422)
        ->assertJsonPath('resultat', ResultatScanEnum::EXPIRE->value);
});

test('un billet non payé est refusé (NON_EMBARQUE)', function () {
    [$embarquement, $billet] = scenarioScan(StatutBilletEnum::EN_ATTENTE_PAIEMENT);
    Sanctum::actingAs(User::factory()->agent()->create());

    $this->postJson('/api/v1/scans', ['qr_token' => $billet->qr_token, 'embarquement_id' => $embarquement->id])
        ->assertStatus(422)
        ->assertJsonPath('resultat', ResultatScanEnum::NON_EMBARQUE->value);
});

test('un QR inconnu renvoie 404', function () {
    $embarquement = Embarquement::factory()->create();
    Sanctum::actingAs(User::factory()->agent()->create());

    $this->postJson('/api/v1/scans', ['qr_token' => 'QR_inconnu', 'embarquement_id' => $embarquement->id])
        ->assertNotFound()
        ->assertJsonPath('resultat', ResultatScanEnum::NON_EMBARQUE->value);
});

test('deux scans successifs du même billet : le 2e est DEJA_SCANNE (multi-contrôleurs)', function () {
    [$embarquement, $billet] = scenarioScan(StatutBilletEnum::PAYE);
    Sanctum::actingAs(User::factory()->agent()->create());

    $payload = ['qr_token' => $billet->qr_token, 'embarquement_id' => $embarquement->id];

    $this->postJson('/api/v1/scans', $payload)->assertJsonPath('resultat', ResultatScanEnum::VALIDE->value);
    $this->postJson('/api/v1/scans', $payload)->assertJsonPath('resultat', ResultatScanEnum::DEJA_SCANNE->value);

    expect(Scan::where('billet_id', $billet->id)->count())->toBe(2);
});

test('le scan valide les champs requis', function () {
    Sanctum::actingAs(User::factory()->agent()->create());

    $this->postJson('/api/v1/scans', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['qr_token', 'embarquement_id']);
});

test('un client ne peut pas scanner', function () {
    [$embarquement, $billet] = scenarioScan(StatutBilletEnum::PAYE);
    Sanctum::actingAs(User::factory()->client()->create());

    $this->postJson('/api/v1/scans', ['qr_token' => $billet->qr_token, 'embarquement_id' => $embarquement->id])
        ->assertForbidden();
});

test('le scan exige une authentification', function () {
    [$embarquement, $billet] = scenarioScan(StatutBilletEnum::PAYE);

    $this->postJson('/api/v1/scans', ['qr_token' => $billet->qr_token, 'embarquement_id' => $embarquement->id])
        ->assertUnauthorized();
});
