<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Diffusée en temps réel (Reverb) sur le canal privé de l'utilisateur destinataire.
 * Le front (React + Laravel Echo) s'abonne à `App.Models.User.{id}` et écoute
 * l'événement `notification.creee`.
 */
class NotificationCreee implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Notification $notification,
        public string $message,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('App.Models.User.'.$this->notification->user_id)];
    }

    public function broadcastAs(): string
    {
        return 'notification.creee';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'type' => $this->notification->type?->value,
            'canal' => $this->notification->canal?->value,
            'message' => $this->message,
            'lu_a' => $this->notification->lu_a,
            'created_at' => $this->notification->created_at?->toIso8601String(),
        ];
    }
}
