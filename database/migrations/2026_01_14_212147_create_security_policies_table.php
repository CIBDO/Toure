<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * ÉTAPE 4 : Créer la table security_policies
     * 
     * Cette table stocke les politiques de sécurité du système :
     * - Longueur minimale du mot de passe
     * - Complexité requise (majuscules, minuscules, chiffres, caractères spéciaux)
     * - Expiration des mots de passe (en jours)
     * - Historique des mots de passe (nombre de mots de passe précédents à retenir)
     * - Verrouillage après X tentatives échouées
     * - Durée du verrouillage (en minutes)
     * 
     * Structure : Table clé-valeur pour flexibilité
     */
    public function up(): void
    {
        Schema::create('security_policies', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Clé de la politique (ex: password_min_length)');
            $table->text('value')->nullable()->comment('Valeur de la politique');
            $table->string('type')->default('string')->comment('Type de valeur : string, integer, boolean, json');
            $table->text('description')->nullable()->comment('Description de la politique');
            $table->boolean('is_active')->default(true)->comment('Politique active ou non');
            $table->timestamps();

            // Index pour améliorer les performances
            $table->index('key');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_policies');
    }
};
