<?php

declare(strict_types=1);

namespace App\Enums;

// Extension du diagramme UML : requise par docs/api.md §5bis.3 pour que le webhook sache router entre achat de billet et recharge de portefeuille.
enum TypeTransactionPayDunyaEnum: string
{
    case ACHAT_BILLET = 'ACHAT_BILLET';
    case RECHARGE_PORTEFEUILLE = 'RECHARGE_PORTEFEUILLE';
    case ABONNEMENT = 'ABONNEMENT';

    public function label(): string
    {
        return match ($this) {
            self::ACHAT_BILLET => 'Achat billet',
            self::RECHARGE_PORTEFEUILLE => 'Recharge portefeuille',
            self::ABONNEMENT => 'Abonnement',
        };
    }
}
