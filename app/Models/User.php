<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'telephone',
        'mot_de_passe',
        'password_reset_at',
        'active',
        'est_resident',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'mot_de_passe',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'est_resident' => 'boolean',
            'password_reset_at' => 'datetime',
        ];
    }

    /**
     * Allow Laravel's built-in Auth::attempt()/Sanctum password checks to
     * work transparently against the `mot_de_passe` column instead of the
     * framework's default `password` column.
     */
    public function getAuthPassword(): string
    {
        return $this->mot_de_passe;
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function resident()
    {
        return $this->hasOne(Resident::class);
    }

    public function demandesResidence()
    {
        return $this->hasMany(DemandeResidence::class);
    }

    public function demandesValidees()
    {
        return $this->hasMany(DemandeResidence::class, 'valide_par');
    }

    public function payements()
    {
        return $this->hasMany(Payement::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function portefeuille()
    {
        return $this->hasOne(Portefeuille::class);
    }

    public function billets()
    {
        return $this->hasMany(Billet::class);
    }

    public function alertesFraudeTraitees()
    {
        return $this->hasMany(AlerteFraude::class, 'traite_par');
    }
}
