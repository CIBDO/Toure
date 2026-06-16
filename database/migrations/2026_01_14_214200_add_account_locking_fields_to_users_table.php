<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * ÉTAPE 6 : Ajouter les champs de verrouillage de compte
     * 
     * Cette migration ajoute les champs suivants à la table users :
     * - failed_login_attempts : Nombre de tentatives de connexion échouées
     * - locked_until : Date et heure jusqu'à laquelle le compte est verrouillé
     * 
     * Le verrouillage permet de :
     * - Protéger contre les attaques par force brute
     * - Verrouiller automatiquement après X tentatives échouées
     * - Déverrouiller automatiquement après un certain temps
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Ajouter failed_login_attempts (nombre de tentatives échouées)
            if (!Schema::hasColumn('users', 'failed_login_attempts')) {
                $table->unsignedInteger('failed_login_attempts')->default(0)->after('last_login_at')->comment('Nombre de tentatives de connexion échouées');
            }

            // Ajouter locked_until (date de déverrouillage)
            if (!Schema::hasColumn('users', 'locked_until')) {
                $table->timestamp('locked_until')->nullable()->after('failed_login_attempts')->comment('Date jusqu\'à laquelle le compte est verrouillé');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = ['failed_login_attempts', 'locked_until'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
