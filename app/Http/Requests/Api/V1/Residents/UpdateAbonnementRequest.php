<?php

namespace App\Http\Requests\Api\V1\Residents;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Requête de validation pour la mise à jour d'un abonnement (admin).
 */
class UpdateAbonnementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_debut' => ['sometimes', 'date'],
            'date_fin' => ['sometimes', 'date', 'after:date_debut'],
            'montant' => ['sometimes', 'numeric', 'min:0'],
            'resident_id' => ['sometimes', 'uuid', 'exists:residents,id'],
            'plan_id' => ['sometimes', 'uuid', 'exists:plans,id'],
            'payement_id' => ['nullable', 'uuid', 'exists:payements,id'],
        ];
    }
}
