<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * ÉTAPE 3 : Ajouter les champs manquants à la table users
     * 
     * Cette migration ajoute les champs suivants :
     * - fonction : poste/grade de l'utilisateur (ex: Agent, Superviseur, Directeur)
     * - unite_service : unité/service auquel appartient l'utilisateur
     * - region : région géographique de l'utilisateur
     * - photo : photo de profil (utilise le champ 'avatar' s'il existe déjà)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Ajouter fonction (poste/grade)
            if (!Schema::hasColumn('users', 'fonction')) {
                $table->string('fonction')->nullable()->after('telephone');
            }

            // Ajouter unite_service (unité/service)
            if (!Schema::hasColumn('users', 'unite_service')) {
                $table->string('unite_service')->nullable()->after('fonction');
            }

            // Ajouter region (région géographique)
            if (!Schema::hasColumn('users', 'region')) {
                $table->string('region')->nullable()->after('unite_service');
            }

            // Note : Le champ 'avatar' existe déjà dans la migration Vuexy
            // On va l'utiliser pour stocker la photo de profil
            // Si nécessaire, on pourra ajouter un champ 'photo' séparément plus tard
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = ['fonction', 'unite_service', 'region'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
