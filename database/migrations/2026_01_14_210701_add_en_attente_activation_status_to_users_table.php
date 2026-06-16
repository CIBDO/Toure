<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * ÉTAPE 1 : Ajouter le statut EN_ATTENTE_ACTIVATION
     * 
     * Cette migration ajoute la valeur 'EN_ATTENTE_ACTIVATION' à l'enum 'statut'
     * de la table users.
     * 
     * Ce statut permet de créer des comptes qui nécessitent une activation
     * par un administrateur avant de pouvoir se connecter.
     */
    public function up(): void
    {
        // Modifier l'ENUM pour ajouter EN_ATTENTE_ACTIVATION
        // Note : On ne peut pas modifier un ENUM avec Schema::table, il faut utiliser DB::statement
        DB::statement("ALTER TABLE users MODIFY COLUMN statut ENUM('ACTIF', 'SUSPENDU', 'DESACTIVE', 'EN_ATTENTE_ACTIVATION') DEFAULT 'ACTIF'");
    }

    /**
     * Reverse the migrations.
     * 
     * Attention : Cette opération peut échouer si des utilisateurs ont le statut EN_ATTENTE_ACTIVATION
     * Il faudrait d'abord mettre à jour ces utilisateurs vers un autre statut.
     */
    public function down(): void
    {
        // Remettre l'ENUM sans EN_ATTENTE_ACTIVATION
        // Mettre à jour les utilisateurs avec EN_ATTENTE_ACTIVATION vers ACTIF avant de modifier
        DB::statement("UPDATE users SET statut = 'ACTIF' WHERE statut = 'EN_ATTENTE_ACTIVATION'");
        DB::statement("ALTER TABLE users MODIFY COLUMN statut ENUM('ACTIF', 'SUSPENDU', 'DESACTIVE') DEFAULT 'ACTIF'");
    }
};
