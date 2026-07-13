<?php

use App\Enums\StatutBilletEnum;
use App\Jobs\ExpireTicketsJob;
use App\Models\Billet;
use App\Models\Voyage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('un billet payé d\'un voyage passé (>1h) est expiré', function () {
    $voyage = Voyage::factory()->create(['date_voyage' => now()->subDay()->toDateString()]);
    $billet = Billet::factory()->paye()->create(['voyage_id' => $voyage->id]);

    (new ExpireTicketsJob)->handle();

    expect($billet->fresh()->statut)->toBe(StatutBilletEnum::EXPIRE);
});

test('un billet d\'un voyage futur n\'est pas expiré', function () {
    $voyage = Voyage::factory()->create(['date_voyage' => now()->addDay()->toDateString()]);
    $billet = Billet::factory()->paye()->create(['voyage_id' => $voyage->id]);

    (new ExpireTicketsJob)->handle();

    expect($billet->fresh()->statut)->toBe(StatutBilletEnum::PAYE);
});

test('un billet déjà utilisé n\'est pas ré-expiré', function () {
    $voyage = Voyage::factory()->create(['date_voyage' => now()->subDay()->toDateString()]);
    $billet = Billet::factory()->utilise()->create(['voyage_id' => $voyage->id]);

    (new ExpireTicketsJob)->handle();

    expect($billet->fresh()->statut)->toBe(StatutBilletEnum::UTILISE);
});
