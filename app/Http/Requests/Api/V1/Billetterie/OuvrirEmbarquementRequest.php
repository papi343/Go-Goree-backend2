<?php

namespace App\Http\Requests\Api\V1\Billetterie;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Requête d'ouverture d'une session d'embarquement pour un voyage.
 */
class OuvrirEmbarquementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'voyage_id' => ['required', 'uuid', 'exists:voyages,id'],
        ];
    }
}
