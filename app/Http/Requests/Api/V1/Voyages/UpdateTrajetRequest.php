<?php

namespace App\Http\Requests\Api\V1\Voyages;

use App\Enums\JourEnum;
use App\Models\Trajet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Requête de validation pour la mise à jour d'un trajet.
 */
class UpdateTrajetRequest extends FormRequest
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
        $trajetId = $this->route('trajet');
        $jour = $this->input('jour', Trajet::find($trajetId)?->jour?->value);

        return [
            'jour' => ['sometimes', new Enum(JourEnum::class)],
            'heure_depart' => [
                'sometimes',
                'date_format:H:i',
                Rule::unique('trajets')
                    ->where(fn ($query) => $query->where('jour', $jour))
                    ->ignore($trajetId)
                    ->whereNull('deleted_at'),
            ],
            'duree' => ['sometimes', 'numeric', 'min:1'],
        ];
    }
}
