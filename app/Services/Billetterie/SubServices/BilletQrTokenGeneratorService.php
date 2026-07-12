<?php

namespace App\Services\Billetterie\SubServices;

use Illuminate\Support\Str;

/**
 * Service pour la génération de jetons de sécurité uniques pour les codes QR des billets.
 */
class BilletQrTokenGeneratorService
{
    /**
     * Générer un jeton QR unique pour un billet.
     */
    public function generate(): string
    {
        return 'GOREE_' . Str::random(16) . '_' . dechex(time());
    }
}
