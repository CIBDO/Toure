<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * ÉTAPE 2 : Ajouter le soft delete à la table users
     * 
     * Cette migration ajoute la colonne 'deleted_at' à la table users
     * pour permettre la suppression logique (soft delete).
     * 
     * Le soft delete permet de :
     * - Marquer un utilisateur comme supprimé sans le supprimer réellement
     * - Conserver l'historique (conformité réglementaire)
     * - Pouvoir restaurer un utilisateur supprimé
     * - Conserver les relations (ex: audit logs)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes(); // Ajoute la colonne 'deleted_at'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes(); // Supprime la colonne 'deleted_at'
        });
    }
};
