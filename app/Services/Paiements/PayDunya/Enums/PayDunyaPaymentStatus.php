<?php

declare(strict_types=1);

namespace App\Services\Paiements\PayDunya\Enums;

/**
 * Statut normalisé d'un paiement PayDunya, indépendant du vocabulaire exact
 * renvoyé par l'API (completed/success, cancelled/failed, pending...).
 */
enum PayDunyaPaymentStatus: string
{
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case PENDING = 'pending';
    case UNKNOWN = 'unknown';

    /**
     * Convertit une valeur brute renvoyée par PayDunya en statut normalisé.
     */

    
    public static function depuisApi(?string $statut): self
    {
        return match (strtolower(trim((string) $statut))) {
            'completed', 'success', 'succeeded', 'paid' => self::COMPLETED,
            'cancelled', 'canceled', 'failed', 'refused', 'declined' => self::CANCELLED,
            'pending', 'in_progress', 'processing' => self::PENDING,
            default => self::UNKNOWN,
        };
    }

    public function estPaye(): bool
    {
        return $this === self::COMPLETED;
    }

    public function estEchoue(): bool
    {
        return $this === self::CANCELLED;
    }
}
