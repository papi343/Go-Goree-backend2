<?php

namespace App\Http\Requests\Api\V1\Voyages;

use App\Models\Voyage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $voyageId = $this->route('voyage');
        $trajetId = $this->input('trajet_id', Voyage::find($voyageId)?->trajet_id);

        return [
            'date_voyage' => [
                'sometimes',
                'date',
                'after_or_equal:today',
                // Reflète la contrainte unique (trajet_id, date_voyage) en base.
                Rule::unique('voyages')
                    ->where(fn ($query) => $query->where('trajet_id', $trajetId))
                    ->ignore($voyageId)
                    ->whereNull('deleted_at'),
            ],
            'places'      => ['sometimes', 'integer', 'min:1'],
            'trajet_id'   => ['sometimes', 'exists:trajets,id'],
            'chaloupe_id' => ['sometimes', 'exists:chaloupes,id'],
            // places_restantes est recalculé automatiquement côté serveur :
            // nouvelles_places − billets_vendus (statut PAYE ou UTILISE).
        ];
    }
}
