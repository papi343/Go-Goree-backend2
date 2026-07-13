<?php

namespace App\Http\Controllers\Api\V1\Billetterie;

use App\Enums\StatutEmbarquementEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Billetterie\OuvrirEmbarquementRequest;
use App\Models\Embarquement;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Gestion des sessions d'embarquement par les contrôleurs.
 */
class EmbarquementController extends Controller
{
    /**
     * Sessions d'embarquement ouvertes.
     */
    public function index()
    {
        return response()->json(
            Embarquement::with('voyage')
                ->where('statut', StatutEmbarquementEnum::OUVERT->value)
                ->latest()
                ->paginate()
        );
    }

    /**
     * Ouvrir (ou récupérer) la session d'embarquement d'un voyage.
     * Idempotent : plusieurs contrôleurs partagent la même session.
     */
    public function ouvrir(OuvrirEmbarquementRequest $request)
    {
        $embarquement = Embarquement::firstOrCreate(
            ['voyage_id' => $request->voyage_id, 'statut' => StatutEmbarquementEnum::OUVERT->value],
            ['ouvert_a' => now(), 'ouvert_par' => $request->user()->id]
        );

        return response()->json($embarquement->load('voyage'), Response::HTTP_OK);
    }

    /**
     * Fermer une session d'embarquement.
     */
    public function fermer(Request $request, $id)
    {
        $embarquement = Embarquement::findOrFail($id);
        $embarquement->update([
            'statut' => StatutEmbarquementEnum::FERME,
            'ferme_a' => now(),
        ]);

        return response()->json($embarquement);
    }
}
