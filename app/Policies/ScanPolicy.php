<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Scan;
use App\Enums\RoleEnum;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Politique de sécurité pour la gestion des scans de billets (embarquement).
 */
class ScanPolicy
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
     * Déterminer si l'utilisateur peut voir la liste des scans.
     */
    public function viewAny(User $user): bool
    {
        return $this->isAdminOrAgent($user);
    }

    /**
     * Déterminer si l'utilisateur peut voir un scan spécifique.
     */
    public function view(User $user, Scan $scan): bool
    {
        if ($this->isAdminOrAgent($user)) {
            return true;
        }
        
        return $scan->billet && $user->id === $scan->billet->user_id;
    }

    /**
     * Déterminer si l'utilisateur peut enregistrer un scan (embarquement).
     */
    public function create(User $user): bool
    {
        return $this->isAdminOrAgent($user);
    }

    /**
     * Déterminer si l'utilisateur peut modifier un scan.
     */
    public function update(User $user, Scan $scan): bool
    {
        return $user->role && $user->role->nom === RoleEnum::ADMIN;
    }

    /**
     * Déterminer si l'utilisateur peut supprimer un scan.
     */
    public function delete(User $user, Scan $scan): bool
    {
        return $user->role && $user->role->nom === RoleEnum::ADMIN;
    }
}