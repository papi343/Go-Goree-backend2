<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payement extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'reference',
        'montant',
        'timestamp',
        'statut',
        'mode',
        'type_transaction',
        'paydunya_token',
        'billet_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'statut' => \App\Enums\StatutPayementEnum::class,
            'mode' => \App\Enums\ModePayementEnum::class,
            'type_transaction' => \App\Enums\TypeTransactionPayDunyaEnum::class,
            'montant' => 'decimal:2',
            'timestamp' => 'datetime',
        ];
    }

    public function billet()
    {
        return $this->belongsTo(Billet::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mouvements()
    {
        return $this->hasMany(MouvementPortefeuille::class);
    }

    public function alertesFraude()
    {
        return $this->hasMany(AlerteFraude::class);
    }
}
