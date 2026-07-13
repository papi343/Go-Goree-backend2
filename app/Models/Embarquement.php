<?php

namespace App\Models;

use App\Enums\StatutEmbarquementEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Session d'embarquement ouverte par un contrôleur pour un voyage donné.
 */
class Embarquement extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'statut',
        'ouvert_a',
        'ferme_a',
        'voyage_id',
        'ouvert_par',
    ];

    protected function casts(): array
    {
        return [
            'statut' => StatutEmbarquementEnum::class,
            'ouvert_a' => 'datetime',
            'ferme_a' => 'datetime',
        ];
    }

    public function voyage()
    {
        return $this->belongsTo(Voyage::class);
    }

    public function scans()
    {
        return $this->hasMany(Scan::class);
    }

    public function estOuvert(): bool
    {
        return $this->statut === StatutEmbarquementEnum::OUVERT;
    }
}
