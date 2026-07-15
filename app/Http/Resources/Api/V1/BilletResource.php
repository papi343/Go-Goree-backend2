<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource de présentation JSON pour un Billet.
 */
class BilletResource extends JsonResource
{
    /**
     * Transformer la ressource en tableau.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'qr_token' => $this->qr_token,
            'montant' => $this->montant,
            'statut' => $this->statut,
            'voyage' => new VoyageResource($this->whenLoaded('voyage')),
            'tarif' => $this->tarif,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'prenom' => $this->user->prenom,
                'nom' => $this->user->nom,
                'email' => $this->user->email,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
