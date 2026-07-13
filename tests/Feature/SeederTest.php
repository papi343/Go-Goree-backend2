<?php

use App\Models\Chaloupe;
use App\Models\Plan;
use App\Models\Voyage;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('le seeder amorce les données de démo (2 chaloupes, plans, voyages de la semaine)', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Chaloupe::count())->toBe(2);
    $this->assertDatabaseHas('chaloupes', ['nom' => 'Beer']);
    $this->assertDatabaseHas('chaloupes', ['nom' => 'Coumba Castel']);

    expect(Plan::count())->toBe(3);

    // Le cron de génération a produit les voyages des 7 prochains jours.
    expect(Voyage::count())->toBeGreaterThan(0);

    $this->assertDatabaseHas('users', ['email' => 'admin@goree.sn']);
    $this->assertDatabaseHas('users', ['email' => 'client@goree.sn']);
});

test('le seeder est idempotent (relançable sans doublon)', function () {
    $this->seed(DatabaseSeeder::class);
    $chaloupes = Chaloupe::count();
    $voyages = Voyage::count();

    $this->seed(DatabaseSeeder::class);

    expect(Chaloupe::count())->toBe($chaloupes);
    expect(Voyage::count())->toBe($voyages);
});
