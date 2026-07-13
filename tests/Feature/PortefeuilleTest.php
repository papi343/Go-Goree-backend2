<?php

use App\Enums\ModePayementEnum;
use App\Models\Portefeuille;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('un utilisateur consulte le solde de son portefeuille', function () {
    $user = User::factory()->client()->create();
    Portefeuille::factory()->solde(7500)->create(['user_id' => $user->id]);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/portefeuille')
        ->assertOk()
        ->assertJsonPath('data.solde', '7500.00');
});

test('consulter un portefeuille inexistant renvoie 404', function () {
    Sanctum::actingAs(User::factory()->client()->create());

    $this->getJson('/api/v1/portefeuille')->assertNotFound();
});

test('la recharge refuse un montant trop faible', function () {
    Sanctum::actingAs(User::factory()->client()->create());

    $this->postJson('/api/v1/portefeuille/recharge', ['montant' => 50])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['montant']);
});

test('la recharge refuse un montant non numérique', function () {
    Sanctum::actingAs(User::factory()->client()->create());

    $this->postJson('/api/v1/portefeuille/recharge', ['montant' => 'beaucoup'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['montant']);
});

test('la recharge refuse le mode PORTEFEUILLE (on ne recharge pas depuis soi-même)', function () {
    Sanctum::actingAs(User::factory()->client()->create());

    $this->postJson('/api/v1/portefeuille/recharge', [
        'montant' => 5000,
        'payment_mode' => ModePayementEnum::PORTEFEUILLE->value,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['payment_mode']);
});

test('la recharge refuse un mode de paiement inconnu', function () {
    Sanctum::actingAs(User::factory()->client()->create());

    $this->postJson('/api/v1/portefeuille/recharge', [
        'montant' => 5000,
        'payment_mode' => 'BITCOIN',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['payment_mode']);
});

test('le portefeuille est protégé par authentification', function () {
    $this->getJson('/api/v1/portefeuille')->assertUnauthorized();
    $this->postJson('/api/v1/portefeuille/recharge', ['montant' => 5000])->assertUnauthorized();
});
