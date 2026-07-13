<?php

use App\Mail\ReinitialisationMotDePasseMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('un contrôleur active son compte via le lien et peut ensuite se connecter', function () {
    Mail::fake();

    // 1) L'admin crée le contrôleur.
    Sanctum::actingAs(User::factory()->admin()->create());
    $this->postJson('/api/v1/controleurs', [
        'prenom' => 'Modou',
        'nom' => 'Fall',
        'email' => 'modou@goree.sn',
    ])->assertCreated();

    // 2) On récupère le jeton transmis dans l'email d'activation.
    $token = null;
    Mail::assertQueued(ReinitialisationMotDePasseMail::class, function ($mail) use (&$token) {
        $token = $mail->token;

        return true;
    });
    expect($token)->toBeString()->not->toBeEmpty();

    // 3) Le contrôleur définit son mot de passe.
    $this->postJson('/api/v1/password/reset', [
        'email' => 'modou@goree.sn',
        'token' => $token,
        'mot_de_passe' => 'nouveaupass123',
        'mot_de_passe_confirmation' => 'nouveaupass123',
    ])->assertOk();

    $controleur = User::where('email', 'modou@goree.sn')->firstOrFail();
    expect($controleur->password_reset_at)->not->toBeNull();

    // Le jeton est consommé.
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'modou@goree.sn']);

    // 4) Connexion avec le nouveau mot de passe.
    $this->postJson('/api/v1/login', [
        'email' => 'modou@goree.sn',
        'mot_de_passe' => 'nouveaupass123',
    ])->assertOk()->assertJsonStructure(['access_token']);
});

test('la demande de réinitialisation envoie un email pour un compte existant', function () {
    Mail::fake();
    User::factory()->create(['email' => 'existe@goree.sn']);

    $this->postJson('/api/v1/password/forgot', ['email' => 'existe@goree.sn'])
        ->assertOk();

    Mail::assertQueued(ReinitialisationMotDePasseMail::class);
    $this->assertDatabaseHas('password_reset_tokens', ['email' => 'existe@goree.sn']);
});

test('la demande pour un email inconnu ne divulgue rien et n\'envoie pas d\'email', function () {
    Mail::fake();

    $this->postJson('/api/v1/password/forgot', ['email' => 'inconnu@goree.sn'])
        ->assertOk()
        ->assertJsonPath('message', 'Si un compte existe pour cet email, un lien de réinitialisation a été envoyé.');

    Mail::assertNothingQueued();
});

test('la réinitialisation échoue avec un jeton invalide', function () {
    Mail::fake();
    $user = User::factory()->create(['email' => 'user@goree.sn']);

    $this->postJson('/api/v1/password/forgot', ['email' => 'user@goree.sn'])->assertOk();

    $this->postJson('/api/v1/password/reset', [
        'email' => 'user@goree.sn',
        'token' => 'jeton-bidon',
        'mot_de_passe' => 'nouveaupass123',
        'mot_de_passe_confirmation' => 'nouveaupass123',
    ])->assertStatus(422)->assertJsonValidationErrors(['token']);
});

test('la réinitialisation échoue avec un jeton expiré', function () {
    Mail::fake();
    User::factory()->create(['email' => 'user@goree.sn']);

    $token = null;
    $this->postJson('/api/v1/password/forgot', ['email' => 'user@goree.sn'])->assertOk();
    Mail::assertQueued(ReinitialisationMotDePasseMail::class, function ($mail) use (&$token) {
        $token = $mail->token;

        return true;
    });

    // On vieillit artificiellement le jeton au-delà de la fenêtre d'expiration.
    DB::table('password_reset_tokens')
        ->where('email', 'user@goree.sn')
        ->update(['created_at' => now()->subHours(2)]);

    $this->postJson('/api/v1/password/reset', [
        'email' => 'user@goree.sn',
        'token' => $token,
        'mot_de_passe' => 'nouveaupass123',
        'mot_de_passe_confirmation' => 'nouveaupass123',
    ])->assertStatus(422)->assertJsonValidationErrors(['token']);
});

test('la réinitialisation exige un mot de passe confirmé et assez long', function () {
    User::factory()->create(['email' => 'user@goree.sn']);

    $this->postJson('/api/v1/password/reset', [
        'email' => 'user@goree.sn',
        'token' => 'x',
        'mot_de_passe' => 'court',
        'mot_de_passe_confirmation' => 'different',
    ])->assertStatus(422)->assertJsonValidationErrors(['mot_de_passe']);
});
