<?php

namespace Database\Seeders;

use App\Enums\CategorieEnum;
use App\Enums\JourEnum;
use App\Enums\RoleEnum;
use App\Enums\StatutChaloupeEnum;
use App\Jobs\GenererVoyagesSemaineJob;
use App\Models\Chaloupe;
use App\Models\Plan;
use App\Models\Portefeuille;
use App\Models\Role;
use App\Models\Tarif;
use App\Models\Trajet;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Amorçage minimal permettant de dérouler les workflows du guide :
 * rôles, un admin, un client de démo, la grille tarifaire et un voyage.
 *
 * Idempotent (firstOrCreate) : peut être relancé sans dupliquer.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Rôles
        $admin = Role::firstOrCreate(['nom' => RoleEnum::ADMIN->value]);
        Role::firstOrCreate(['nom' => RoleEnum::AGENT->value]);
        $client = Role::firstOrCreate(['nom' => RoleEnum::CLIENT->value]);

        // 2) Administrateur de démo (mot de passe : Admin@1234)
        User::firstOrCreate(
            ['email' => 'admin@goree.sn'],
            [
                'prenom' => 'Admin',
                'nom' => 'Gorée',
                'mot_de_passe' => Hash::make('Admin@1234'),
                'password_reset_at' => now(),
                'active' => true,
                'role_id' => $admin->id,
            ]
        );

        // 3) Client de démo (mot de passe : Client@1234) + portefeuille crédité
        $demoClient = User::firstOrCreate(
            ['email' => 'client@goree.sn'],
            [
                'prenom' => 'Samba',
                'nom' => 'Diallo',
                'mot_de_passe' => Hash::make('Client@1234'),
                'password_reset_at' => now(),
                'active' => true,
                'role_id' => $client->id,
            ]
        );
        Portefeuille::firstOrCreate(['user_id' => $demoClient->id], ['solde' => 10000]);

        // 4) Grille tarifaire
        $tarifs = [
            [CategorieEnum::ENFANT, 500],
            [CategorieEnum::ADULTE, 1500],
            [CategorieEnum::RESIDENT, 500],
            [CategorieEnum::ETRANGER, 2500],
        ];
        foreach ($tarifs as [$categorie, $prix]) {
            Tarif::firstOrCreate(['categorie' => $categorie->value], ['prix' => $prix]);
        }

        // 4-bis) Plans d'abonnement (durée + prix)
        $plans = [
            ['Abonnement 1 mois', 1, 5000],
            ['Abonnement 6 mois', 6, 27000],
            ['Abonnement 12 mois', 12, 50000],
        ];
        foreach ($plans as [$nom, $duree, $prix]) {
            Plan::firstOrCreate(['nom' => $nom], ['duree_mois' => $duree, 'prix' => $prix, 'actif' => true]);
        }

        // 5) Les deux chaloupes de Gorée : Beer & Coumba Castel
        Chaloupe::firstOrCreate(
            ['imatriculation' => 'IM-BEER-001'],
            ['nom' => 'Beer', 'capacite' => 150, 'statut' => StatutChaloupeEnum::ACTIVE->value]
        );
        Chaloupe::firstOrCreate(
            ['imatriculation' => 'IM-COUMBA-001'],
            ['nom' => 'Coumba Castel', 'capacite' => 200, 'statut' => StatutChaloupeEnum::ACTIVE->value]
        );

        // 6) Trajets récurrents (tous les jours, deux départs) pour alimenter le cron.
        $departs = ['07:30', '16:00'];
        foreach (JourEnum::cases() as $jour) {
            foreach ($departs as $heure) {
                Trajet::firstOrCreate(
                    ['jour' => $jour->value, 'heure_depart' => $heure],
                    ['duree' => 20]
                );
            }
        }

        // 7) Génération des voyages des 7 prochains jours (round-robin des chaloupes),
        //    exactement comme le fait le cron quotidien.
        (new GenererVoyagesSemaineJob)->handle();
    }
}
