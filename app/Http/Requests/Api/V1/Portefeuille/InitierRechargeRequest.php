<?php

namespace App\Http\Requests\Api\V1\Portefeuille;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Requête de validation pour initier la recharge d'un portefeuille.
 */
class InitierRechargeRequest extends FormRequest
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
            'montant' => ['required', 'numeric', 'min:100'],
        ];
    }
}
