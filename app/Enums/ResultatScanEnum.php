<?php

declare(strict_types=1);

namespace App\Enums;

enum ResultatScanEnum: string
{
    case VALIDE = 'VALIDE';
    case DEJA_SCANNE = 'DEJA_SCANNE';
    case EXPIRE = 'EXPIRE';
    case NON_EMBARQUE = 'NON_EMBARQUE';
    case MAUVAIS_VOYAGE = 'MAUVAIS_VOYAGE';

    public function label(): string
    {
        return match ($this) {
            self::VALIDE => 'Valide',
            self::DEJA_SCANNE => 'Déjà scanné',
            self::EXPIRE => 'Expiré',
            self::NON_EMBARQUE => 'Non embarqué',
            self::MAUVAIS_VOYAGE => 'Mauvais voyage',
        };
    }
}
