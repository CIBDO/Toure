<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration : Gestion des utilisateurs CANAM
 *
 * Cette migration :
 * 1. Renomme le type_compte 'IMF' en 'CONTRAT' (Gestion des Contrats CANAM)
 * 2. Ajoute les champs complémentaires pour une gestion complète des utilisateurs
 *    (matricule, date_naissance, sexe, adresse, structure_rattachement, date_prise_fonction)
 * 3. Migre les données existantes (type_compte IMF → CONTRAT)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Modifier l'enum type_compte : IMF → CONTRAT ──────────────────────
        // MySQL ne permet pas de modifier directement un ENUM avec des données existantes.
        // On passe par une colonne temporaire.

        Schema::table('users', function (Blueprint $table) {
            // Ajouter une colonne temporaire string pour la transition
            $table->string('type_compte_new')->nullable()->after('type_compte');
        });

        // Copier les valeurs en convertissant IMF → CONTRAT
        DB::statement("
            UPDATE users
            SET type_compte_new = CASE
                WHEN type_compte = 'IMF'     THEN 'CONTRAT'
                WHEN type_compte = 'DM'      THEN 'CANAM'
                WHEN type_compte = 'CANAM'   THEN 'CANAM'
                WHEN type_compte = 'SYSTEME' THEN 'SYSTEME'
                ELSE 'CANAM'
            END
        ");

        // Supprimer l'ancienne colonne enum
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('type_compte');
        });

        // Recréer la colonne avec le bon enum
        Schema::table('users', function (Blueprint $table) {
            $table->enum('type_compte', ['CANAM', 'CONTRAT', 'SYSTEME'])
                  ->default('CANAM')
                  ->after('statut');
        });

        // Copier les données de la colonne temporaire
        DB::statement("UPDATE users SET type_compte = type_compte_new WHERE type_compte_new IS NOT NULL");

        // Supprimer la colonne temporaire
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('type_compte_new');
        });

        // ── 2. Ajouter les champs complémentaires pour la gestion des utilisateurs ──
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'matricule')) {
                $table->string('matricule', 50)
                      ->nullable()
                      ->unique()
                      ->after('prenom')
                      ->comment('Matricule interne de l\'agent CANAM');
            }

            if (!Schema::hasColumn('users', 'date_naissance')) {
                $table->date('date_naissance')
                      ->nullable()
                      ->after('matricule')
                      ->comment('Date de naissance');
            }

            if (!Schema::hasColumn('users', 'sexe')) {
                $table->enum('sexe', ['M', 'F'])
                      ->nullable()
                      ->after('date_naissance')
                      ->comment('Sexe : M (Masculin) ou F (Féminin)');
            }

            if (!Schema::hasColumn('users', 'adresse')) {
                $table->string('adresse', 500)
                      ->nullable()
                      ->after('sexe')
                      ->comment('Adresse physique');
            }

            if (!Schema::hasColumn('users', 'structure_rattachement')) {
                $table->string('structure_rattachement', 255)
                      ->nullable()
                      ->after('unite_service')
                      ->comment('Structure ou direction de rattachement CANAM');
            }

            if (!Schema::hasColumn('users', 'date_prise_fonction')) {
                $table->date('date_prise_fonction')
                      ->nullable()
                      ->after('fonction')
                      ->comment('Date de prise de fonction dans le poste actuel');
            }

            if (!Schema::hasColumn('users', 'photo_profil')) {
                $table->string('photo_profil', 500)
                      ->nullable()
                      ->after('avatar')
                      ->comment('URL de la photo de profil officielle');
            }

            if (!Schema::hasColumn('users', 'notes_admin')) {
                $table->text('notes_admin')
                      ->nullable()
                      ->after('photo_profil')
                      ->comment('Notes internes de l\'administrateur sur cet utilisateur');
            }
        });

        // ── 3. Ajouter des index pour les recherches fréquentes ──────────────────
        Schema::table('users', function (Blueprint $table) {
            if (!$this->indexExists('users', 'users_type_compte_index')) {
                $table->index('type_compte', 'users_type_compte_index');
            }
            if (!$this->indexExists('users', 'users_statut_index')) {
                $table->index('statut', 'users_statut_index');
            }
            if (!$this->indexExists('users', 'users_structure_rattachement_index')) {
                $table->index('structure_rattachement', 'users_structure_rattachement_index');
            }
        });
    }

    public function down(): void
    {
        // Supprimer les index ajoutés (si existants)
        Schema::table('users', function (Blueprint $table) {
            if ($this->indexExists('users', 'users_type_compte_index'))
                $table->dropIndex('users_type_compte_index');
            if ($this->indexExists('users', 'users_statut_index'))
                $table->dropIndex('users_statut_index');
            if ($this->indexExists('users', 'users_structure_rattachement_index'))
                $table->dropIndex('users_structure_rattachement_index');
        });

        // Supprimer les champs complémentaires
        Schema::table('users', function (Blueprint $table) {
            $columnsToRemove = [
                'matricule', 'date_naissance', 'sexe', 'adresse',
                'structure_rattachement', 'date_prise_fonction',
                'photo_profil', 'notes_admin',
            ];
            foreach ($columnsToRemove as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        // Remettre l'enum type_compte avec IMF
        Schema::table('users', function (Blueprint $table) {
            $table->string('type_compte_old')->nullable()->after('type_compte');
        });

        DB::statement("
            UPDATE users
            SET type_compte_old = CASE
                WHEN type_compte = 'CONTRAT' THEN 'IMF'
                ELSE type_compte
            END
        ");

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('type_compte');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('type_compte', ['CANAM', 'IMF', 'SYSTEME'])->default('CANAM')->after('statut');
        });

        DB::statement("UPDATE users SET type_compte = type_compte_old WHERE type_compte_old IS NOT NULL");

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('type_compte_old');
        });
    }

    /**
     * Vérifie si un index existe sur une table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }
};
