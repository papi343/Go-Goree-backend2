<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rapport extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'nom_fichier',
        'format',
        'date_generation',
        'genere_par',
        'chemin_stockage',
    ];

    protected function casts(): array
    {
        return [
            'date_generation' => 'datetime',
        ];
    }
}
