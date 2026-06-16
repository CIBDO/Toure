<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * ÉTAPE 8 : Créer la table user_sessions
     * 
     * Cette table stocke les sessions actives des utilisateurs :
     * - Token de session
     * - Utilisateur concerné
     * - Adresse IP et User Agent
     * - Date de création et dernière activité
     * - Date d'expiration
     * - Statut (active, revoked)
     * 
     * Permet de :
     * - Tracker toutes les sessions actives d'un utilisateur
     * - Révoquer des sessions spécifiques
     * - Se déconnecter de tous les appareils
     */
    public function up(): void
    {
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('Utilisateur propriétaire de la session');
            $table->string('token', 64)->unique()->comment('Token de session unique');
            $table->string('ip_address', 45)->nullable()->comment('Adresse IP de la connexion');
            $table->text('user_agent')->nullable()->comment('User agent du navigateur');
            $table->timestamp('last_activity_at')->nullable()->comment('Dernière activité sur cette session');
            $table->timestamp('expires_at')->nullable()->comment('Date d\'expiration de la session');
            $table->boolean('is_active')->default(true)->comment('Session active ou révoquée');
            $table->timestamps();

            // Index pour améliorer les performances
            $table->index('user_id');
            $table->index('token');
            $table->index('is_active');
            $table->index('expires_at');
            $table->index('last_activity_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
