<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource Eloquent pour représenter un utilisateur dans les réponses de l'API.
 */
class UserResource extends JsonResource
{
    /**
     * Transformer la ressource en tableau.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'prenom' => $this->prenom,
            'nom' => $this->nom,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'active' => (bool) $this->active,
            'role' => $this->relationLoaded('role') && $this->role ? [
                'id' => $this->role->id,
                'nom' => $this->role->nom,
            ] : null,
            'portefeuille' => $this->relationLoaded('portefeuille') && $this->portefeuille ? [
                'solde' => $this->portefeuille->solde,
            ] : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
