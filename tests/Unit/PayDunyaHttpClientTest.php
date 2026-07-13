<?php

use App\Services\Paiements\PayDunya\Data\PaymentIntent;
use App\Services\Paiements\PayDunya\Enums\PayDunyaPaymentStatus;
use App\Services\Paiements\PayDunya\Exceptions\PayDunyaException;
use App\Services\Paiements\PayDunya\PayDunyaHttpClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// Ces tests utilisent le conteneur Laravel (façade Http), d'où le TestCase applicatif.
uses(TestCase::class);

function configHttp(array $overrides = []): array
{
    return array_replace_recursive([
        'environment' => 'test',
        'urls' => [
            'live' => 'https://app.paydunya.com/api/v1',
            'sandbox' => 'https://app.paydunya.com/sandbox-api/v1',
        ],
        'keys' => [
            'master' => 'master-key',
            'private' => 'private-key',
            'token' => 'token-key',
        ],
        'store' => ['name' => 'Go Gorée'],
        'actions' => [
            'callback_url' => 'https://app.test/webhooks/paydunya',
            'return_url' => 'https://app.test/retour',
            'cancel_url' => 'https://app.test/annule',
        ],
        'http' => ['timeout' => 10, 'retries' => 0, 'retry_delay_ms' => 0],
    ], $overrides);
}

function intent(): PaymentIntent
{
    return new PaymentIntent('PAY_1', 5000, 'Achat billet', ['payement_id' => 'p1']);
}

test('createInvoice réussit lorsque PayDunya renvoie response_code 00', function () {
    Http::fake([
        '*checkout-invoice/create' => Http::response([
            'response_code' => '00',
            'response_text' => 'ok',
            'token' => 'tok_reel_123',
            'checkout_url' => 'https://paydunya.com/checkout/tok_reel_123',
        ]),
    ]);

    $result = (new PayDunyaHttpClient(configHttp()))->createInvoice(intent());

    expect($result->success)->toBeTrue();
    expect($result->token)->toBe('tok_reel_123');
    expect($result->checkoutUrl)->toBe('https://paydunya.com/checkout/tok_reel_123');
});

test('createInvoice cible l\'API sandbox en environnement test avec les bons en-têtes', function () {
    Http::fake([
        '*' => Http::response(['response_code' => '00', 'token' => 't', 'checkout_url' => 'u']),
    ]);

    (new PayDunyaHttpClient(configHttp()))->createInvoice(intent());

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'sandbox-api/v1/checkout-invoice/create')
            && $request->hasHeader('PAYDUNYA-MASTER-KEY', 'master-key')
            && $request->hasHeader('PAYDUNYA-PRIVATE-KEY', 'private-key')
            && $request->hasHeader('PAYDUNYA-TOKEN', 'token-key');
    });
});

test('createInvoice cible l\'API de production en environnement live', function () {
    Http::fake([
        '*' => Http::response(['response_code' => '00', 'token' => 't', 'checkout_url' => 'u']),
    ]);

    (new PayDunyaHttpClient(configHttp(['environment' => 'live'])))->createInvoice(intent());

    Http::assertSent(fn ($request) => str_contains($request->url(), 'app.paydunya.com/api/v1/'));
});

test('createInvoice renvoie un échec si response_code n\'est pas 00', function () {
    Http::fake([
        '*checkout-invoice/create' => Http::response([
            'response_code' => '1001',
            'response_text' => 'Montant invalide',
        ]),
    ]);

    $result = (new PayDunyaHttpClient(configHttp()))->createInvoice(intent());

    expect($result->success)->toBeFalse();
    expect($result->responseCode)->toBe('1001');
    expect($result->message)->toBe('Montant invalide');
});

test('confirmInvoice normalise le statut renvoyé', function () {
    Http::fake([
        '*checkout-invoice/confirm/*' => Http::response([
            'response_code' => '00',
            'status' => 'completed',
        ]),
    ]);

    expect((new PayDunyaHttpClient(configHttp()))->confirmInvoice('tok_1'))
        ->toBe(PayDunyaPaymentStatus::COMPLETED);
});

test('confirmInvoice renvoie CANCELLED pour un paiement annulé', function () {
    Http::fake([
        '*checkout-invoice/confirm/*' => Http::response([
            'response_code' => '00',
            'status' => 'cancelled',
        ]),
    ]);

    expect((new PayDunyaHttpClient(configHttp()))->confirmInvoice('tok_1'))
        ->toBe(PayDunyaPaymentStatus::CANCELLED);
});

test('une configuration sans clés lève une PayDunyaException', function () {
    $config = configHttp(['keys' => ['master' => '', 'private' => '', 'token' => '']]);

    expect(fn () => (new PayDunyaHttpClient($config))->createInvoice(intent()))
        ->toThrow(PayDunyaException::class);

    Http::assertNothingSent();
});

test('une erreur serveur PayDunya lève une PayDunyaException', function () {
    Http::fake(['*' => Http::response('erreur', 500)]);

    expect(fn () => (new PayDunyaHttpClient(configHttp()))->createInvoice(intent()))
        ->toThrow(PayDunyaException::class);
});
