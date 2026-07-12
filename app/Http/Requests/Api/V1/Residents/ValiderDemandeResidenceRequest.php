<?php

namespace App\Http\Requests\Api\V1\Residents;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Requête de validation pour approuver ou rejeter une demande de résidence.
 */
class ValiderDemandeResidenceRequest extends FormRequest
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
            'motif_refus' => ['required_if:action,refuser', 'string', 'max:500'],
        ];
    }
}
