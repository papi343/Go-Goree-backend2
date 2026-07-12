<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\User;
use App\Enums\CanalEnum;
use App\Enums\NotificationEnum;
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
                $this->sendPushNotification($user->id, $message);
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
     * Envoyer une notification Push in-app (simulation via logs).
     */
    protected function sendPushNotification(string $userId, string $message): void
    {
        Log::info("Envoi Notification Push in-app à l'utilisateur {$userId} : {$message}");
    }
}
