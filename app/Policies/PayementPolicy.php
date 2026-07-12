<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Payement;
use App\Enums\RoleEnum;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Politique de sécurité pour la gestion des paiements.
 */
class PayementPolicy
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
     * Déterminer si l'utilisateur peut voir la liste des paiements.
     */
    public function viewAny(User $user): bool
    {
        return $this->isAdminOrAgent($user);
    }

    /**
     * Déterminer si l'utilisateur peut voir un paiement spécifique.
     */
    public function view(User $user, Payement $payement): bool
    {
        if ($this->isAdminOrAgent($user)) {
            return true;
        }
        
        // Vérifie si le paiement appartient à l'utilisateur connecté
        return $user->id === $payement->user_id;
    }

    /**
     * Déterminer si l'utilisateur peut créer un enregistrement de paiement.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Déterminer si l'utilisateur peut modifier un paiement.
     */
    public function update(User $user, Payement $payement): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Déterminer si l'utilisateur peut supprimer un paiement.
     */
    public function delete(User $user, Payement $payement): bool
    {
        return $this->isAdmin($user);
    }
}