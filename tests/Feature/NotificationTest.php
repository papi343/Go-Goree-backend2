<?php

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('un utilisateur ne voit que ses propres notifications', function () {
    $user = User::factory()->create();
    Notification::factory()->count(2)->create(['user_id' => $user->id]);
    Notification::factory()->create(); // celle d'un autre

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/notifications')->assertOk();
    expect($response->json('total'))->toBe(2);
});

test('un utilisateur marque sa notification comme lue', function () {
    $user = User::factory()->create();
    $notif = Notification::factory()->create(['user_id' => $user->id]);

    Sanctum::actingAs($user);

    $this->putJson("/api/v1/notifications/{$notif->id}")->assertOk();

    expect($notif->fresh()->lu_a)->not->toBeNull();
});

test('un utilisateur supprime sa notification', function () {
    $user = User::factory()->create();
    $notif = Notification::factory()->create(['user_id' => $user->id]);

    Sanctum::actingAs($user);

    $this->deleteJson("/api/v1/notifications/{$notif->id}")->assertNoContent();
    $this->assertSoftDeleted('notifications', ['id' => $notif->id]);
});

test('les notifications sont protégées par authentification', function () {
    $this->getJson('/api/v1/notifications')->assertUnauthorized();
});

test('FAILLE connue (IDOR) : un utilisateur peut accéder à la notification d\'un autre', function () {
    // Le contrôleur ne vérifie pas la propriété dans show/update/destroy.
    // Ce test documente la faille signalée (S5). Il devra être inversé
    // (assertForbidden) une fois le contrôle de propriété ajouté.
    $autre = Notification::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/v1/notifications/{$autre->id}")
        ->assertOk()
        ->assertJsonPath('id', $autre->id);
});
