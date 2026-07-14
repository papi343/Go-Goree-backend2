<?php

namespace App\Http\Controllers\Api\V1\Notifications;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Events\NotificationCreee;
use App\Mail\CampaignMail;
use App\Enums\NotificationEnum;
use App\Enums\CanalEnum;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Contrôleur pour gérer les notifications in-app des utilisateurs.
 */
class NotificationController extends Controller
{
    /**
     * Liste des notifications de l'utilisateur connecté.
     */
    public function index(Request $request)
    {
        return response()->json(Notification::where('user_id', $request->user()->id)->paginate());
    }

    /**
     * Afficher les détails d'une notification spécifique (propriétaire uniquement).
     */
    public function show(Request $request, $id)
    {
        $notif = $this->notificationDeLUtilisateur($request, $id);

        return response()->json($notif);
    }

    /**
     * Marquer une notification comme lue (propriétaire uniquement).
     */
    public function update(Request $request, $id)
    {
        $notif = $this->notificationDeLUtilisateur($request, $id);
        $notif->update(['lu_a' => now()]);

        return response()->json($notif);
    }

    /**
     * Supprimer une notification (propriétaire uniquement).
     */
    public function destroy(Request $request, $id)
    {
        $notif = $this->notificationDeLUtilisateur($request, $id);
        $notif->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Diffuser une campagne de notification globale.
     */
    public function broadcast(Request $request)
    {
        $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'canaux' => ['required', 'array'],
            'canaux.*' => ['string', 'in:SMS,Email,Push,In-App'],
            'destinataires' => ['required', 'string'],
        ]);

        $destQuery = User::query();

        switch ($request->destinataires) {
            case 'Résidents uniquement':
                $destQuery->where('est_resident', true);
                break;
            case 'Touristes uniquement':
                $destQuery->where('est_resident', false)->whereHas('role', fn($q) => $q->where('nom', 'Client'));
                break;
            case 'Scolaires uniquement':
                $destQuery->whereHas('role', fn($q) => $q->where('nom', 'Client'));
                break;
            case 'Tous les passagers':
            default:
                $destQuery->whereHas('role', fn($q) => $q->where('nom', 'Client'));
                break;
        }

        $users = $destQuery->get();

        foreach ($users as $user) {
            // 1. Create In-App Notification (majority of notifications)
            if (in_array('In-App', $request->canaux, true)) {
                $notification = Notification::create([
                    'type' => NotificationEnum::ALERTE,
                    'canal' => CanalEnum::IN_APP,
                    'lu_a' => null,
                    'user_id' => $user->id,
                ]);

                // Emit event to Reverb
                event(new NotificationCreee($notification, $request->message));
            }

            // 2. Send email
            if (in_array('Email', $request->canaux, true) && $user->email) {
                Mail::to($user->email)->queue(new CampaignMail($request->titre, $request->message));
            }

            // 3. SMS/Push simulation logging
            if (in_array('SMS', $request->canaux, true) && $user->telephone) {
                \Illuminate\Support\Facades\Log::info("Simulated SMS to {$user->telephone}: {$request->message}");
            }
            if (in_array('Push', $request->canaux, true)) {
                \Illuminate\Support\Facades\Log::info("Simulated Push alert to user {$user->id}: {$request->message}");
            }
        }

        return response()->json([
            'message' => 'Campagne de notification diffusée avec succès.',
            'destinataires_contactes' => $users->count(),
        ], Response::HTTP_CREATED);
    }

    /**
     * Récupère la notification en s'assurant qu'elle appartient à l'utilisateur
     * connecté (404 si elle n'existe pas ou n'est pas la sienne).
     */
    private function notificationDeLUtilisateur(Request $request, string $id): Notification
    {
        return Notification::where('user_id', $request->user()->id)->findOrFail($id);
    }
}
