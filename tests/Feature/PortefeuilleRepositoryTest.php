<?php

use App\Enums\MouvementPortefeuilleEnum;
use App\Models\Portefeuille;
use App\Models\User;
use App\Repositories\Contracts\PortefeuilleRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repo = app(PortefeuilleRepositoryInterface::class);
});

test('le crédit crée le portefeuille s\'il n\'existe pas et enregistre un mouvement', function () {
    $user = User::factory()->create();

    $portefeuille = $this->repo->lockForUpdateAndCredit($user->id, 5000);

    expect((float) $portefeuille->solde)->toBe(5000.0);
    $this->assertDatabaseHas('mouvement_portefeuilles', [
        'portefeuille_id' => $portefeuille->id,
        'type' => MouvementPortefeuilleEnum::RECHARGE->value,
        'montant' => 5000,
    ]);
});

test('le crédit s\'ajoute au solde existant', function () {
    $user = User::factory()->create();
    Portefeuille::factory()->solde(1000)->create(['user_id' => $user->id]);

    $portefeuille = $this->repo->lockForUpdateAndCredit($user->id, 2500);

    expect((float) $portefeuille->solde)->toBe(3500.0);
});

test('le débit soustrait du solde et enregistre un mouvement DEBIT', function () {
    $user = User::factory()->create();
    Portefeuille::factory()->solde(3000)->create(['user_id' => $user->id]);

    $portefeuille = $this->repo->lockForUpdateAndDebit($user->id, 1200);

    expect((float) $portefeuille->solde)->toBe(1800.0);
    $this->assertDatabaseHas('mouvement_portefeuilles', [
        'portefeuille_id' => $portefeuille->id,
        'type' => MouvementPortefeuilleEnum::DEBIT->value,
        'montant' => 1200,
    ]);
});

test('le débit lève une exception si le solde est insuffisant', function () {
    $user = User::factory()->create();
    Portefeuille::factory()->solde(500)->create(['user_id' => $user->id]);

    expect(fn () => $this->repo->lockForUpdateAndDebit($user->id, 1000))
        ->toThrow(Exception::class, 'Solde insuffisant.');

    // Le solde reste intact et aucun mouvement n'est enregistré.
    expect((float) Portefeuille::where('user_id', $user->id)->first()->solde)->toBe(500.0);
    $this->assertDatabaseCount('mouvement_portefeuilles', 0);
});

test('le débit lève une exception si le portefeuille n\'existe pas', function () {
    $user = User::factory()->create();

    expect(fn () => $this->repo->lockForUpdateAndDebit($user->id, 1000))
        ->toThrow(Exception::class, "Le portefeuille n'existe pas.");
});
