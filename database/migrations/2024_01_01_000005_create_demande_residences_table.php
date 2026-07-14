<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demande_residences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('carte_identite');
            $table->string('residence');
            $table->string('statut')->default('EN_COURS');
            $table->string('photo');
            $table->string('cni_recto')->nullable();
            $table->string('cni_verso')->nullable();
            $table->string('certificat_residence')->nullable();
            $table->string('motif_refus')->nullable();
            $table->foreignUuid('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('date_validation')->nullable();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demande_residences');
    }
};
