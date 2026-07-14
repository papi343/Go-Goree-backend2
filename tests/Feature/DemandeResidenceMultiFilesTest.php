<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemandeResidenceMultiFilesTest extends TestCase
{
    use RefreshDatabase;

    protected User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $clientRole = Role::firstOrCreate(['nom' => 'Client']);
        $this->client = User::factory()->create([
            'role_id' => $clientRole->id,
        ]);
    }

    public function test_user_can_submit_demande_with_uploaded_files()
    {
        Storage::fake('public');

        $photoFile = UploadedFile::fake()->image('profile.jpg');
        $cniRectoFile = UploadedFile::fake()->create('cni_front.pdf', 500, 'application/pdf');
        $cniVersoFile = UploadedFile::fake()->create('cni_back.png', 500, 'image/png');
        $residenceFile = UploadedFile::fake()->create('certificat.pdf', 800, 'application/pdf');

        $response = $this->actingAs($this->client)
            ->postJson('/api/v1/demandes-residence', [
                'carte_identite' => 'CNI1234567890',
                'residence' => '12 Rue Saint Germain, Gorée',
                'photo_file' => $photoFile,
                'cni_recto_file' => $cniRectoFile,
                'cni_verso_file' => $cniVersoFile,
                'certificat_residence_file' => $residenceFile,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'demande' => [
                    'id',
                    'nom',
                    'carte_identite',
                    'residence',
                    'statut',
                    'photo',
                    'cni_recto',
                    'cni_verso',
                    'certificat_residence',
                    'docs',
                ],
            ]);

        // Assert files exist on fake storage
        $demande = $response->json('demande');
        Storage::disk('public')->assertExists($demande['photo']);
        Storage::disk('public')->assertExists($demande['cni_recto']);
        Storage::disk('public')->assertExists($demande['cni_verso']);
        Storage::disk('public')->assertExists($demande['certificat_residence']);

        // Assert the array representation docs has the basename of the files
        $this->assertCount(3, $demande['docs']);
        $this->assertEquals(basename($demande['cni_recto']), $demande['docs'][0]);
    }
}
