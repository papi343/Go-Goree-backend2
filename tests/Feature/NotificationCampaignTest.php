<?php

namespace Tests\Feature;

use App\Events\NotificationCreee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationCampaignTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['nom' => 'Admin']);
        $clientRole = Role::firstOrCreate(['nom' => 'Client']);

        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
        ]);

        $this->client = User::factory()->create([
            'role_id' => $clientRole->id,
        ]);
    }

    public function test_admin_can_send_broadcast_campaign()
    {
        Event::fake([NotificationCreee::class]);
        Mail::fake();

        // Create some clients to receive the broadcast
        $clientRole = Role::where('nom', 'Client')->first();
        User::factory()->count(3)->create([
            'role_id' => $clientRole->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/notifications/broadcast', [
                'titre' => 'Test Campaign',
                'message' => 'This is a test broadcast message.',
                'canaux' => ['In-App', 'Email'],
                'destinataires' => 'Tous les passagers',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Campagne de notification diffusée avec succès.',
            ]);

        // Verify that NotificationCreee events were dispatched for clients
        Event::assertDispatched(NotificationCreee::class);
    }

    public function test_client_cannot_send_broadcast_campaign()
    {
        $response = $this->actingAs($this->client)
            ->postJson('/api/v1/notifications/broadcast', [
                'titre' => 'Hacker Campaign',
                'message' => 'Unauthorized.',
                'canaux' => ['In-App'],
                'destinataires' => 'Tous les passagers',
            ]);

        $response->assertStatus(403);
    }
}
