<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('la liste des utilisateurs est paginée', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    User::factory()->count(3)->create();

    $this->getJson('/api/v1/users')
        ->assertOk()
        ->assertJsonStructure(['data', 'links', 'meta']);
});

test('un utilisateur peut être créé', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $role = Role::factory()->client()->create();

    $response = $this->postJson('/api/v1/users', [
        'prenom' => 'Awa',
        'nom' => 'Ndiaye',
        'email' => 'awa.ndiaye@goree.sn',
        'mot_de_passe' => 'motdepasse123',
        'role_id' => $role->id,
        'active' => true,
    ]);

    $response->assertCreated()->assertJsonPath('data.email', 'awa.ndiaye@goree.sn');
    $this->assertDatabaseHas('users', ['email' => 'awa.ndiaye@goree.sn']);
});

test('la création d\'utilisateur valide les champs requis', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->postJson('/api/v1/users', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['prenom', 'nom', 'email', 'mot_de_passe', 'role_id']);
});

test('la création d\'utilisateur refuse un email déjà utilisé', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $role = Role::factory()->client()->create();
    User::factory()->create(['email' => 'doublon@goree.sn']);

    $this->postJson('/api/v1/users', [
        'prenom' => 'Awa',
        'nom' => 'Ndiaye',
        'email' => 'doublon@goree.sn',
        'mot_de_passe' => 'motdepasse123',
        'role_id' => $role->id,
    ])->assertStatus(422)->assertJsonValidationErrors(['email']);
});

test('la création d\'utilisateur refuse un mot de passe trop court', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $role = Role::factory()->client()->create();

    $this->postJson('/api/v1/users', [
        'prenom' => 'Awa',
        'nom' => 'Ndiaye',
        'email' => 'awa@goree.sn',
        'mot_de_passe' => 'court',
        'role_id' => $role->id,
    ])->assertStatus(422)->assertJsonValidationErrors(['mot_de_passe']);
});

test('la création d\'utilisateur refuse un role_id inexistant', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->postJson('/api/v1/users', [
        'prenom' => 'Awa',
        'nom' => 'Ndiaye',
        'email' => 'awa@goree.sn',
        'mot_de_passe' => 'motdepasse123',
        'role_id' => Str::uuid()->toString(),
    ])->assertStatus(422)->assertJsonValidationErrors(['role_id']);
});

test('la réponse de création n\'expose pas le mot de passe', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $role = Role::factory()->client()->create();

    $response = $this->postJson('/api/v1/users', [
        'prenom' => 'Awa',
        'nom' => 'Ndiaye',
        'email' => 'awa.ndiaye@goree.sn',
        'mot_de_passe' => 'motdepasse123',
        'role_id' => $role->id,
    ]);

    expect(data_get($response->json(), 'data.mot_de_passe'))->toBeNull();
});

test('un utilisateur peut être consulté par identifiant', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $user = User::factory()->create();

    $this->getJson("/api/v1/users/{$user->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

test('consulter un utilisateur inexistant renvoie 404', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->getJson('/api/v1/users/'.Str::uuid())
        ->assertNotFound();
});

test('un utilisateur peut être supprimé (soft delete)', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $user = User::factory()->create();

    $this->deleteJson("/api/v1/users/{$user->id}")->assertNoContent();

    $this->assertSoftDeleted('users', ['id' => $user->id]);
});
