<?php

namespace App\Services\Notifications;

use App\Enums\CanalEnum;
use App\Enums\NotificationEnum;
use App\Events\NotificationCreee;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Service pour la distribution et l'envoi de notifications aux utilisateurs (SMS, Email, Push In-App).
 */
class NotificationDispatchService
{
    /**
     * Distribuer une notification à un utilisateur.
     */
    public function dispatch(User $user, NotificationEnum $type, CanalEnum $canal, string $message): Notification
    {
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'canal' => $canal,
            'lu_a' => null,
        ]);

        switch ($canal) {
            case CanalEnum::SMS:
                $this->sendSms($user->telephone, $message);
                break;
            case CanalEnum::MAIL:
                $this->sendMail($user->email, $message);
                break;
            case CanalEnum::IN_APP:
                $this->sendPushNotification($notification, $message);
                break;
        }

        return $notification;
    }

    /**
     * Envoyer un SMS (simulation via logs).
     */
    protected function sendSms(?string $phone, string $message): void
    {
        if ($phone) {
            Log::info("Envoi SMS à {$phone} : {$message}");
        }
    }

    /**
     * Envoyer un e-mail (simulation via logs).
     */
    protected function sendMail(?string $email, string $message): void
    {
        if ($email) {
            Log::info("Envoi E-mail à {$email} : {$message}");
        }
    }

    /**
     * Diffuser la notification in-app en temps réel (Reverb) sur le canal privé
     * du destinataire, en plus de la persistance en base.
     */
    protected function sendPushNotification(Notification $notification, string $message): void
    {
        event(new NotificationCreee($notification, $message));

        Log::info("Notification temps réel diffusée à l'utilisateur {$notification->user_id} : {$message}");
    }
}
