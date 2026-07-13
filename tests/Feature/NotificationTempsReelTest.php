<?php

use App\Enums\CanalEnum;
use App\Enums\NotificationEnum;
use App\Events\NotificationCreee;
use App\Mail\NouvelleDemandeResidenceMail;
use App\Models\Notification;
use App\Models\User;
use App\Services\Notifications\NotificationDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('NotificationCreee diffuse sur le canal privé du destinataire', function () {
    $notif = Notification::factory()->create();

    $event = new NotificationCreee($notif, 'Bonjour');

    expect($event->broadcastOn()[0]->name)->toBe('private-App.Models.User.'.$notif->user_id);
    expect($event->broadcastAs())->toBe('notification.creee');
    expect($event->broadcastWith()['message'])->toBe('Bonjour');
});

test('une notification in-app est diffusée en temps réel', function () {
    Event::fake([NotificationCreee::class]);
    $user = User::factory()->create();

    app(NotificationDispatchService::class)->dispatch(
        $user,
        NotificationEnum::PAYEMENT,
        CanalEnum::IN_APP,
        'Paiement reçu'
    );

    // Persistée en base…
    $this->assertDatabaseHas('notifications', ['user_id' => $user->id]);
    // …et diffusée en temps réel.
    Event::assertDispatched(NotificationCreee::class, fn ($e) => $e->message === 'Paiement reçu'
        && $e->notification->user_id === $user->id);
});

test('une nouvelle demande notifie les admins en temps réel ET par mail détaillé', function () {
    Event::fake([NotificationCreee::class]);
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $client = User::factory()->client()->create();
    Sanctum::actingAs($client);

    $this->postJson('/api/v1/demandes-residence', [
        'carte_identite' => 'CNI123456789',
        'residence' => 'Gorée Centre',
        'photo' => 'photo.png',
    ])->assertCreated();

    // Notif in-app persistée + diffusée + email détaillé à l'admin.
    $this->assertDatabaseHas('notifications', ['user_id' => $admin->id]);
    Event::assertDispatched(NotificationCreee::class);
    Mail::assertQueued(NouvelleDemandeResidenceMail::class, fn ($m) => $m->hasTo($admin->email));
});
