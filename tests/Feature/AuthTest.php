<?php

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('un utilisateur actif peut se connecter et reçoit un jeton', function () {
    $user = User::factory()->client()->create(['email' => 'client@goree.sn']);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'client@goree.sn',
        'mot_de_passe' => UserFactory::MOT_DE_PASSE,
    ]);

    $response->assertOk()
        ->assertJsonStructure(['access_token', 'token_type', 'user' => ['id', 'email']]);

    expect($response->json('token_type'))->toBe('Bearer');
    expect($response->json('access_token'))->toBeString()->not->toBeEmpty();
});

test('la connexion échoue avec un mauvais mot de passe', function () {
    User::factory()->client()->create(['email' => 'client@goree.sn']);

    $this->postJson('/api/v1/login', [
        'email' => 'client@goree.sn',
        'mot_de_passe' => 'mauvais-mot-de-passe',
    ])->assertUnauthorized()
        ->assertJsonPath('message', 'Identifiants incorrects.');
});

test('la connexion échoue pour un email inexistant', function () {
    $this->postJson('/api/v1/login', [
        'email' => 'inconnu@goree.sn',
        'mot_de_passe' => 'password',
    ])->assertUnauthorized();
});

test('un compte désactivé ne peut pas se connecter', function () {
    User::factory()->client()->inactive()->create(['email' => 'inactif@goree.sn']);

    $this->postJson('/api/v1/login', [
        'email' => 'inactif@goree.sn',
        'mot_de_passe' => UserFactory::MOT_DE_PASSE,
    ])->assertForbidden()
        ->assertJsonPath('message', 'Votre compte est désactivé.');
});

test('la connexion valide les champs requis', function () {
    $this->postJson('/api/v1/login', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'mot_de_passe']);
});

test('la connexion refuse un email mal formé', function () {
    $this->postJson('/api/v1/login', [
        'email' => 'pas-un-email',
        'mot_de_passe' => 'password',
    ])->assertStatus(422)->assertJsonValidationErrors(['email']);
});

test('un utilisateur connecté peut consulter son profil', function () {
    $user = User::factory()->client()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email);
});

test('le profil n\'expose jamais le mot de passe', function () {
    $user = User::factory()->client()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/me');
    expect($response->json())->not->toHaveKey('mot_de_passe');
    expect(data_get($response->json(), 'data.mot_de_passe'))->toBeNull();
});

test('un utilisateur connecté peut se déconnecter et son jeton est révoqué', function () {
    $user = User::factory()->client()->create();
    $token = $user->createToken('auth_token')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/logout')
        ->assertOk();

    expect($user->fresh()->tokens()->count())->toBe(0);
});

test('les routes protégées rejettent les requêtes non authentifiées', function () {
    $this->getJson('/api/v1/me')->assertUnauthorized();
    $this->getJson('/api/v1/portefeuille')->assertUnauthorized();
    $this->getJson('/api/v1/billets')->assertUnauthorized();
    $this->getJson('/api/v1/users')->assertUnauthorized();
});
