<?php

namespace App\Http\Controllers\Api\V1\Residents;

use App\Enums\ModePayementEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Residents\SouscrireAbonnementRequest;
use App\Http\Requests\Api\V1\Residents\StoreAbonnementRequest;
use App\Http\Requests\Api\V1\Residents\UpdateAbonnementRequest;
use App\Models\Abonnement;
use App\Models\Plan;
use App\Services\Residents\AbonnementSouscriptionService;
use Illuminate\Http\Response;

/**
 * Contrôleur pour gérer les abonnements des résidents (CRUD administratif + souscription).
 */
class AbonnementController extends Controller
{
    /**
     * Souscrire un abonnement (résident) : débit portefeuille immédiat ou lien PayDunya.
     */
    public function souscrire(SouscrireAbonnementRequest $request, AbonnementSouscriptionService $service)
    {
        $plan = Plan::where('actif', true)->findOrFail($request->plan_id);

        try {
            $result = $service->souscrire(
                $request->user(),
                $plan,
                ModePayementEnum::from($request->payment_mode),
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'message' => $result['abonnement']
                ? 'Abonnement activé avec succès.'
                : 'Souscription initiée, finalisez le paiement.',
            'abonnement' => $result['abonnement'],
            'reference' => $result['payement']->reference,
            'redirect_url' => $result['redirect_url'],
        ], Response::HTTP_CREATED);
    }

    /**
     * Liste des abonnements.
     */
    public function index()
    {
        return response()->json(Abonnement::paginate());
    }

    /**
     * Créer un nouvel abonnement.
     */
    public function store(StoreAbonnementRequest $request)
    {
        $record = Abonnement::create($request->validated());

        return response()->json($record, Response::HTTP_CREATED);
    }

    /**
     * Afficher les détails d'un abonnement spécifique.
     */
    public function show($id)
    {
        return response()->json(Abonnement::findOrFail($id));
    }

    /**
     * Mettre à jour un abonnement.
     */
    public function update(UpdateAbonnementRequest $request, $id)
    {
        $record = Abonnement::findOrFail($id);
        $record->update($request->validated());

        return response()->json($record);
    }

    /**
     * Supprimer un abonnement.
     */
    public function destroy($id)
    {
        $record = Abonnement::findOrFail($id);
        $record->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
