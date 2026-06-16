<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contrats', function (Blueprint $table) {
            $table->string('status_execution')->nullable()->after('statut')
                ->comment('reception_provisoire, reception_definitive, en_execution, etc.');
            $table->boolean('cloturable')->default(false)->after('status_execution')
                ->comment('Contrat éligible à clôture après réception définitive approuvée');
        });
    }

    public function down(): void
    {
        Schema::table('contrats', function (Blueprint $table) {
            $table->dropColumn(['status_execution', 'cloturable']);
        });
    }
};
