<?php

namespace App\Http\Requests\Api\V1\Users;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Requête de création d'un compte contrôleur (agent) par un administrateur.
 * Seul un administrateur est autorisé.
 */
class StoreControleurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()
            && $this->user()->role
            && $this->user()->role->nom === RoleEnum::ADMIN;
    }

    public function rules(): array
    {
        return [
            'prenom' => ['required', 'string', 'max:255'],
            'nom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'telephone' => ['nullable', 'string', 'max:50'],
        ];
    }
}
