<?php

namespace App\Models;

use App\Enums\ModePayementEnum;
use App\Enums\StatutPayementEnum;
use App\Enums\TypeTransactionPayDunyaEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payement extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'reference',
        'montant',
        'timestamp',
        'statut',
        'mode',
        'type_transaction',
        'paydunya_token',
        'billet_id',
        'plan_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'statut' => StatutPayementEnum::class,
            'mode' => ModePayementEnum::class,
            'type_transaction' => TypeTransactionPayDunyaEnum::class,
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

    public function plan()
    {
        return $this->belongsTo(Plan::class);
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
