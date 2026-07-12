<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Billet;
use App\Enums\RoleEnum;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Politique de sécurité pour la gestion des billets de transport.
 */
class BilletPolicy
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
     * Déterminer si l'utilisateur peut voir la liste des billets.
     */
    public function viewAny(User $user): bool
    {
        return $this->isAdminOrAgent($user);
    }

    /**
     * Déterminer si l'utilisateur peut voir un billet spécifique.
     */
    public function view(User $user, Billet $billet): bool
    {
        if ($this->isAdminOrAgent($user)) {
            return true;
        }
        
        // Vérifie si le billet appartient à l'utilisateur connecté
        return $user->id === $billet->user_id;
    }

    /**
     * Déterminer si l'utilisateur peut créer un billet.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Déterminer si l'utilisateur peut modifier un billet.
     */
    public function update(User $user, Billet $billet): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Déterminer si l'utilisateur peut supprimer un billet.
     */
    public function delete(User $user, Billet $billet): bool
    {
        return $this->isAdmin($user);
    }
}