<?php

namespace App\Http\Controllers\Api\V1\Billetterie;

use App\Enums\NiveauAlerteFraudeEnum;
use App\Enums\ResultatScanEnum;
use App\Enums\StatutAlerteFraudeEnum;
use App\Enums\StatutBilletEnum;
use App\Events\BilletScanne;
use App\Events\FraudeDetectee;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BilletResource;
use App\Models\AlerteFraude;
use App\Models\Billet;
use App\Models\Embarquement;
use App\Models\Scan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Scan des billets à l'embarquement.
 *
 * Le scan est rattaché à une session d'embarquement (donc à un voyage). La
 * validation utilise un UPDATE atomique conditionnel : une seule requête
 * indexée, sans verrou applicatif, ce qui gère nativement plusieurs contrôleurs
 * scannant simultanément (un seul peut faire passer le billet à UTILISE).
 */
class ScanController extends Controller
{
    public function index()
    {
        return response()->json(Scan::with('billet')->paginate());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'qr_token' => ['required', 'string'],
            'embarquement_id' => ['required', 'uuid', 'exists:embarquements,id'],
        ]);

        $embarquement = Embarquement::findOrFail($data['embarquement_id']);

        if (! $embarquement->estOuvert()) {
            return response()->json([
                'message' => "Cette session d'embarquement est fermée.",
            ], Response::HTTP_CONFLICT);
        }

        // Lookup indexé (qr_token est unique) — colonnes minimales.
        $billet = Billet::where('qr_token', $data['qr_token'])->first(['id', 'statut', 'voyage_id']);

        if (! $billet) {
            return response()->json([
                'message' => 'Billet non trouvé.',
                'resultat' => ResultatScanEnum::NON_EMBARQUE->value,
            ], Response::HTTP_NOT_FOUND);
        }

        // Billet d'un autre voyage que celui embarqué : confusion de voyage.
        if ($billet->voyage_id !== $embarquement->voyage_id) {
            $resultat = ResultatScanEnum::MAUVAIS_VOYAGE;
        } else {
            // Réclamation atomique : seul le 1er scan concurrent réussit.
            $valide = Billet::where('qr_token', $data['qr_token'])
                ->where('statut', StatutBilletEnum::PAYE->value)
                ->update(['statut' => StatutBilletEnum::UTILISE->value]) === 1;

            $resultat = $valide
                ? ResultatScanEnum::VALIDE
                : $this->resultatEchec($billet->fresh()->statut);
        }

        $scan = Scan::create([
            'billet_id' => $billet->id,
            'embarquement_id' => $embarquement->id,
            'scanne_par' => $request->user()->id,
            'resultat' => $resultat,
        ]);

        if ($resultat === ResultatScanEnum::VALIDE) {
            event(new BilletScanne($scan));
        } elseif ($resultat === ResultatScanEnum::DEJA_SCANNE) {
            $this->signalerDoubleScan($billet->id, $embarquement->id);
        }

        return response()->json([
            'message' => $resultat === ResultatScanEnum::VALIDE ? 'Scan validé.' : 'Scan invalide.',
            'resultat' => $resultat->value,
            'scan' => $scan,
            'billet' => new BilletResource(Billet::with(['voyage', 'tarif'])->find($billet->id)),
        ], $resultat === ResultatScanEnum::VALIDE ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function show($id)
    {
        return response()->json(Scan::with('billet')->findOrFail($id));
    }

    /**
     * Détermine le résultat quand la réclamation atomique a échoué.
     */
    private function resultatEchec(StatutBilletEnum $statut): ResultatScanEnum
    {
        return match ($statut) {
            StatutBilletEnum::UTILISE => ResultatScanEnum::DEJA_SCANNE,
            StatutBilletEnum::EXPIRE => ResultatScanEnum::EXPIRE,
            default => ResultatScanEnum::NON_EMBARQUE,
        };
    }

    /**
     * Signale une fraude lorsqu'un billet déjà utilisé est scanné à nouveau.
     */
    private function signalerDoubleScan(string $billetId, string $embarquementId): void
    {
        $alerte = AlerteFraude::create([
            'payement_id' => null,
            'niveau' => NiveauAlerteFraudeEnum::SUSPECT,
            'regle_declenchee' => 'double_scan_billet',
            'payload_suspect' => ['billet_id' => $billetId, 'embarquement_id' => $embarquementId],
            'statut' => StatutAlerteFraudeEnum::EN_ATTENTE,
        ]);

        event(new FraudeDetectee($alerte));
    }
}
