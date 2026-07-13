<?php

namespace App\Http\Requests\Api\V1\Residents;

use App\Enums\ModePayementEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Requête de souscription d'un abonnement par un résident.
 */
class SouscrireAbonnementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'uuid', 'exists:plans,id'],
            'payment_mode' => ['required', new Enum(ModePayementEnum::class)],
        ];
    }
}
