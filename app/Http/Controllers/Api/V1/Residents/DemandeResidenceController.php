<?php

namespace App\Http\Controllers\Api\V1\Residents;

use App\Enums\DemandeResidenceEnum;
use App\Enums\RoleEnum;
use App\Events\DemandeResidenceSoumise;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Residents\StoreDemandeResidenceRequest;
use App\Http\Requests\Api\V1\Residents\ValiderDemandeResidenceRequest;
use App\Http\Resources\Api\V1\DemandeResidenceResource;
use App\Models\DemandeResidence;
use App\Services\Residents\DemandeResidenceValidationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Contrôleur pour gérer les demandes de résidence (soumission, validation, refus).
 */
class DemandeResidenceController extends Controller
{
    /**
     * @var DemandeResidenceValidationService
     */
    public function __construct(protected DemandeResidenceValidationService $validationService) {}

    /**
     * Liste des demandes de résidence de l'utilisateur (ou toutes les demandes si Admin/Agent).
     */
    public function index(Request $request)
    {
        $query = DemandeResidence::query();

        if ($request->user()->role && $request->user()->role->nom === RoleEnum::CLIENT) {
            $query->where('user_id', $request->user()->id);
        }

        return DemandeResidenceResource::collection($query->paginate());
    }

    /**
     * Soumettre une nouvelle demande de résidence.
     */
    public function store(StoreDemandeResidenceRequest $request)
    {
        $photoPath = $request->photo;
        if ($request->hasFile('photo_file')) {
            $photoPath = $request->file('photo_file')->store('demandes_residence', 'public');
        }

        $cniRectoPath = $request->cni_recto;
        if ($request->hasFile('cni_recto_file')) {
            $cniRectoPath = $request->file('cni_recto_file')->store('demandes_residence', 'public');
        }

        $cniVersoPath = $request->cni_verso;
        if ($request->hasFile('cni_verso_file')) {
            $cniVersoPath = $request->file('cni_verso_file')->store('demandes_residence', 'public');
        }

        $certificatResidencePath = $request->certificat_residence;
        if ($request->hasFile('certificat_residence_file')) {
            $certificatResidencePath = $request->file('certificat_residence_file')->store('demandes_residence', 'public');
        }

        $demande = DemandeResidence::create([
            'carte_identite' => $request->carte_identite,
            'residence' => $request->residence,
            'photo' => $photoPath ?? 'photo_defaut.png',
            'cni_recto' => $cniRectoPath,
            'cni_verso' => $cniVersoPath,
            'certificat_residence' => $certificatResidencePath,
            'statut' => DemandeResidenceEnum::EN_COURS,
            'user_id' => $request->user()->id,
        ]);

        event(new DemandeResidenceSoumise($demande));

        return response()->json([
            'message' => 'Demande de résidence soumise avec succès.',
            'demande' => new DemandeResidenceResource($demande),
        ], Response::HTTP_CREATED);
    }

    /**
     * Afficher les détails d'une demande de résidence spécifique.
     */
    public function show(Request $request, $id)
    {
        $demande = DemandeResidence::findOrFail($id);

        if ($request->user()->role && $request->user()->role->nom === RoleEnum::CLIENT && $demande->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], Response::HTTP_FORBIDDEN);
        }

        return new DemandeResidenceResource($demande);
    }

    /**
     * Valider (approuver) une demande de résidence.
     */
    public function valider(ValiderDemandeResidenceRequest $request, $id)
    {
        $demande = DemandeResidence::findOrFail($id);

        // Vérifier l'autorisation via la policy
        if ($request->user()->cannot('validate', $demande)) {
            return response()->json(['message' => 'Non autorisé.'], Response::HTTP_FORBIDDEN);
        }

        $this->validationService->valider($demande, $request->user());

        return response()->json([
            'message' => 'Demande de résidence validée.',
            'demande' => new DemandeResidenceResource($demande),
        ]);
    }

    /**
     * Refuser une demande de résidence avec un motif.
     */
    public function refuser(ValiderDemandeResidenceRequest $request, $id)
    {
        $demande = DemandeResidence::findOrFail($id);

        // Vérifier l'autorisation via la policy
        if ($request->user()->cannot('validate', $demande)) {
            return response()->json(['message' => 'Non autorisé.'], Response::HTTP_FORBIDDEN);
        }

        $request->validate([
            'motif_refus' => ['required', 'string'],
        ]);

        $this->validationService->refuser($demande, $request->motif_refus, $request->user());

        return response()->json([
            'message' => 'Demande de résidence refusée.',
            'demande' => new DemandeResidenceResource($demande),
        ]);
    }
}
