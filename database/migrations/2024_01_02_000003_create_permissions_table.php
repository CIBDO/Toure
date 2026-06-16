<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Étape 3 : Crée la table permissions
     * 
     * Cette table stocke les permissions granulaires :
     * - DEMANDES_READ, DEMANDES_WRITE, IMF_EDIT, SANCTIONS_APPROVE, etc.
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Code unique de la permission');
            $table->string('libelle')->comment('Libellé de la permission');
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
        Schema::dropIfExists('permissions');
    }
};
