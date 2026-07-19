<?php

namespace App\Http\Requests\Api\V1\Billetterie;

use App\Enums\ModePayementEnum;
use App\Enums\StatutPayementEnum;
use App\Enums\TypeTransactionPayDunyaEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Requête de validation pour la création manuelle d'un paiement (admin).
 */
class StorePayementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'max:255'],
            'montant' => ['required', 'numeric', 'min:0'],
            'statut' => ['required', new Enum(StatutPayementEnum::class)],
            'mode' => ['required', new Enum(ModePayementEnum::class)],
            'type_transaction' => ['nullable', new Enum(TypeTransactionPayDunyaEnum::class)],
            'paydunya_token' => ['nullable', 'string', 'max:255'],
            'billet_id' => ['nullable', 'uuid', 'exists:billets,id'],
            'plan_id' => ['nullable', 'uuid', 'exists:plans,id'],
            'user_id' => ['required', 'uuid', 'exists:users,id'],
        ];
    }
}
