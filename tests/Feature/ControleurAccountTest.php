<?php

use App\Enums\RoleEnum;
use App\Mail\ReinitialisationMotDePasseMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('un admin crée un compte contrôleur : role Agent, mot de passe non défini, email d\'activation', function () {
    Mail::fake();
    Sanctum::actingAs(User::factory()->admin()->create());

    $response = $this->postJson('/api/v1/controleurs', [
        'prenom' => 'Modou',
        'nom' => 'Fall',
        'email' => 'modou.controleur@goree.sn',
        'telephone' => '770000000',
    ])->assertCreated();

    $controleur = User::where('email', 'modou.controleur@goree.sn')->firstOrFail();
    expect($controleur->role->nom)->toBe(RoleEnum::AGENT);
    expect($controleur->password_reset_at)->toBeNull();
    expect((bool) $controleur->active)->toBeTrue();

    // Un jeton de définition de mot de passe a été créé et un email envoyé.
    $this->assertDatabaseHas('password_reset_tokens', ['email' => 'modou.controleur@goree.sn']);
    Mail::assertQueued(ReinitialisationMotDePasseMail::class, fn ($mail) => $mail->invitation === true
        && $mail->hasTo('modou.controleur@goree.sn'));
});

test('un client ne peut pas créer de contrôleur', function () {
    Mail::fake();
    Sanctum::actingAs(User::factory()->client()->create());

    $this->postJson('/api/v1/controleurs', [
        'prenom' => 'Modou',
        'nom' => 'Fall',
        'email' => 'modou@goree.sn',
    ])->assertForbidden();

    Mail::assertNothingQueued();
});

test('la création de contrôleur valide les champs et l\'unicité de l\'email', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    User::factory()->create(['email' => 'existe@goree.sn']);

    $this->postJson('/api/v1/controleurs', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['prenom', 'nom', 'email']);

    $this->postJson('/api/v1/controleurs', [
        'prenom' => 'Modou',
        'nom' => 'Fall',
        'email' => 'existe@goree.sn',
    ])->assertStatus(422)->assertJsonValidationErrors(['email']);
});

test('la liste des contrôleurs ne renvoie que les agents', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $agentRole = Role::factory()->agent()->create();
    User::factory()->count(2)->create(['role_id' => $agentRole->id]);
    User::factory()->client()->create(); // pas un agent

    $response = $this->getJson('/api/v1/controleurs')->assertOk();
    expect($response->json('meta.total'))->toBe(2);
});

test('la création de contrôleur exige une authentification', function () {
    $this->postJson('/api/v1/controleurs', [
        'prenom' => 'X', 'nom' => 'Y', 'email' => 'x@y.sn',
    ])->assertUnauthorized();
});
