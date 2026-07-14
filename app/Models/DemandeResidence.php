<?php

namespace App\Models;

use App\Enums\DemandeResidenceEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DemandeResidence extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'carte_identite',
        'residence',
        'statut',
        'photo',
        'cni_recto',
        'cni_verso',
        'certificat_residence',
        'motif_refus',
        'valide_par',
        'date_validation',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'statut' => DemandeResidenceEnum::class,
            'date_validation' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function validateur()
    {
        return $this->belongsTo(User::class, 'valide_par');
    }
}
