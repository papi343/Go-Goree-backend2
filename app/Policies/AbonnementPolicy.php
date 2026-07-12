<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Abonnement;
use App\Enums\RoleEnum;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Politique de sécurité pour la gestion des abonnements.
 */
class AbonnementPolicy
{
    use HandlesAuthorization;

    /**
     * Vérifier si l'utilisateur est un administrateur ou un agent.
     */
    protected function isAdminOrAgent(User $user): bool
    {
        return $user->role && in_array($user->role->nom, [RoleEnum::ADMIN, RoleEnum::AGENT], true);
    }

    /**
     * Vérifier si l'utilisateur est un administrateur.
     */
    protected function isAdmin(User $user): bool
    {
        return $user->role && $user->role->nom === RoleEnum::ADMIN;
    }

    /**
     * Déterminer si l'utilisateur peut voir la liste des abonnements.
     */
    public function viewAny(User $user): bool
    {
        return $this->isAdminOrAgent($user);
    }

    /**
     * Déterminer si l'utilisateur peut voir un abonnement spécifique.
     */
    public function view(User $user, Abonnement $abonnement): bool
    {
        if ($this->isAdminOrAgent($user)) {
            return true;
        }
        
        return $abonnement->resident && $user->id === $abonnement->resident->user_id;
    }

    /**
     * Déterminer si l'utilisateur peut créer un abonnement.
     */
    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Déterminer si l'utilisateur peut modifier un abonnement.
     */
    public function update(User $user, Abonnement $abonnement): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Déterminer si l'utilisateur peut supprimer un abonnement.
     */
    public function delete(User $user, Abonnement $abonnement): bool
    {
        return $this->isAdmin($user);
    }
}