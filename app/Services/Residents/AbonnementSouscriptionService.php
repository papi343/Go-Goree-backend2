<?php

declare(strict_types=1);

namespace App\Services\Residents;

use App\Enums\ModePayementEnum;
use App\Enums\StatutPayementEnum;
use App\Enums\TypeTransactionPayDunyaEnum;
use App\Events\PaiementAccepte;
use App\Events\PaiementInitie;
use App\Models\Abonnement;
use App\Models\Payement;
use App\Models\Plan;
use App\Models\User;
use App\Services\Paiements\PayDunya\Exceptions\PayDunyaException;
use App\Services\Paiements\PayDunyaPaymentService;
use App\Services\Portefeuille\PortefeuilleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Gère la souscription d'un abonnement par un résident : création du paiement,
 * puis activation de l'abonnement (immédiate en portefeuille, ou via le webhook
 * PayDunya pour les paiements externes).
 */
class AbonnementSouscriptionService
{
    public function __construct(
        protected PayDunyaPaymentService $payDunya,
        protected PortefeuilleService $portefeuilleService,
    ) {}

    /**
     * Initier la souscription d'un plan par un utilisateur résident.
     *
     * @return array{payement: Payement, abonnement: ?Abonnement, redirect_url: ?string}
     */
    public function souscrire(User $user, Plan $plan, ModePayementEnum $mode): array
    {
        if (! $user->est_resident || ! $user->resident) {
            throw new \RuntimeException('Seul un résident peut souscrire un abonnement.');
        }

        return DB::transaction(function () use ($user, $plan, $mode) {
            $payement = Payement::create([
                'reference' => 'ABON_'.Str::random(12),
                'montant' => $plan->prix,
                'statut' => StatutPayementEnum::EN_COURS,
                'mode' => $mode,
                'type_transaction' => TypeTransactionPayDunyaEnum::ABONNEMENT,
                'paydunya_token' => null,
                'plan_id' => $plan->id,
                'user_id' => $user->id,
            ]);

            if ($mode === ModePayementEnum::PORTEFEUILLE) {
                $this->portefeuilleService->debiter($user->id, (float) $plan->prix, $payement->id);
                $payement->update(['statut' => StatutPayementEnum::ACCEPTE]);

                $abonnement = $this->activer($payement);
                event(new PaiementAccepte($payement));

                return ['payement' => $payement, 'abonnement' => $abonnement, 'redirect_url' => null];
            }

            try {
                $result = $this->payDunya->initier($payement, "Abonnement Go Gorée ({$plan->nom})");
            } catch (PayDunyaException $e) {
                throw new \RuntimeException("Échec de l'initiation du paiement : ".$e->getMessage());
            }

            event(new PaiementInitie($payement));

            return ['payement' => $payement->refresh(), 'abonnement' => null, 'redirect_url' => $result->checkoutUrl];
        });
    }

    /**
     * Créer/prolonger l'abonnement correspondant à un paiement accepté.
     * Idempotent : ne crée rien si un abonnement est déjà rattaché au paiement.
     */
    public function activer(Payement $payement): ?Abonnement
    {
        $user = $payement->user;
        $plan = $payement->plan;

        if (! $user || ! $user->resident || ! $plan) {
            return null;
        }

        // Idempotence fiable : lien dur au paiement (rejeu du webhook, etc.).
        $existant = Abonnement::where('payement_id', $payement->id)->first();
        if ($existant) {
            return $existant;
        }

        // Prolongation : on part de la fin de l'abonnement actif s'il existe.
        $actif = Abonnement::where('resident_id', $user->resident->id)
            ->where('date_fin', '>', now())
            ->orderByDesc('date_fin')
            ->first();

        $debut = $actif ? $actif->date_fin->copy() : now();

        return Abonnement::create([
            'resident_id' => $user->resident->id,
            'plan_id' => $plan->id,
            'payement_id' => $payement->id,
            'date_debut' => $debut,
            'date_fin' => $debut->copy()->addMonths($plan->duree_mois),
            'montant' => $plan->prix,
        ]);
    }
}
