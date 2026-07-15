<?php

namespace App\Http\Controllers\Api\V1\Rapports;

use App\Http\Controllers\Controller;
use App\Models\Rapport;
use App\Services\Rapports\RapportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class RapportController extends Controller
{
    public function __construct(protected RapportService $rapportService) {}

    /**
     * Get list of reports.
     */
    public function index()
    {
        return response()->json($this->rapportService->listRapports());
    }

    /**
     * Generate a new report.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => ['required', 'string', 'max:255'],
            'mois' => ['required', 'string', 'max:255'],
            'format' => ['required', 'string', 'max:255'],
        ]);

        $rapport = $this->rapportService->generateRapport($request->type, $request->mois, $request->format);

        return response()->json($rapport, Response::HTTP_CREATED);
    }

    /**
     * Download the report file.
     */
    public function telecharger($id)
    {
        $rapport = Rapport::findOrFail($id);

        if (!Storage::disk('public')->exists($rapport->chemin_stockage)) {
            return response()->json([
                'message' => 'Fichier introuvable sur le serveur.',
            ], Response::HTTP_NOT_FOUND);
        }

        return Storage::disk('public')->download($rapport->chemin_stockage, $rapport->nom_fichier);
    }
}
