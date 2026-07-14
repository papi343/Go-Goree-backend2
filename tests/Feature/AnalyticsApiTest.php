<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsApiTest extends TestCase
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

    public function test_admin_can_access_dashboard_analytics()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/analytics/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'overview' => [
                    'total_sales_ytd',
                    'total_tickets_ytd',
                    'record_month',
                    'average_sales_per_month',
                    'average_tickets_per_month',
                    'tendance_percentage',
                ],
                'monthly_data',
                'weekly_distribution',
                'visitor_categories',
                'hourly_boardings',
                'chaloupes_occupations',
                'daily_historique',
            ]);
    }

    public function test_admin_can_access_transactions_analytics()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/analytics/transactions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'payment_methods',
                'wallet_overview' => [
                    'soldeGlobal',
                    'walletsActifs',
                    'rechargementsMois',
                ],
            ]);
    }

    public function test_client_cannot_access_analytics()
    {
        $response1 = $this->actingAs($this->client)
            ->getJson('/api/v1/analytics/dashboard');

        $response1->assertStatus(403);

        $response2 = $this->actingAs($this->client)
            ->getJson('/api/v1/analytics/transactions');

        $response2->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_analytics()
    {
        $response = $this->getJson('/api/v1/analytics/dashboard');
        $response->assertStatus(401);
    }
}
