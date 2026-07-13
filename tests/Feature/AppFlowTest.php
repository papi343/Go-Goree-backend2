<?php

use App\Enums\CategorieEnum;
use App\Enums\DemandeResidenceEnum;
use App\Enums\JourEnum;
use App\Enums\ModePayementEnum;
use App\Enums\ResultatScanEnum;
use App\Enums\StatutBilletEnum;
use App\Enums\StatutPayementEnum;
use App\Enums\TypeTransactionPayDunyaEnum;
use App\Events\PaiementAccepte;
use App\Models\Billet;
use App\Models\Chaloupe;
use App\Models\Payement;
use App\Models\Portefeuille;
use App\Models\Resident;
use App\Models\Role;
use App\Models\Tarif;
use App\Models\Trajet;
use App\Models\User;
use App\Models\Voyage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('flux complet de l\'application : authentification, résidence, recharge, achat et scan', function () {
    // -------------------------------------------------------------
    // PRÉPARATION : Création des Rôles et Utilisateurs (Client et Admin)
    // -------------------------------------------------------------
    $clientRole = Role::create(['nom' => 'Client']);
    $adminRole = Role::create(['nom' => 'Admin']);

    $client = User::create([
        'prenom' => 'Samba',
        'nom' => 'Diallo',
        'email' => 'samba.diallo@example.com',
        'mot_de_passe' => Hash::make('passer123'),
        'role_id' => $clientRole->id,
        'active' => true,
    ]);

    $admin = User::create([
        'prenom' => 'Admin',
        'nom' => 'Général',
        'email' => 'admin@goree.sn',
        'mot_de_passe' => Hash::make('admin123'),
        'role_id' => $adminRole->id,
        'active' => true,
    ]);

    // -------------------------------------------------------------
    // ÉTAPE 1 : Connexion (Client)
    // -------------------------------------------------------------
    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => 'samba.diallo@example.com',
        'mot_de_passe' => 'passer123',
    ]);

    $loginResponse->assertStatus(200)
        ->assertJsonStructure(['access_token', 'token_type', 'user']);

    // -------------------------------------------------------------
    // ÉTAPE 2 : Soumission de la demande de résidence (Client)
    // -------------------------------------------------------------
    Sanctum::actingAs($client);

    $demandeResponse = $this->postJson('/api/v1/demandes-residence', [
        'carte_identite' => 'CNI123456789',
        'residence' => 'Gorée Centre',
        'photo' => 'profile_pic.png',
    ]);

    $demandeResponse->assertStatus(201);
    $demandeId = $demandeResponse->json('demande.id');

    // Vérifier l'état de la demande en base
    $this->assertDatabaseHas('demande_residences', [
        'id' => $demandeId,
        'statut' => DemandeResidenceEnum::EN_COURS->value,
    ]);

    // -------------------------------------------------------------
    // ÉTAPE 3 : Validation de la demande par l'Admin
    // -------------------------------------------------------------
    Sanctum::actingAs($admin);

    $validationResponse = $this->postJson("/api/v1/demandes-residence/{$demandeId}/valider");
    $validationResponse->assertStatus(200);

    // Vérifier que la demande est bien acceptée
    $this->assertDatabaseHas('demande_residences', [
        'id' => $demandeId,
        'statut' => DemandeResidenceEnum::ACCEPTEE->value,
    ]);

    // Vérifier que l'écouteur d'événement a bien activé le résident et créé son abonnement de 12 mois
    $resident = Resident::where('user_id', $client->id)->first();
    $this->assertNotNull($resident);
    $this->assertTrue((bool) $resident->active);

    $this->assertDatabaseHas('abonnements', [
        'resident_id' => $resident->id,
    ]);

    // -------------------------------------------------------------
    // ÉTAPE 4 : Recharge du portefeuille (Client)
    // -------------------------------------------------------------
    Sanctum::actingAs($client);

    $rechargeResponse = $this->postJson('/api/v1/portefeuille/recharge', [
        'montant' => 5000,
        'payment_mode' => ModePayementEnum::PAYDUNYA->value,
    ]);

    $rechargeResponse->assertStatus(201);

    // Le jeton PayDunya n'est volontairement PLUS exposé dans la réponse (sécurité).
    // On récupère le paiement en base, puis on simule la confirmation PayDunya.
    $payement = Payement::where('user_id', $client->id)
        ->where('type_transaction', TypeTransactionPayDunyaEnum::RECHARGE_PORTEFEUILLE)
        ->first();
    $this->assertNotNull($payement);
    $payement->update(['statut' => StatutPayementEnum::ACCEPTE]);
    event(new PaiementAccepte($payement));

    // Vérifier que le solde du portefeuille a bien été crédité
    $portefeuille = Portefeuille::where('user_id', $client->id)->first();
    $this->assertNotNull($portefeuille);
    $this->assertEquals(5000.0, (float) $portefeuille->solde);

    // -------------------------------------------------------------
    // ÉTAPE 5 : Achat de Billet Résident (Client)
    // -------------------------------------------------------------
    $client->refresh();
    Sanctum::actingAs($client);

    // Préparer les entités de voyage requises pour l'achat
    $chaloupe = Chaloupe::create([
        'imatriculation' => 'IM_BEER_123',
        'nom' => 'Beer',
        'capacite' => 150,
    ]);
    $trajet = Trajet::create([
        'jour' => JourEnum::LUNDI,
        'heure_depart' => '07:30',
        'duree' => 20,
    ]);
    $voyage = Voyage::create([
        'date_voyage' => now()->toDateString(),
        'places' => 150,
        'places_restantes' => 150,
        'trajet_id' => $trajet->id,
        'chaloupe_id' => $chaloupe->id,
    ]);
    $tarif = Tarif::create([
        'categorie' => CategorieEnum::RESIDENT,
        'prix' => 500.0,
    ]);
    Tarif::create([
        'categorie' => CategorieEnum::ADULTE,
        'prix' => 1500.0,
    ]);

    $achatResponse = $this->postJson('/api/v1/billets', [
        'voyage_id' => $voyage->id,
        'tarif_id' => $tarif->id,
        'payment_mode' => ModePayementEnum::PORTEFEUILLE->value,
    ]);

    $achatResponse->assertStatus(201);
    $billetId = $achatResponse->json('billet.id');
    $qrToken = $achatResponse->json('billet.qr_token');

    // Vérifier le débit du portefeuille et le statut payé du billet
    $this->assertEquals(4500.0, (float) $portefeuille->fresh()->solde);
    $this->assertDatabaseHas('billets', [
        'id' => $billetId,
        'statut' => StatutBilletEnum::PAYE->value,
    ]);

    // -------------------------------------------------------------
    // ÉTAPE 6 : Scan du Billet lors de l'embarquement
    // -------------------------------------------------------------
    Sanctum::actingAs($admin);

    $scanResponse = $this->postJson('/api/v1/scans', [
        'qr_token' => $qrToken,
    ]);

    $scanResponse->assertStatus(200)
        ->assertJsonPath('resultat', ResultatScanEnum::VALIDE->value);

    // Vérifier que le statut du billet est passé à UTILISE
    $this->assertDatabaseHas('billets', [
        'id' => $billetId,
        'statut' => StatutBilletEnum::UTILISE->value,
    ]);
});
