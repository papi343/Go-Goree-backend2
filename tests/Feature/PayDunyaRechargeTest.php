<?php

use App\Enums\ModePayementEnum;
use App\Enums\StatutPayementEnum;
use App\Models\Payement;
use App\Models\Portefeuille;
use App\Models\Role;
use App\Models\User;
use App\Services\Paiements\PayDunya\PayDunyaClientInterface;
use App\Services\Paiements\PayDunya\PayDunyaWebhookVerifier;
use App\Services\Paiements\PayDunyaPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * Force le pilote "fake" et rafraîchit les singletons pour qu'ils prennent en
 * compte la config de test.
 */
function utiliserPayDunyaFake(): string
{
    config()->set('paydunya.driver', 'fake');
    config()->set('paydunya.fake.auto_complete', true);
    config()->set('paydunya.webhook.require_signature', true);
    config()->set('paydunya.fake.secret', 'secret-test-paydunya');

    app()->forgetInstance(PayDunyaClientInterface::class);
    app()->forgetInstance(PayDunyaWebhookVerifier::class);
    app()->forgetInstance(PayDunyaPaymentService::class);

    return hash('sha512', 'secret-test-paydunya');
}

function creerClient(): User
{
    $role = Role::create(['nom' => 'Client']);

    return User::create([
        'prenom' => 'Samba',
        'nom' => 'Diallo',
        'email' => 'samba.recharge@example.com',
        'mot_de_passe' => Hash::make('passer123'),
        'role_id' => $role->id,
        'active' => true,
    ]);
}

test('la recharge initie un paiement PayDunya sans exposer le jeton', function () {
    utiliserPayDunyaFake();
    $client = creerClient();
    Sanctum::actingAs($client);

    $response = $this->postJson('/api/v1/portefeuille/recharge', [
        'montant' => 5000,
        'payment_mode' => ModePayementEnum::PAYDUNYA->value,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['message', 'reference', 'montant', 'statut', 'redirect_url'])
        ->assertJsonMissing(['paydunya_token']);

    expect($response->json('redirect_url'))->toBeString()->not->toBeEmpty();

    $payement = Payement::where('user_id', $client->id)->first();
    expect($payement)->not->toBeNull();
    expect($payement->statut)->toBe(StatutPayementEnum::EN_COURS);
    expect($payement->paydunya_token)->toStartWith('fake_');
});

test('le webhook signé crédite le portefeuille une seule fois (idempotence)', function () {
    $hash = utiliserPayDunyaFake();
    $client = creerClient();
    Sanctum::actingAs($client);

    $this->postJson('/api/v1/portefeuille/recharge', [
        'montant' => 5000,
        'payment_mode' => ModePayementEnum::PAYDUNYA->value,
    ])->assertStatus(201);

    $token = Payement::where('user_id', $client->id)->first()->paydunya_token;

    $payload = [
        'data' => [
            'hash' => $hash,
            'status' => 'completed',
            'invoice' => ['token' => $token],
        ],
    ];

    // 1er webhook : le paiement est accepté et le portefeuille crédité.
    $this->postJson('/webhooks/paydunya', $payload)
        ->assertStatus(200)
        ->assertJsonPath('resultat', 'accepte');

    $solde = fn () => (float) Portefeuille::where('user_id', $client->id)->first()->solde;
    expect($solde())->toBe(5000.0);
    expect(Payement::where('user_id', $client->id)->first()->statut)
        ->toBe(StatutPayementEnum::ACCEPTE);

    // Rejeu du même webhook : aucun double crédit.
    $this->postJson('/webhooks/paydunya', $payload)
        ->assertStatus(200)
        ->assertJsonPath('resultat', 'deja_traite');

    expect($solde())->toBe(5000.0);
});

test('un webhook avec une signature invalide est rejeté et ne crédite rien', function () {
    utiliserPayDunyaFake();
    $client = creerClient();
    Sanctum::actingAs($client);

    $this->postJson('/api/v1/portefeuille/recharge', [
        'montant' => 5000,
        'payment_mode' => ModePayementEnum::PAYDUNYA->value,
    ])->assertStatus(201);

    $token = Payement::where('user_id', $client->id)->first()->paydunya_token;

    $this->postJson('/webhooks/paydunya', [
        'data' => [
            'hash' => 'signature-bidon',
            'status' => 'completed',
            'invoice' => ['token' => $token],
        ],
    ])->assertStatus(401);

    expect(Portefeuille::where('user_id', $client->id)->first())->toBeNull();
    expect(Payement::where('user_id', $client->id)->first()->statut)
        ->toBe(StatutPayementEnum::EN_COURS);
});

test('le webhook rejette une recharge dont le mode est PORTEFEUILLE', function () {
    utiliserPayDunyaFake();
    $client = creerClient();
    Sanctum::actingAs($client);

    $this->postJson('/api/v1/portefeuille/recharge', [
        'montant' => 5000,
        'payment_mode' => ModePayementEnum::PORTEFEUILLE->value,
    ])->assertStatus(422);
});
