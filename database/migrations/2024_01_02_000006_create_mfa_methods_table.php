<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Étape 6 : Crée la table mfa_methods (optionnel)
     * 
     * Cette table stocke les méthodes d'authentification multi-facteurs :
     * - TOTP (Time-based One-Time Password)
     * - SMS
     * - EMAIL
     */
    public function up(): void
    {
        Schema::create('mfa_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['TOTP', 'SMS', 'EMAIL'])->comment('Type de MFA');
            $table->string('secret')->nullable()->comment('Secret pour TOTP');
            $table->string('destination')->nullable()->comment('Numéro de téléphone ou email selon le type');
            $table->boolean('actif')->default(false)->comment('Méthode active ou non');
            $table->timestamps();

            // Index pour améliorer les performances
            $table->index('user_id');
            $table->index(['user_id', 'actif']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mfa_methods');
    }
};
