<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute la colonne password_reset_at : horodatage de la dernière définition
     * du mot de passe PAR l'utilisateur lui-même. NULL = compte créé par un admin
     * dont le mot de passe n'a pas encore été défini (activation via lien email).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('password_reset_at')->nullable()->after('mot_de_passe');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_reset_at');
        });
    }
};
