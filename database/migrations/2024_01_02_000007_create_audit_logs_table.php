<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Étape 7 : Crée la table audit_logs
     * 
     * Cette table stocke tous les événements d'audit du système :
     * - Actions effectuées
     * - Qui a fait l'action
     * - Sur quel objet
     * - Quand et depuis où
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->onDelete('set null')->comment('Utilisateur qui a effectué l\'action (nullable si action publique)');
            $table->string('action')->comment('Action effectuée (CREATE, UPDATE, DELETE, LOGIN, etc.)');
            $table->string('objet_type')->nullable()->comment('Type d\'objet concerné (User, Demande, etc.)');
            $table->unsignedBigInteger('objet_id')->nullable()->comment('ID de l\'objet concerné');
            $table->json('payload_json')->nullable()->comment('Résumé JSON de l\'action');
            $table->string('ip', 45)->nullable()->comment('Adresse IP');
            $table->text('user_agent')->nullable()->comment('User agent du navigateur');
            $table->timestamps();

            // Index pour améliorer les performances des recherches
            $table->index('actor_user_id');
            $table->index('action');
            $table->index(['objet_type', 'objet_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
