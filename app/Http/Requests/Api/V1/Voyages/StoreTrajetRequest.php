<?php

namespace App\Http\Requests\Api\V1\Voyages;

use App\Enums\JourEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Requête de validation pour la création d'un trajet.
 */
class StoreTrajetRequest extends FormRequest
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
            'jour' => ['required', new Enum(JourEnum::class)],
            'heure_depart' => [
                'required',
                'date_format:H:i',
                Rule::unique('trajets')
                    ->where(fn ($query) => $query->where('jour', $this->jour))
                    ->whereNull('deleted_at'),
            ],
            'duree' => ['required', 'numeric', 'min:1'],
        ];
    }
}
