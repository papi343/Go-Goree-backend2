<?php

declare(strict_types=1);

namespace App\Enums;

enum ModePayementEnum: string
{
    case WAVE = 'WAVE';
    case ORANGE_MONEY = 'ORANGE_MONEY';
    case YAS = 'YAS';
    case CARTE_BANCAIRE = 'CARTE_BANCAIRE';
    case PORTEFEUILLE = 'PORTEFEUILLE';
    case PAYDUNYA = 'PAYDUNYA';

    public function label(): string
    {
        return match ($this) {
            self::WAVE => 'Wave',
            self::ORANGE_MONEY => 'Orange Money',
            self::YAS => 'YAS',
            self::CARTE_BANCAIRE => 'Carte Bancaire',
            self::PORTEFEUILLE => 'Portefeuille',
            self::PAYDUNYA => 'Paydunya',
        };
    }
}
