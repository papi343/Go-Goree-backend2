<?php

namespace App\Http\Requests\Api\V1\Billetterie;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\ModePayementEnum;
use App\Enums\CategorieEnum;

/**
 * Requête de validation pour la création/l'achat d'un billet.
 */
class StoreBilletRequest extends FormRequest
{
    /**
     * Déterminer si l'utilisateur est autorisé à effectuer cette requête.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtenir les règles de validation qui s'appliquent à la requête.
     */
    public function rules(): array
    {
        return [
            'voyage_id' => ['required', 'uuid', 'exists:voyages,id'],
            'payment_mode' => ['required', new Enum(ModePayementEnum::class)],
            'categorie' => ['nullable', new Enum(CategorieEnum::class)],
        ];
    }
}
