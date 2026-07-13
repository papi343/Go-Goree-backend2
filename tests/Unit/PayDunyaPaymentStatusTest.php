<?php

use App\Services\Paiements\PayDunya\Enums\PayDunyaPaymentStatus;

test('les statuts « payés » sont normalisés en COMPLETED', function (string $brut) {
    expect(PayDunyaPaymentStatus::depuisApi($brut))->toBe(PayDunyaPaymentStatus::COMPLETED);
})->with(['completed', 'success', 'SUCCEEDED', 'Paid']);

test('les statuts « échoués » sont normalisés en CANCELLED', function (string $brut) {
    expect(PayDunyaPaymentStatus::depuisApi($brut))->toBe(PayDunyaPaymentStatus::CANCELLED);
})->with(['cancelled', 'canceled', 'failed', 'refused']);

test('les statuts « en attente » sont normalisés en PENDING', function (string $brut) {
    expect(PayDunyaPaymentStatus::depuisApi($brut))->toBe(PayDunyaPaymentStatus::PENDING);
})->with(['pending', 'processing']);

test('un statut inconnu ou nul devient UNKNOWN', function () {
    expect(PayDunyaPaymentStatus::depuisApi('n_importe_quoi'))->toBe(PayDunyaPaymentStatus::UNKNOWN);
    expect(PayDunyaPaymentStatus::depuisApi(null))->toBe(PayDunyaPaymentStatus::UNKNOWN);
});

test('les helpers estPaye/estEchoue reflètent le statut', function () {
    expect(PayDunyaPaymentStatus::COMPLETED->estPaye())->toBeTrue();
    expect(PayDunyaPaymentStatus::COMPLETED->estEchoue())->toBeFalse();
    expect(PayDunyaPaymentStatus::CANCELLED->estEchoue())->toBeTrue();
    expect(PayDunyaPaymentStatus::PENDING->estPaye())->toBeFalse();
});
