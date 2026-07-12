<?php

namespace App\Services\Residents\SubServices;

use App\Models\User;
use App\Models\Resident;

/**
 * Service pour activer le profil et le statut de résident pour un compte utilisateur.
 */
class ResidentActivationService
{
    /**
     * Activer le statut de résident pour un utilisateur.
     */
    public function activate(User $user): Resident
    {
        $user->update(['est_resident' => true]);

        return Resident::firstOrCreate(
            ['user_id' => $user->id],
            ['active' => true]
        );
    }
}
