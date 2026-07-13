<?php

namespace App\Http\Controllers\Api\V1\Residents;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Plans d'abonnement : consultation ouverte, gestion réservée aux administrateurs.
 */
class PlanController extends Controller
{
    /**
     * Liste des plans actifs (visible par tout utilisateur authentifié).
     */
    public function index()
    {
        return response()->json(Plan::where('actif', true)->orderBy('duree_mois')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'duree_mois' => ['required', 'integer', 'min:1', 'max:60'],
            'prix' => ['required', 'numeric', 'min:0'],
            'actif' => ['sometimes', 'boolean'],
        ]);

        return response()->json(Plan::create($data), Response::HTTP_CREATED);
    }

    public function show($id)
    {
        return response()->json(Plan::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $plan = Plan::findOrFail($id);

        $data = $request->validate([
            'nom' => ['sometimes', 'string', 'max:255'],
            'duree_mois' => ['sometimes', 'integer', 'min:1', 'max:60'],
            'prix' => ['sometimes', 'numeric', 'min:0'],
            'actif' => ['sometimes', 'boolean'],
        ]);

        $plan->update($data);

        return response()->json($plan);
    }

    public function destroy($id)
    {
        Plan::findOrFail($id)->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
