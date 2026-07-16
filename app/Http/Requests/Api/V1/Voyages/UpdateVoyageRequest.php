<?php

namespace App\Http\Requests\Api\V1\Voyages;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Requête de validation pour la mise à jour d'un voyage.
 */
class UpdateVoyageRequest extends FormRequest
{
    /**
     * Déterminer si l'utilisateur est autorisé à effectuer cette requête.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation appliquées à la requête.
     */
    public function rules(): array
    {
        return [
            'date_voyage' => ['sometimes', 'date', 'after_or_equal:today'],
            'places'      => ['sometimes', 'integer', 'min:1'],
            'trajet_id'   => ['sometimes', 'exists:trajets,id'],
            'chaloupe_id' => ['sometimes', 'exists:chaloupes,id'],
            // places_restantes est recalculé automatiquement côté serveur :
            // nouvelles_places − billets_vendus (statut PAYE ou UTILISE).
        ];
    }
}
