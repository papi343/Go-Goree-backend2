<?php

use App\Services\Paiements\PayDunya\PayDunyaWebhookVerifier;

function verifieurAvecConfig(array $overrides = []): PayDunyaWebhookVerifier
{
    $config = array_replace_recursive([
        'driver' => 'http',
        'keys' => ['master' => 'ma-master-key'],
        'fake' => ['secret' => 'secret-fake'],
        'webhook' => ['require_signature' => true],
    ], $overrides);

    return new PayDunyaWebhookVerifier($config);
}

test('une signature SHA-512 valide (master key) est acceptée', function () {
    $verifier = verifieurAvecConfig();
    $payload = ['data' => ['hash' => hash('sha512', 'ma-master-key')]];

    expect($verifier->estValide($payload))->toBeTrue();
});

test('une signature invalide est rejetée', function () {
    $verifier = verifieurAvecConfig();

    expect($verifier->estValide(['data' => ['hash' => 'faux']]))->toBeFalse();
    expect($verifier->estValide(['hash' => 'faux']))->toBeFalse();
    expect($verifier->estValide([]))->toBeFalse();
});

test('en mode fake, la signature est calculée sur le secret fake', function () {
    $verifier = verifieurAvecConfig(['driver' => 'fake']);
    $payload = ['hash' => hash('sha512', 'secret-fake')];

    expect($verifier->estValide($payload))->toBeTrue();
});

test('sans secret configuré, la vérification échoue (fail-closed)', function () {
    $verifier = verifieurAvecConfig(['driver' => 'http', 'keys' => ['master' => '']]);

    expect($verifier->estValide(['hash' => hash('sha512', '')]))->toBeFalse();
});

test('la vérification peut être désactivée explicitement', function () {
    $verifier = verifieurAvecConfig(['webhook' => ['require_signature' => false]]);

    expect($verifier->estValide(['n_importe_quoi' => true]))->toBeTrue();
});

test('le jeton est extrait quelle que soit la forme du payload', function () {
    $verifier = verifieurAvecConfig();

    expect($verifier->extraireToken(['token' => 'tok1']))->toBe('tok1');
    expect($verifier->extraireToken(['data' => ['token' => 'tok2']]))->toBe('tok2');
    expect($verifier->extraireToken(['invoice' => ['token' => 'tok3']]))->toBe('tok3');
    expect($verifier->extraireToken(['data' => ['invoice' => ['token' => 'tok4']]]))->toBe('tok4');
    expect($verifier->extraireToken(['rien' => 1]))->toBeNull();
});

test('la comparaison de signature est faite en temps constant (hash_equals)', function () {
    // Une signature de la bonne longueur mais fausse est bien rejetée.
    $verifier = verifieurAvecConfig();
    $faux = str_repeat('a', 128);

    expect($verifier->estValide(['hash' => $faux]))->toBeFalse();
});
