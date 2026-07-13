<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Plan d'abonnement : une durée (en mois) et un prix.
 */
class Plan extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'nom',
        'duree_mois',
        'prix',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'duree_mois' => 'integer',
            'prix' => 'decimal:2',
            'actif' => 'boolean',
        ];
    }

    public function abonnements()
    {
        return $this->hasMany(Abonnement::class);
    }
}
