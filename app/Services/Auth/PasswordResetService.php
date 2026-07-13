<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Gère les jetons de définition / réinitialisation de mot de passe.
 *
 * Le jeton en clair n'est transmis qu'à l'utilisateur (par email) ; seule sa
 * version hachée (SHA-256) est stockée en base (table password_reset_tokens),
 * exactement comme le fait le broker natif de Laravel.
 */
class PasswordResetService
{
    protected string $table = 'password_reset_tokens';

    /**
     * Créer (ou remplacer) un jeton de réinitialisation pour un utilisateur et
     * renvoyer le jeton EN CLAIR (à envoyer par email, jamais persisté tel quel).
     */
    public function genererToken(User $user): string
    {
        $tokenClair = Str::random(64);

        DB::table($this->table)->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => hash('sha256', $tokenClair),
                'created_at' => now(),
            ]
        );

        return $tokenClair;
    }

    /**
     * Définir le mot de passe d'un utilisateur à partir d'un jeton valide.
     *
     * @throws ValidationException si le couple email/jeton est invalide ou expiré.
     */
    public function reinitialiser(string $email, string $tokenClair, string $motDePasse): User
    {
        $ligne = DB::table($this->table)->where('email', $email)->first();

        $tokenInvalide = ! $ligne || ! hash_equals($ligne->token, hash('sha256', $tokenClair));

        if ($tokenInvalide || $this->estExpire($ligne->created_at)) {
            throw ValidationException::withMessages([
                'token' => ['Ce lien de réinitialisation est invalide ou a expiré.'],
            ]);
        }

        $user = User::where('email', $email)->firstOrFail();

        $user->forceFill([
            'mot_de_passe' => Hash::make($motDePasse),
            'password_reset_at' => now(),
        ])->save();

        // Le jeton est à usage unique.
        DB::table($this->table)->where('email', $email)->delete();

        return $user;
    }

    /**
     * Un jeton est expiré au-delà de la fenêtre configurée (auth.passwords.users.expire, minutes).
     */
    protected function estExpire(?string $createdAt): bool
    {
        if ($createdAt === null) {
            return true;
        }

        $minutes = (int) config('auth.passwords.users.expire', 60);

        return Carbon::parse($createdAt)->addMinutes($minutes)->isPast();
    }
}
