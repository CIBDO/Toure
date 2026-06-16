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
     * Étape 1 : Modifie la table users pour la structure IAM
     * 
     * Cette migration :
     * - Ajoute les nouveaux champs (nom, prenom, telephone, statut, type_compte, last_login_at)
     * - Préserve les données existantes
     * - Garde l'id en bigIncrements (plus simple que UUID)
     * 
     * Note : Les anciens champs Vuexy (role, current_plan, etc.) seront supprimés dans une migration séparée
     * pour éviter de casser les données existantes.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Ajouter les nouveaux champs IAM
            // On garde 'name' pour compatibilité, on migrera vers nom/prenom progressivement
            if (!Schema::hasColumn('users', 'nom')) {
                $table->string('nom')->nullable()->after('id');
            }
            if (!Schema::hasColumn('users', 'prenom')) {
                $table->string('prenom')->nullable()->after('nom');
            }
            if (!Schema::hasColumn('users', 'telephone')) {
                $table->string('telephone')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'statut')) {
                $table->enum('statut', ['ACTIF', 'SUSPENDU', 'DESACTIVE'])->default('ACTIF')->after('password');
            }
            if (!Schema::hasColumn('users', 'type_compte')) {
                $table->enum('type_compte', ['CANAM', 'IMF', 'SYSTEME'])->default('CANAM')->after('statut');
            }
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('type_compte');
            }
        });

        // Migrer les données existantes : copier name dans nom si nom est vide
        DB::statement("UPDATE users SET nom = COALESCE(NULLIF(nom, ''), name) WHERE (nom IS NULL OR nom = '') AND name IS NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = ['nom', 'prenom', 'telephone', 'statut', 'type_compte', 'last_login_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
