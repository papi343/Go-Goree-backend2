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

test('un admin peut désactiver un contrôleur ce qui révoque ses jetons d\'accès', function () {
    Sanctum::actingAs($admin = User::factory()->admin()->create());
    $agentRole = Role::factory()->agent()->create();
    $controleur = User::factory()->create([
        'role_id' => $agentRole->id,
        'active' => true,
    ]);

    // Simuler que le contrôleur a un jeton actif
    $token = $controleur->createToken('test_token')->plainTextToken;
    expect($controleur->tokens)->toHaveCount(1);

    // L'admin désactive le contrôleur
    $this->putJson("/api/v1/users/{$controleur->id}", [
        'active' => false,
    ])->assertOk();

    // Vérifier que le contrôleur est désactivé et ses jetons supprimés
    $controleur->refresh();
    expect($controleur->active)->toBeFalse();
    expect($controleur->tokens)->toHaveCount(0);

    // Tenter de se connecter avec l'utilisateur désactivé
    $this->postJson('/api/v1/login', [
        'email' => $controleur->email,
        'mot_de_passe' => 'password', // Le mot de passe par défaut des factories
    ])->assertStatus(403);
});

test('un admin peut renvoyer l\'email d\'invitation à un contrôleur non activé', function () {
    Mail::fake();
    Sanctum::actingAs(User::factory()->admin()->create());
    $agentRole = Role::factory()->agent()->create();
    $controleur = User::factory()->create([
        'role_id' => $agentRole->id,
        'password_reset_at' => null,
    ]);

    // Renvoyer l'invitation
    $this->postJson("/api/v1/controleurs/{$controleur->id}/renvoyer-invitation")
        ->assertOk()
        ->assertJsonPath('message', "L'email d'activation a été renvoyé avec succès.");

    // Vérifier qu'un nouveau jeton a été créé et un e-mail envoyé
    $this->assertDatabaseHas('password_reset_tokens', ['email' => $controleur->email]);
    Mail::assertQueued(ReinitialisationMotDePasseMail::class, fn ($mail) => $mail->invitation === true
        && $mail->hasTo($controleur->email));
});

test('on ne peut pas renvoyer l\'invitation à un contrôleur déjà activé', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $agentRole = Role::factory()->agent()->create();
    $controleur = User::factory()->create([
        'role_id' => $agentRole->id,
        'password_reset_at' => now(),
    ]);

    // Tenter de renvoyer l'invitation
    $this->postJson("/api/v1/controleurs/{$controleur->id}/renvoyer-invitation")
        ->assertStatus(422)
        ->assertJsonPath('message', 'Ce compte est déjà activé.');
});
