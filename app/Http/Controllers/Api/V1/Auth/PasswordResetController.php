<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Mail\ReinitialisationMotDePasseMail;
use App\Models\User;
use App\Services\Auth\PasswordResetService;
use Illuminate\Support\Facades\Mail;

/**
 * Flux de définition / réinitialisation de mot de passe par jeton email.
 */
class PasswordResetController extends Controller
{
    public function __construct(protected PasswordResetService $passwordReset) {}

    /**
     * Demander un lien de réinitialisation (mot de passe oublié).
     *
     * Réponse volontairement identique que l'email existe ou non, pour ne pas
     * divulguer la présence d'un compte (anti-énumération).
     */
    public function forgot(ForgotPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if ($user) {
            $token = $this->passwordReset->genererToken($user);
            Mail::to($user->email)->queue(new ReinitialisationMotDePasseMail($user, $token));
        }

        return response()->json([
            'message' => 'Si un compte existe pour cet email, un lien de réinitialisation a été envoyé.',
        ]);
    }

    /**
     * Définir un nouveau mot de passe à partir d'un jeton valide.
     */
    public function reset(ResetPasswordRequest $request)
    {
        $this->passwordReset->reinitialiser(
            $request->email,
            $request->token,
            $request->mot_de_passe,
        );

        return response()->json([
            'message' => 'Mot de passe défini avec succès. Vous pouvez maintenant vous connecter.',
        ]);
    }
}
