<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Session d'embarquement : un contrôleur « ouvre » l'embarcation d'un voyage,
     * puis les scans se rattachent à cette session (et donc à ce voyage).
     */
    public function up(): void
    {
        Schema::create('embarquements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('statut')->default('OUVERT');
            $table->timestamp('ouvert_a')->nullable();
            $table->timestamp('ferme_a')->nullable();
            $table->foreignUuid('voyage_id')->constrained('voyages')->cascadeOnDelete();
            $table->foreignUuid('ouvert_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('scans', function (Blueprint $table) {
            $table->foreignUuid('embarquement_id')->nullable()->after('billet_id')
                ->constrained('embarquements')->nullOnDelete();
            $table->foreignUuid('scanne_par')->nullable()->after('embarquement_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('scanne_par');
            $table->dropConstrainedForeignId('embarquement_id');
        });

        Schema::dropIfExists('embarquements');
    }
};
