<?php

use App\Services\Paiements\PayDunya\Data\PaymentIntent;
use App\Services\Paiements\PayDunya\Enums\PayDunyaPaymentStatus;
use App\Services\Paiements\PayDunya\FakePayDunyaClient;

function configFake(bool $autoComplete = true): array
{
    return [
        'fake' => [
            'auto_complete' => $autoComplete,
            'checkout_base' => 'https://app.test/paydunya/fake/checkout',
        ],
    ];
}

test('createInvoice renvoie un succès avec un jeton et une URL de paiement', function () {
    $client = new FakePayDunyaClient(configFake());

    $result = $client->createInvoice(new PaymentIntent(
        reference: 'RECH_123',
        montant: 5000,
        description: 'Recharge test',
    ));

    expect($result->success)->toBeTrue();
    expect($result->token)->toStartWith('fake_');
    expect($result->checkoutUrl)->toContain('/paydunya/fake/checkout/');
    expect($result->checkoutUrl)->toContain($result->token);
});

test('confirmInvoice renvoie COMPLETED quand auto_complete est actif', function () {
    $client = new FakePayDunyaClient(configFake(autoComplete: true));

    expect($client->confirmInvoice('fake_xyz'))->toBe(PayDunyaPaymentStatus::COMPLETED);
});

test('confirmInvoice renvoie PENDING quand auto_complete est désactivé', function () {
    $client = new FakePayDunyaClient(configFake(autoComplete: false));

    expect($client->confirmInvoice('fake_xyz'))->toBe(PayDunyaPaymentStatus::PENDING);
});

test('deux factures ont des jetons distincts', function () {
    $client = new FakePayDunyaClient(configFake());
    $intent = new PaymentIntent('R1', 1000, 'x');

    expect($client->createInvoice($intent)->token)
        ->not->toBe($client->createInvoice($intent)->token);
});
