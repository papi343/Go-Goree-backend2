<?php

namespace App\Services\Billetterie;

use App\Enums\CategorieEnum;
use App\Enums\ModePayementEnum;
use App\Enums\NiveauAlerteFraudeEnum;
use App\Enums\StatutAlerteFraudeEnum;
use App\Enums\StatutBilletEnum;
use App\Enums\StatutPayementEnum;
use App\Events\BilletAchete;
use App\Events\FraudeDetectee;
use App\Events\PaiementAccepte;
use App\Events\PaiementInitie;
use App\Models\AlerteFraude;
use App\Models\Billet;
use App\Models\User;
use App\Repositories\Contracts\BilletRepositoryInterface;
use App\Services\Billetterie\SubServices\BilletQrTokenGeneratorService;
use App\Services\Billetterie\SubServices\PaymentInitiationService;
use App\Services\Billetterie\SubServices\PlaceReservationService;
use App\Services\Billetterie\SubServices\ResidentAbonnementCheckerService;
use App\Services\Billetterie\SubServices\TarifResolverService;
use App\Services\Portefeuille\PortefeuilleService;
use Illuminate\Support\Facades\DB;

/**
 * Orchestration de la génération/achat d'un billet.
 *
 * Règles métier :
 *  - Un seul billet actif par (client, voyage) : toute tentative en double
 *    déclenche une alerte de fraude et est refusée.
 *  - Résident avec abonnement actif : le billet est GÉNÉRÉ gratuitement
 *    (aucun paiement) — l'abonnement couvre le trajet.
 *  - Sinon : achat normal (tarif réduit résident si applicable, sinon plein tarif).
 */
class BilletPurchaseService
{
    /**
     * Statuts considérés comme « actifs » (un billet déjà valable pour ce voyage).
     */
    private const STATUTS_ACTIFS = [
        StatutBilletEnum::EN_ATTENTE_PAIEMENT->value,
        StatutBilletEnum::PAYE->value,
        StatutBilletEnum::UTILISE->value,
    ];

    public function __construct(
        protected BilletRepositoryInterface $billetRepository,
        protected TarifResolverService $tarifResolver,
        protected PlaceReservationService $placeReservation,
        protected BilletQrTokenGeneratorService $qrTokenGenerator,
        protected PaymentInitiationService $paymentInitiation,
        protected PortefeuilleService $portefeuilleService,
        protected ResidentAbonnementCheckerService $abonnementChecker,
    ) {}

    public function purchase(User $user, string $voyageId, ModePayementEnum $paymentMode, ?CategorieEnum $requestedCategory = null): array
    {
        // Anti-doublon (hors transaction pour conserver l'alerte de fraude en cas de refus).
        if ($this->possedeDejaUnBillet($user->id, $voyageId)) {
            $this->signalerDoubleBillet($user, $voyageId);

            throw new \RuntimeException('Vous avez déjà un billet pour ce voyage.');
        }

        return DB::transaction(function () use ($user, $voyageId, $paymentMode, $requestedCategory) {
            $estAbonne = $this->abonnementChecker->check($user);
            $tarif = $this->tarifResolver->resolve($user, $requestedCategory);

            if (! $this->placeReservation->reserve($voyageId, 1)) {
                throw new \RuntimeException('Pas de places disponibles pour ce voyage.');
            }

            $billet = $this->billetRepository->create([
                'qr_token' => $this->qrTokenGenerator->generate(),
                'montant' => $estAbonne ? 0 : $tarif->prix,
                'statut' => $estAbonne ? StatutBilletEnum::PAYE : StatutBilletEnum::EN_ATTENTE_PAIEMENT,
                'voyage_id' => $voyageId,
                'tarif_id' => $tarif->id,
                'user_id' => $user->id,
            ]);

            // Résident abonné : génération gratuite, aucun paiement.
            if ($estAbonne) {
                event(new BilletAchete($billet));

                return ['billet' => $billet, 'payement' => null, 'redirect_url' => null];
            }

            // Sinon : flux de paiement.
            $paymentResult = $this->paymentInitiation->initiate($billet, $paymentMode);
            if (! $paymentResult['success']) {
                throw new \RuntimeException("Échec de l'initiation du paiement: ".($paymentResult['message'] ?? ''));
            }

            $payement = $paymentResult['payement'];

            if ($paymentMode === ModePayementEnum::PORTEFEUILLE) {
                $this->portefeuilleService->debiter($user->id, (float) $tarif->prix, $payement->id);
                $payement->update(['statut' => StatutPayementEnum::ACCEPTE]);
                $billet->update(['statut' => StatutBilletEnum::PAYE]);

                event(new PaiementAccepte($payement));
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

    /**
     * L'utilisateur a-t-il déjà un billet actif pour ce voyage ?
     */
    private function possedeDejaUnBillet(string $userId, string $voyageId): bool
    {
        return Billet::where('user_id', $userId)
            ->where('voyage_id', $voyageId)
            ->whereIn('statut', self::STATUTS_ACTIFS)
            ->exists();
    }

    /**
     * Enregistre une alerte de fraude pour une tentative de double billet.
     */
    private function signalerDoubleBillet(User $user, string $voyageId): void
    {
        $alerte = AlerteFraude::create([
            'payement_id' => null,
            'niveau' => NiveauAlerteFraudeEnum::SUSPECT,
            'regle_declenchee' => 'double_billet_voyage',
            'payload_suspect' => ['user_id' => $user->id, 'voyage_id' => $voyageId],
            'statut' => StatutAlerteFraudeEnum::EN_ATTENTE,
        ]);

        event(new FraudeDetectee($alerte));
    }
}
