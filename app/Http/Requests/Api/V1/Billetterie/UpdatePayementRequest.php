<?php

namespace App\Http\Requests\Api\V1\Billetterie;

use App\Enums\ModePayementEnum;
use App\Enums\StatutPayementEnum;
use App\Enums\TypeTransactionPayDunyaEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Requête de validation pour la mise à jour d'un paiement (admin).
 */
class UpdatePayementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference' => ['sometimes', 'string', 'max:255'],
            'montant' => ['sometimes', 'numeric', 'min:0'],
            'statut' => ['sometimes', new Enum(StatutPayementEnum::class)],
            'mode' => ['sometimes', new Enum(ModePayementEnum::class)],
            'type_transaction' => ['nullable', new Enum(TypeTransactionPayDunyaEnum::class)],
            'paydunya_token' => ['nullable', 'string', 'max:255'],
            'billet_id' => ['nullable', 'uuid', 'exists:billets,id'],
            'plan_id' => ['nullable', 'uuid', 'exists:plans,id'],
            'user_id' => ['sometimes', 'uuid', 'exists:users,id'],
        ];
    }
}
