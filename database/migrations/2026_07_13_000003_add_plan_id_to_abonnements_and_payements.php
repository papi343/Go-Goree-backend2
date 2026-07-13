<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abonnements', function (Blueprint $table) {
            $table->foreignUuid('plan_id')->nullable()->after('resident_id')
                ->constrained('plans')->nullOnDelete();
        });

        Schema::table('payements', function (Blueprint $table) {
            $table->foreignUuid('plan_id')->nullable()->after('billet_id')
                ->constrained('plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('abonnements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
        });

        Schema::table('payements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
        });
    }
};
