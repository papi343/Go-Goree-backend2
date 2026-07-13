<?php

declare(strict_types=1);

namespace App\Enums;

enum StatutEmbarquementEnum: string
{
    case OUVERT = 'OUVERT';
    case FERME = 'FERME';

    public function label(): string
    {
        return match ($this) {
            self::OUVERT => 'Ouvert',
            self::FERME => 'Fermé',
        };
    }
}
