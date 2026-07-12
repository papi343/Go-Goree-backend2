<?php

namespace App\Policies;

use App\Models\User;
use App\Models\DemandeResidence;
use App\Enums\RoleEnum;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Politique de sécurité pour la gestion des demandes de résidence.
 */
class DemandeResidencePolicy
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
     * Vérifier si l'utilisateur est un agent.
     */
    protected function isAgent(User $user): bool
    {
        return $user->role && $user->role->nom === RoleEnum::AGENT;
    }

    /**
     * Déterminer si l'utilisateur peut voir la liste des demandes de résidence.
     */
    public function viewAny(User $user): bool
    {
        return $this->isAdminOrAgent($user);
    }

    /**
     * Déterminer si l'utilisateur peut voir une demande de résidence spécifique.
     */
    public function view(User $user, DemandeResidence $demandeResidence): bool
    {
        if ($this->isAdminOrAgent($user)) {
            return true;
        }
        
        // Vérifie si la demande appartient à l'utilisateur connecté
        return $user->id === $demandeResidence->user_id;
    }

    /**
     * Déterminer si l'utilisateur peut créer/soumettre une demande de résidence.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Déterminer si l'utilisateur peut modifier une demande de résidence.
     */
    public function update(User $user, DemandeResidence $demandeResidence): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Déterminer si l'utilisateur peut supprimer une demande de résidence.
     */
    public function delete(User $user, DemandeResidence $demandeResidence): bool
    {
        return $this->isAdmin($user);
    }
}