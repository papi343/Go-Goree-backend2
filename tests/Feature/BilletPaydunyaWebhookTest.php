<?php

use App\Enums\ModePayementEnum;
use App\Enums\StatutBilletEnum;
use App\Enums\StatutPayementEnum;
use App\Models\Billet;
use App\Models\Payement;
use App\Models\Tarif;
use App\Models\User;
use App\Models\Voyage;
use App\Services\Paiements\PayDunya\PayDunyaClientInterface;
use App\Services\Paiements\PayDunya\PayDunyaWebhookVerifier;
use App\Services\Paiements\PayDunyaPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function activerPaydunyaFakeBillet(): string
{
    config()->set('paydunya.driver', 'fake');
    config()->set('paydunya.fake.auto_complete', true);
    config()->set('paydunya.webhook.require_signature', true);
    config()->set('paydunya.fake.secret', 'secret-billet');

    app()->forgetInstance(PayDunyaClientInterface::class);
    app()->forgetInstance(PayDunyaWebhookVerifier::class);
    app()->forgetInstance(PayDunyaPaymentService::class);

    return hash('sha512', 'secret-billet');
}

test('achat PayDunya → webhook signé → paiement accepté et billet payé (end-to-end)', function () {
    $hash = activerPaydunyaFakeBillet();

    $client = User::factory()->client()->create();
    Tarif::factory()->etranger(2500)->create();
    $voyage = Voyage::factory()->placesRestantes(10)->create();
    Sanctum::actingAs($client);

    // 1) Achat : billet en attente + paiement en cours + jeton PayDunya rattaché.
    $this->postJson('/api/v1/billets', [
        'voyage_id' => $voyage->id,
        'payment_mode' => ModePayementEnum::PAYDUNYA->value,
    ])->assertCreated();

    $payement = Payement::where('user_id', $client->id)->firstOrFail();
    $billet = Billet::where('user_id', $client->id)->firstOrFail();
    expect($payement->statut)->toBe(StatutPayementEnum::EN_COURS);
    expect($billet->statut)->toBe(StatutBilletEnum::EN_ATTENTE_PAIEMENT);
    expect($payement->billet_id)->toBe($billet->id);

    // 2) Réception du webhook PayDunya (signé).
    $this->postJson('/webhooks/paydunya', [
        'data' => [
            'hash' => $hash,
            'status' => 'completed',
            'invoice' => ['token' => $payement->paydunya_token],
        ],
    ])->assertOk()->assertJsonPath('resultat', 'accepte');

    // 3) Paiement affecté en base (ACCEPTE) et billet créé/confirmé (PAYE).
    expect($payement->fresh()->statut)->toBe(StatutPayementEnum::ACCEPTE);
    expect($billet->fresh()->statut)->toBe(StatutBilletEnum::PAYE);
});
