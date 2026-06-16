<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Étape 2 : Crée la table roles
     * 
     * Cette table stocke les rôles du système :
     * - ADMIN_DM, SUP_DM, AGENT_DM, IMF_USER, AUDIT_READ, etc.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Code unique du rôle (ADMIN_DM, SUP_DM, etc.)');
            $table->string('libelle')->comment('Libellé du rôle');
            $table->timestamps();

            // Index pour améliorer les performances
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
