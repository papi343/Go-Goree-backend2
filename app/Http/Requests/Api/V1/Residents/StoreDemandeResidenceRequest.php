<?php

namespace App\Http\Requests\Api\V1\Residents;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Requête de validation pour la soumission d'une demande de résidence.
 */
class StoreDemandeResidenceRequest extends FormRequest
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
            'carte_identite' => ['required', 'string'],
            'residence' => ['required', 'string'],
            // photo can be a string path (for seeds/tests) or an uploaded image
            'photo' => ['required_without:photo_file', 'string', 'max:255'],
            'photo_file' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            
            // cni_recto, cni_verso, and certificat_residence can be string paths or files
            'cni_recto' => ['nullable', 'sometimes', 'string', 'max:255'],
            'cni_recto_file' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,pdf,doc,docx', 'max:10240'],
            
            'cni_verso' => ['nullable', 'sometimes', 'string', 'max:255'],
            'cni_verso_file' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,pdf,doc,docx', 'max:10240'],
            
            'certificat_residence' => ['nullable', 'sometimes', 'string', 'max:255'],
            'certificat_residence_file' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,pdf,doc,docx', 'max:10240'],
        ];
    }
}
