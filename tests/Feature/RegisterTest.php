<?php

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('un visiteur peut s\'inscrire et reçoit un jeton (auto-connexion)', function () {
    $response = $this->postJson('/api/v1/register', [
        'prenom' => 'Awa',
        'nom' => 'Ndiaye',
        'email' => 'awa@goree.sn',
        'mot_de_passe' => 'MotDePasse1',
        'mot_de_passe_confirmation' => 'MotDePasse1',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['access_token', 'token_type', 'user' => ['id', 'email']]);

    $user = User::where('email', 'awa@goree.sn')->firstOrFail();
    expect($user->role->nom)->toBe(RoleEnum::CLIENT);
    expect($user->mot_de_passe)->not->toBe('MotDePasse1'); // haché
});

test('un compte inscrit peut ensuite se connecter', function () {
    $this->postJson('/api/v1/register', [
        'prenom' => 'Awa',
        'nom' => 'Ndiaye',
        'email' => 'awa@goree.sn',
        'mot_de_passe' => 'MotDePasse1',
        'mot_de_passe_confirmation' => 'MotDePasse1',
    ])->assertCreated();

    $this->postJson('/api/v1/login', [
        'email' => 'awa@goree.sn',
        'mot_de_passe' => 'MotDePasse1',
    ])->assertOk()->assertJsonStructure(['access_token']);
});

test('l\'inscription valide les champs et l\'unicité de l\'email', function () {
    User::factory()->create(['email' => 'existe@goree.sn']);

    $this->postJson('/api/v1/register', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['prenom', 'nom', 'email', 'mot_de_passe']);

    $this->postJson('/api/v1/register', [
        'prenom' => 'A', 'nom' => 'B', 'email' => 'existe@goree.sn',
        'mot_de_passe' => 'MotDePasse1', 'mot_de_passe_confirmation' => 'MotDePasse1',
    ])->assertStatus(422)->assertJsonValidationErrors(['email']);
});

test('l\'inscription exige la confirmation du mot de passe', function () {
    $this->postJson('/api/v1/register', [
        'prenom' => 'A', 'nom' => 'B', 'email' => 'a@b.sn',
        'mot_de_passe' => 'MotDePasse1', 'mot_de_passe_confirmation' => 'different',
    ])->assertStatus(422)->assertJsonValidationErrors(['mot_de_passe']);
});

test('l\'inscription n\'accorde jamais le statut résident (validation admin requise)', function () {
    // Même si le client tente de forcer est_resident, il est ignoré.
    $this->postJson('/api/v1/register', [
        'prenom' => 'A', 'nom' => 'B', 'email' => 'res@goree.sn',
        'mot_de_passe' => 'MotDePasse1', 'mot_de_passe_confirmation' => 'MotDePasse1',
        'est_resident' => true,
    ])->assertCreated();

    expect(User::where('email', 'res@goree.sn')->first()->est_resident)->toBeFalse();
});
