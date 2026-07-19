<?php

namespace App\Http\Requests\Api\V1\Voyages;

use App\Enums\CategorieEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Requête de validation pour la mise à jour d'un tarif.
 */
class UpdateTarifRequest extends FormRequest
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
            'categorie' => [
                'sometimes',
                new Enum(CategorieEnum::class),
                Rule::unique('tarifs', 'categorie')->ignore($this->route('tarif'))->whereNull('deleted_at'),
            ],
            'prix' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
