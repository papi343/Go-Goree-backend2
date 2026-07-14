<?php

namespace App\Http\Controllers\Api\V1\Voyages;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Voyages\StoreVoyageRequest;
use App\Http\Requests\Api\V1\Voyages\UpdateVoyageRequest;
use App\Http\Resources\Api\V1\VoyageResource;
use App\Models\Voyage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Contrôleur pour gérer les instances de voyage d'une chaloupe.
 */
class VoyageController extends Controller
{
    /**
     * Liste des voyages, avec filtres :
     *   ?periode=today      → uniquement aujourd'hui
     *   ?periode=semaine    → 7 prochains jours (défaut : voyages à venir)
     *   ?date=YYYY-MM-DD    → une date précise
     *   ?disponibles=true   → uniquement ceux avec des places restantes
     */
    public function index(Request $request)
    {
        $query = Voyage::with(['trajet', 'chaloupe'])
            ->orderBy('date_voyage');

        $today = now()->toDateString();

        if ($request->filled('date')) {
            $query->whereDate('date_voyage', $request->date);
        } elseif ($request->periode === 'today') {
            $query->whereDate('date_voyage', $today);
        } elseif ($request->periode === 'semaine') {
            $query->whereBetween('date_voyage', [$today, now()->addDays(6)->toDateString()]);
        } else {
            // Par défaut : les voyages à venir (aujourd'hui et après).
            $query->whereDate('date_voyage', '>=', $today);
        }

        if ($request->boolean('disponibles')) {
            $query->where('places_restantes', '>', 0);
        }

        return VoyageResource::collection($query->paginate());
    }

    /**
     * Enregistrer un nouveau voyage.
     */
    public function store(StoreVoyageRequest $request)
    {
        $record = Voyage::create($request->validated());

        return (new VoyageResource($record->load(['trajet', 'chaloupe'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Afficher les détails d'un voyage spécifique.
     */
    public function show($id)
    {
        $record = Voyage::with(['trajet', 'chaloupe'])->findOrFail($id);

        return new VoyageResource($record);
    }

    /**
     * Mettre à jour un voyage.
     */
    public function update(UpdateVoyageRequest $request, $id)
    {
        $record = Voyage::findOrFail($id);
        $record->update($request->validated());

        return new VoyageResource($record->load(['trajet', 'chaloupe']));
    }

    /**
     * Supprimer un voyage.
     */
    public function destroy($id)
    {
        $record = Voyage::findOrFail($id);
        $record->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Déclencher manuellement la génération des voyages des 7 prochains jours.
     */
    public function generer()
    {
        (new \App\Jobs\GenererVoyagesSemaineJob)->handle();
        return response()->json([
            'message' => 'Génération des voyages pour les 7 prochains jours terminée avec succès.'
        ]);
    }
}
