<?php

declare(strict_types=1);

namespace App\Services\Paiements\PayDunya;

use App\Services\Paiements\PayDunya\Data\InvoiceResult;
use App\Services\Paiements\PayDunya\Data\PaymentIntent;
use App\Services\Paiements\PayDunya\Enums\PayDunyaPaymentStatus;
use Illuminate\Support\Str;

/**
 * Implémentation simulée de PayDunya : aucun appel réseau.
 *
 * Utilisée en local et dans les tests (« compte fictif »). Elle génère des
 * jetons déterministes et considère par défaut tout paiement comme réussi
 * (config paydunya.fake.auto_complete), permettant de dérouler le flux complet
 * — y compris le webhook signé — sans dépendre de PayDunya.
 */
class FakePayDunyaClient implements PayDunyaClientInterface
{
    /**
     * @param  array<string, mixed>  $config  Sous-arbre config('paydunya').
     */
    
    public function __construct(protected array $config) {}

    public function createInvoice(PaymentIntent $intent): InvoiceResult
    {
        $token = 'fake_'.Str::random(28);
        $base = rtrim((string) ($this->config['fake']['checkout_base'] ?? ''), '/');
        $checkoutUrl = $base.'/'.$token;

        return InvoiceResult::succes($token, $checkoutUrl, [
            'response_code' => '00',
            'response_text' => 'Facture PayDunya simulée créée.',
            'token' => $token,
            'checkout_url' => $checkoutUrl,
            'reference' => $intent->reference,
            'total_amount' => $intent->montant,
        ]);
    }

    public function confirmInvoice(string $token): PayDunyaPaymentStatus
    {
        return ($this->config['fake']['auto_complete'] ?? true)
            ? PayDunyaPaymentStatus::COMPLETED
            : PayDunyaPaymentStatus::PENDING;
    }
}
