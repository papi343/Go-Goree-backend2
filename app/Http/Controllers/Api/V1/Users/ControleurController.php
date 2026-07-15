<?php

namespace App\Http\Controllers\Api\V1\Users;

use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Users\StoreControleurRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Mail\ReinitialisationMotDePasseMail;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\PasswordResetService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Création de comptes contrôleurs (agents) par un administrateur.
 *
 * Le compte est créé SANS mot de passe utilisable (password_reset_at = null) ;
 * le contrôleur reçoit un email l'invitant à définir son mot de passe via un
 * jeton à usage unique.
 */
class ControleurController extends Controller
{
    public function __construct(protected PasswordResetService $passwordReset) {}

    /**
     * Liste des contrôleurs (comptes de rôle Agent).
     */
    public function index()
    {
        $agents = User::with('role')
            ->whereHas('role', fn ($q) => $q->where('nom', RoleEnum::AGENT->value))
            ->paginate();

        return UserResource::collection($agents);
    }

    /**
     * Créer un compte contrôleur et lui envoyer l'email d'activation.
     */
    public function store(StoreControleurRequest $request)
    {
        $roleAgent = Role::firstOrCreate(['nom' => RoleEnum::AGENT->value]);

        $user = DB::transaction(function () use ($request, $roleAgent) {
            return User::create([
                'prenom' => $request->prenom,
                'nom' => $request->nom,
                'email' => $request->email,
                'telephone' => $request->telephone,
                // Mot de passe aléatoire inutilisable tant que le contrôleur
                // n'a pas défini le sien via le lien d'activation.
                'mot_de_passe' => Hash::make(Str::random(40)),
                'password_reset_at' => null,
                'active' => true,
                'role_id' => $roleAgent->id,
            ]);
        });

        $token = $this->passwordReset->genererToken($user);

        // Mis en file d'attente : l'envoi SMTP ne bloque pas la réponse.
        Mail::to($user->email)->queue(
            new ReinitialisationMotDePasseMail($user, $token, invitation: true)
        );

        return (new UserResource($user->load('role')))
            ->additional(['message' => "Compte contrôleur créé. Un email d'activation a été envoyé."])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Renvoyer l'e-mail d'activation/invitation à un contrôleur.
     */
    public function resendInvitation(string $id)
    {
        $user = User::with('role')
            ->whereHas('role', fn ($q) => $q->where('nom', RoleEnum::AGENT->value))
            ->findOrFail($id);

        if ($user->password_reset_at !== null) {
            return response()->json([
                'message' => 'Ce compte est déjà activé.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $token = $this->passwordReset->genererToken($user);

        Mail::to($user->email)->queue(
            new ReinitialisationMotDePasseMail($user, $token, invitation: true)
        );

        return response()->json([
            'message' => "L'email d'activation a été renvoyé avec succès.",
        ]);
    }
}
