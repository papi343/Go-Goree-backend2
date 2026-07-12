<?php

namespace App\Services\Billetterie;

use App\Models\User;
use App\Models\Billet;
use App\Enums\CategorieEnum;
use App\Enums\ModePayementEnum;
use App\Enums\StatutBilletEnum;
use App\Repositories\Contracts\BilletRepositoryInterface;
use App\Services\Billetterie\SubServices\TarifResolverService;
use App\Services\Billetterie\SubServices\PlaceReservationService;
use App\Services\Billetterie\SubServices\BilletQrTokenGeneratorService;
use App\Services\Billetterie\SubServices\PaymentInitiationService;
use App\Services\Portefeuille\PortefeuilleService;
use Illuminate\Support\Facades\DB;
use App\Events\BilletAchete;
use App\Events\PaiementInitie;

/**
 * Service orchestrant l'achat complet de billets de transport.
 * Gère la résolution des tarifs, la réservation de places, la création du billet, et l'initiation du paiement.
 */
class BilletPurchaseService
{
    public function __construct(
        protected BilletRepositoryInterface $billetRepository,
        protected TarifResolverService $tarifResolver,
        protected PlaceReservationService $placeReservation,
        protected BilletQrTokenGeneratorService $qrTokenGenerator,
        protected PaymentInitiationService $paymentInitiation,
        protected PortefeuilleService $portefeuilleService
    ) {
    }

    /**
     * Effectue le flux complet d'achat d'un billet.
     */
    public function purchase(User $user, string $voyageId, ModePayementEnum $paymentMode, ?CategorieEnum $requestedCategory = null): array
    {
        return DB::transaction(function () use ($user, $voyageId, $paymentMode, $requestedCategory) {
            $tarif = $this->tarifResolver->resolve($user, $requestedCategory);

            $reserved = $this->placeReservation->reserve($voyageId, 1);
            if (!$reserved) {
                throw new \Exception("Pas de places disponibles pour ce voyage.");
            }

            $qrToken = $this->qrTokenGenerator->generate();

            $billet = $this->billetRepository->create([
                'qr_token' => $qrToken,
                'montant' => $tarif->prix,
                'statut' => StatutBilletEnum::EN_ATTENTE_PAIEMENT,
                'voyage_id' => $voyageId,
                'tarif_id' => $tarif->id,
                'user_id' => $user->id,
            ]);

            $paymentResult = $this->paymentInitiation->initiate($billet, $paymentMode);

            if (!$paymentResult['success']) {
                throw new \Exception("Échec de l'initiation du paiement: " . ($paymentResult['message'] ?? ''));
            }

            $payement = $paymentResult['payement'];

            if ($paymentMode === ModePayementEnum::PORTEFEUILLE) {
                $this->portefeuilleService->debiter($user->id, (float) $tarif->prix, $payement->id);
                
                $payement->update(['statut' => \App\Enums\StatutPayementEnum::ACCEPTE]);
                $billet->update(['statut' => StatutBilletEnum::PAYE]);

                event(new BilletAchete($billet));
            } else {
                event(new PaiementInitie($payement));
            }

            return [
                'billet' => $billet,
                'payement' => $payement,
                'redirect_url' => $paymentResult['redirect_url'] ?? null,
            ];
        });
    }
}
