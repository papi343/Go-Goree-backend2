<?php

namespace App\Repositories\Eloquent;

use App\Models\Payement;
use App\Enums\StatutPayementEnum;
use App\Repositories\Contracts\PayementRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Implémentation Eloquent du dépôt pour la gestion des paiements.
 */
class PayementRepository implements PayementRepositoryInterface
{
    /**
     * Trouver un paiement par son identifiant.
     */
    public function find(string $id): ?Payement
    {
        return Payement::find($id);
    }

    /**
     * Trouver un paiement par sa référence unique.
     */
    public function findByReference(string $reference): ?Payement
    {
        return Payement::where('reference', $reference)->first();
    }

    /**
     * Trouver un paiement par son jeton (token) PayDunya de transaction.
     */
    public function findByToken(string $token): ?Payement
    {
        return Payement::where('paydunya_token', $token)->first();
    }

    /**
     * Créer un nouveau paiement.
     */
    public function create(array $data): Payement
    {
        return Payement::create($data);
    }

    /**
     * Mettre à jour un paiement existant.
     */
    public function update(string $id, array $data): Payement
    {
        $payement = Payement::findOrFail($id);
        $payement->update($data);
        return $payement;
    }

    /**
     * Supprimer un paiement.
     */
    public function delete(string $id): bool
    {
        $payement = Payement::find($id);
        if ($payement) {
            return $payement->delete();
        }
        return false;
    }

    /**
     * Paginer la liste des paiements.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Payement::paginate($perPage);
    }

    /**
     * Obtenir les rapports de ventes (nombre total, montant total, statistiques par mode) sur une période donnée.
     */
    public function getSalesReports(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $stats = Payement::where('statut', StatutPayementEnum::ACCEPTE)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('COUNT(*) as total_transactions'),
                DB::raw('SUM(montant) as total_amount')
            )
            ->first();

        $byMode = Payement::where('statut', StatutPayementEnum::ACCEPTE)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('mode')
            ->select('mode', DB::raw('COUNT(*) as transactions'), DB::raw('SUM(montant) as amount'))
            ->get()
            ->toArray();

        return [
            'total_transactions' => (int) ($stats->total_transactions ?? 0),
            'total_amount' => (float) ($stats->total_amount ?? 0),
            'by_mode' => $byMode,
        ];
    }
}
