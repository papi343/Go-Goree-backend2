<?php

namespace Database\Seeders;

use App\Enums\CategorieEnum;
use App\Enums\JourEnum;
use App\Enums\RoleEnum;
use App\Enums\StatutChaloupeEnum;
use App\Enums\StatutBilletEnum;
use App\Enums\DemandeResidenceEnum;
use App\Jobs\GenererVoyagesSemaineJob;
use App\Models\Chaloupe;
use App\Models\Plan;
use App\Models\Portefeuille;
use App\Models\Role;
use App\Models\Tarif;
use App\Models\Trajet;
use App\Models\User;
use App\Models\DemandeResidence;
use App\Models\Billet;
use App\Models\Payement;
use App\Models\Scan;
use App\Models\Voyage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Rôles
        $adminRole = Role::firstOrCreate(['nom' => RoleEnum::ADMIN->value]);
        $agentRole = Role::firstOrCreate(['nom' => RoleEnum::AGENT->value]);
        $clientRole = Role::firstOrCreate(['nom' => RoleEnum::CLIENT->value]);

        // 2) Administrateur (admin@goree.sn / passer123)
        User::updateOrCreate(
            ['email' => 'admin@goree.sn'],
            [
                'prenom' => 'Admin',
                'nom' => 'Gorée',
                'mot_de_passe' => Hash::make('passer123'),
                'password_reset_at' => now(),
                'active' => true,
                'role_id' => $adminRole->id,
            ]
        );

        // 3) Contrôleurs (Oumar Fall, Mariama Diop, Aliou Ndong)
        $controleursData = [
            ['Oumar', 'Fall', 'oumar.fall@goree.sn'],
            ['Mariama', 'Diop', 'mariama.diop@goree.sn'],
            ['Aliou', 'Ndong', 'aliou.ndong@goree.sn'],
        ];
        $agents = [];
        foreach ($controleursData as [$prenom, $nom, $email]) {
            $agents[] = User::firstOrCreate(
                ['email' => $email],
                [
                    'prenom' => $prenom,
                    'nom' => $nom,
                    'telephone' => '77' . rand(1000000, 9999999),
                    'mot_de_passe' => Hash::make('Agent@1234'),
                    'password_reset_at' => now(),
                    'active' => true,
                    'role_id' => $agentRole->id,
                ]
            );
        }

        // 4) Clients de démo avec portefeuilles garnis
        $clientsData = [
            ['Samba', 'Diallo', 'client@goree.sn', 12500, true],
            ['Fatou', 'Diallo', 'fatou.diallo@goree.sn', 8000, false],
            ['Ibrahima', 'Ba', 'ibrahima.ba@goree.sn', 35000, true],
            ['Mariama', 'Fall', 'mariama.fall@goree.sn', 1500, false],
            ['Cheikh', 'Seydi', 'cheikh.seydi@goree.sn', 9200, false],
            ['Amina', 'Sow', 'amina.sow@goree.sn', 24000, true],
            ['Moustapha', 'Ndiaye', 'moustapha.ndiaye@goree.sn', 15000, false],
            ['Penda', 'Boye', 'penda.boye@goree.sn', 600, false],
        ];

        $clients = [];
        foreach ($clientsData as [$prenom, $nom, $email, $solde, $estResident]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'prenom' => $prenom,
                    'nom' => $nom,
                    'telephone' => '77' . rand(1000000, 9999999),
                    'mot_de_passe' => Hash::make('Client@1234'),
                    'password_reset_at' => now(),
                    'active' => true,
                    'est_resident' => $estResident,
                    'role_id' => $clientRole->id,
                ]
            );
            $clients[] = $user;

            Portefeuille::updateOrCreate(
                ['user_id' => $user->id],
                ['solde' => $solde]
            );
        }

        // 5) Grille tarifaire
        $tarifs = [
            CategorieEnum::ENFANT->value => 500,
            CategorieEnum::ADULTE->value => 1500,
            CategorieEnum::RESIDENT->value => 500,
            CategorieEnum::ETRANGER->value => 2500,
        ];
        $tarifModels = [];
        foreach ($tarifs as $categorie => $prix) {
            $tarifModels[$categorie] = Tarif::firstOrCreate(
                ['categorie' => $categorie],
                ['prix' => $prix]
            );
        }

        // 6) Plans d'abonnement
        $plans = [
            ['Abonnement 1 mois', 1, 5000],
            ['Abonnement 6 mois', 6, 27000],
            ['Abonnement 12 mois', 12, 50000],
        ];
        foreach ($plans as [$nom, $duree, $prix]) {
            Plan::firstOrCreate(['nom' => $nom], ['duree_mois' => $duree, 'prix' => $prix, 'actif' => true]);
        }

        // 7) Chaloupes
        $beer = Chaloupe::firstOrCreate(
            ['imatriculation' => 'IM-BEER-001'],
            ['nom' => 'Joseph Ndiaye', 'capacite' => 450, 'statut' => StatutChaloupeEnum::ACTIVE->value]
        );
        $castel = Chaloupe::firstOrCreate(
            ['imatriculation' => 'IM-COUMBA-001'],
            ['nom' => 'Coumba Castel', 'capacite' => 350, 'statut' => StatutChaloupeEnum::ACTIVE->value]
        );
        $augustin = Chaloupe::firstOrCreate(
            ['imatriculation' => 'IM-AUG-001'],
            ['nom' => 'Augustin Elimane Ly', 'capacite' => 150, 'statut' => StatutChaloupeEnum::EN_MAINTENANCE->value]
        );

        // 8) Trajets récurrents
        $departs = ['07:30', '10:00', '14:00', '16:00', '18:30'];
        foreach (JourEnum::cases() as $jour) {
            foreach ($departs as $heure) {
                Trajet::firstOrCreate(
                    ['jour' => $jour->value, 'heure_depart' => $heure],
                    ['duree' => 20]
                );
            }
        }

        // 9) Génération des voyages des 7 prochains jours
        (new GenererVoyagesSemaineJob)->handle();

        // 10) Demandes de résidence avec pièces justificatives
        $residentsDemandes = [
            ['Ibrahima', 'Ba', DemandeResidenceEnum::ACCEPTEE, 'Gorée Centre', 'CNI99281726'],
            ['Fatou', 'Diallo', DemandeResidenceEnum::EN_COURS, 'Gorée Nord', 'CNI29837162'],
            ['Mariama', 'Fall', DemandeResidenceEnum::REFUSEE, 'Gorée Sud', 'CNI10293847', 'Pièce d\'identité illisible ou expirée'],
            ['Amina', 'Sow', DemandeResidenceEnum::ACCEPTEE, 'Gorée Ouest', 'CNI87364521'],
        ];

        foreach ($residentsDemandes as $item) {
            $user = collect($clients)->first(fn($c) => $c->prenom === $item[0]);
            if ($user) {
                DemandeResidence::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'carte_identite' => $item[4],
                        'residence' => $item[3],
                        'statut' => $item[2]->value,
                        'photo' => 'demandes_residence/photo_' . strtolower($user->prenom) . '.png',
                        'cni_recto' => 'demandes_residence/cni_recto_' . strtolower($user->prenom) . '.png',
                        'cni_verso' => 'demandes_residence/cni_verso_' . strtolower($user->prenom) . '.png',
                        'certificat_residence' => 'demandes_residence/certificat_' . strtolower($user->prenom) . '.pdf',
                        'motif_refus' => $item[5] ?? null,
                        'valide_par' => $item[2] === DemandeResidenceEnum::ACCEPTEE ? collect($agents)->first()->id : null,
                        'date_validation' => $item[2] === DemandeResidenceEnum::ACCEPTEE ? now() : null,
                    ]
                );
            }
        }

        // 11) Billets achetés, Paiements et Historique
        $voyages = Voyage::take(5)->get();
        $modes = ['WAVE', 'ORANGE_MONEY', 'CARTE_BANCAIRE', 'YAS', 'PORTEFEUILLE'];

        foreach ($clients as $index => $client) {
            // Chacun achète 1 à 3 billets distincts
            $numTickets = rand(1, 3);
            $selectedVoyages = $voyages->random(min($numTickets, $voyages->count()));

            foreach ($selectedVoyages as $voyage) {
                $categorie = $client->est_resident ? CategorieEnum::RESIDENT->value : CategorieEnum::ETRANGER->value;
                $tarif = $tarifModels[$categorie];
                
                $statutBillet = $index % 3 === 0 
                    ? StatutBilletEnum::UTILISE 
                    : ($index % 3 === 1 ? StatutBilletEnum::PAYE : StatutBilletEnum::EXPIRE);

                $billet = Billet::create([
                    'qr_token' => 'QR-' . strtoupper(Str::random(12)),
                    'montant' => $tarif->prix,
                    'statut' => $statutBillet->value,
                    'voyage_id' => $voyage->id,
                    'tarif_id' => $tarif->id,
                    'user_id' => $client->id,
                ]);

                // Créer le paiement associé
                $payement = Payement::create([
                    'reference' => 'PAY-' . strtoupper(Str::random(8)),
                    'montant' => $tarif->prix,
                    'statut' => 'ACCEPTE',
                    'mode' => $modes[rand(0, 4)],
                    'type_transaction' => 'ACHAT_BILLET',
                    'billet_id' => $billet->id,
                    'user_id' => $client->id,
                ]);

                // Si le billet est utilisé, créer le scan
                if ($statutBillet === StatutBilletEnum::UTILISE) {
                    Scan::create([
                        'resultat' => 'VALIDE',
                        'billet_id' => $billet->id,
                        'scanne_par' => collect($agents)->random()->id,
                        'created_at' => now()->subHours(rand(1, 6)),
                    ]);
                }
            }

            // Standalone Wallet recharge records for history
            if (rand(0, 1)) {
                Payement::create([
                    'reference' => 'RECH-' . strtoupper(Str::random(8)),
                    'montant' => rand(2, 6) * 5000,
                    'statut' => 'ACCEPTE',
                    'mode' => $modes[rand(0, 1)], // Wave or OM
                    'type_transaction' => 'RECHARGE_PORTEFEUILLE',
                    'billet_id' => null,
                    'user_id' => $client->id,
                ]);
            }
        }
    }
}
