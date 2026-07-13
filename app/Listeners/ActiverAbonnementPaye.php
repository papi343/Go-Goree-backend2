<?php

namespace App\Listeners;

use App\Enums\TypeTransactionPayDunyaEnum;
use App\Events\PaiementAccepte;
use App\Services\Residents\AbonnementSouscriptionService;
use Illuminate\Support\Facades\Log;

/**
 * Active (ou prolonge) l'abonnement d'un résident lorsqu'un paiement de type
 * ABONNEMENT est accepté (notamment via le webhook PayDunya).
 */
class ActiverAbonnementPaye
{
    public function __construct(protected AbonnementSouscriptionService $souscription) {}

    public function handle(PaiementAccepte $event): void
    {
        $payement = $event->payement;

        if (! $payement || $payement->type_transaction !== TypeTransactionPayDunyaEnum::ABONNEMENT) {
            return;
        }

        $abonnement = $this->souscription->activer($payement);

        if ($abonnement) {
            Log::info("ActiverAbonnementPaye : abonnement {$abonnement->id} activé jusqu'au {$abonnement->date_fin} (paiement {$payement->id}).");
        }
    }
}
