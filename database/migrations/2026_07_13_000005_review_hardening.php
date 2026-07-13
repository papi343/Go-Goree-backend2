<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // M1 : index sur le jeton PayDunya (recherché à chaque webhook).
        Schema::table('payements', function (Blueprint $table) {
            $table->index('paydunya_token');
        });

        // M2 : lien dur abonnement -> paiement (idempotence d'activation fiable).
        Schema::table('abonnements', function (Blueprint $table) {
            $table->foreignUuid('payement_id')->nullable()->after('plan_id')
                ->constrained('payements')->nullOnDelete();
        });

        // L3 : un billet « gratuit » d'abonné peut ne référencer aucun tarif.
        Schema::table('billets', function (Blueprint $table) {
            $table->uuid('tarif_id')->nullable()->change();
        });

        // H1 : au plus un billet ACTIF par (client, voyage) — garanti au niveau DB
        // (empêche la course entre deux achats simultanés).
        DB::statement(
            'CREATE UNIQUE INDEX billets_user_voyage_actif_unique ON billets (user_id, voyage_id) '.
            "WHERE statut IN ('EN_ATTENTE_PAIEMENT','PAYE','UTILISE') AND deleted_at IS NULL"
        );

        // L2 : au plus un voyage par (trajet, date) — évite les doublons de génération.
        DB::statement(
            'CREATE UNIQUE INDEX voyages_trajet_date_unique ON voyages (trajet_id, date_voyage) '.
            'WHERE deleted_at IS NULL'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS voyages_trajet_date_unique');
        DB::statement('DROP INDEX IF EXISTS billets_user_voyage_actif_unique');

        Schema::table('abonnements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payement_id');
        });

        Schema::table('payements', function (Blueprint $table) {
            $table->dropIndex(['paydunya_token']);
        });
    }
};
