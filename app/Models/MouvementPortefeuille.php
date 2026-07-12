<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MouvementPortefeuille extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'montant',
        'type',
        'payement_id',
        'statut',
        'portefeuille_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => \App\Enums\MouvementPortefeuilleEnum::class,
            'statut' => \App\Enums\StatutMouvementEnum::class,
            'montant' => 'decimal:2',
        ];
    }

    public function portefeuille()
    {
        return $this->belongsTo(Portefeuille::class);
    }

    public function payement()
    {
        return $this->belongsTo(Payement::class);
    }
}
