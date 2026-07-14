<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource de présentation JSON pour une Demande de Résidence.
 */
class DemandeResidenceResource extends JsonResource
{
    /**
     * Transformer la ressource en tableau.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->user ? ($this->user->prenom . ' ' . $this->user->nom) : 'Passager inconnu',
            'carte_identite' => $this->carte_identite,
            'residence' => $this->residence,
            'statut' => $this->statut?->value ?? $this->statut,
            'photo' => $this->photo,
            'cni_recto' => $this->cni_recto,
            'cni_verso' => $this->cni_verso,
            'certificat_residence' => $this->certificat_residence,
            'docs' => array_values(array_filter([
                $this->cni_recto ? basename($this->cni_recto) : null,
                $this->cni_verso ? basename($this->cni_verso) : null,
                $this->certificat_residence ? basename($this->certificat_residence) : null,
            ])),
            'motif_refus' => $this->motif_refus,
            'valide_par' => $this->valide_par,
            'date_validation' => $this->date_validation,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
