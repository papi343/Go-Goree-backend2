<?php

namespace App\Http\Requests\Api\V1\Residents;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Requête de validation pour la création manuelle d'un abonnement (admin).
 */
class StoreAbonnementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after:date_debut'],
            'montant' => ['required', 'numeric', 'min:0'],
            'resident_id' => ['required', 'uuid', 'exists:residents,id'],
            'plan_id' => ['required', 'uuid', 'exists:plans,id'],
            'payement_id' => ['nullable', 'uuid', 'exists:payements,id'],
        ];
    }
}
