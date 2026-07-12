<?php

namespace App\Services\Billetterie\SubServices;

use App\Models\Billet;
use App\Models\Payement;
use App\Enums\ModePayementEnum;
use App\Enums\StatutPayementEnum;
use App\Enums\TypeTransactionPayDunyaEnum;
use Illuminate\Support\Str;

/**
 * Service pour l'enregistrement et l'initiation de la transaction de paiement en base de données.
 */
class PaymentInitiationService
{
    /**
     * Initier le paiement pour le billet de façon simplifiée (sans service externe).
     */
    public function initiate(Billet $billet, ModePayementEnum $mode): array
    {
        $reference = 'PAY_' . Str::random(12);

        $payement = Payement::create([
            'reference' => $reference,
            'montant' => $billet->montant,
            'statut' => StatutPayementEnum::EN_COURS,
            'mode' => $mode,
            'type_transaction' => TypeTransactionPayDunyaEnum::ACHAT_BILLET,
            'billet_id' => $billet->id,
            'user_id' => $billet->user_id,
        ]);

        return [
            'success' => true,
            'payement' => $payement,
            'redirect_url' => null,
            'token' => null,
        ];
    }
}
