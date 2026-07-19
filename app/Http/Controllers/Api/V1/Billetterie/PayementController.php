<?php

namespace App\Http\Controllers\Api\V1\Billetterie;

use App\Enums\StatutPayementEnum;
use App\Events\PaiementAccepte;
use App\Events\PaiementRefuse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Billetterie\StorePayementRequest;
use App\Http\Requests\Api\V1\Billetterie\UpdatePayementRequest;
use App\Models\Payement;
use Illuminate\Http\Response;

/**
 * Contrôleur pour gérer les transactions de paiement (CRUD administratif).
 */
class PayementController extends Controller
{
    /**
     * Liste des paiements.
     */
    public function index()
    {
        return response()->json(Payement::with('user')->paginate());
    }

    /**
     * Créer manuellement un enregistrement de paiement.
     */
    public function store(StorePayementRequest $request)
    {
        $record = Payement::create($request->validated());

        return response()->json($record, Response::HTTP_CREATED);
    }

    /**
     * Afficher les détails d'un paiement spécifique.
     */
    public function show($id)
    {
        return response()->json(Payement::findOrFail($id));
    }

    /**
     * Mettre à jour les informations d'un paiement.
     */
    public function update(UpdatePayementRequest $request, $id)
    {
        $record = Payement::findOrFail($id);
        $oldStatus = $record->statut;
        $record->update($request->validated());

        if ($record->statut !== $oldStatus) {
            if ($record->statut === StatutPayementEnum::ACCEPTE) {
                event(new PaiementAccepte($record));
            } elseif ($record->statut === StatutPayementEnum::REFUSE) {
                event(new PaiementRefuse($record));
            }
        }

        return response()->json($record);
    }

    /**
     * Supprimer un enregistrement de paiement.
     */
    public function destroy($id)
    {
        $record = Payement::findOrFail($id);
        $record->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
