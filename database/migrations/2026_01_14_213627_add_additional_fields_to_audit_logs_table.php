<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * ÉTAPE 5 : Améliorer la table audit_logs
     * 
     * Cette migration ajoute les champs suivants à la table audit_logs :
     * - ancienne_valeur : Valeur avant modification (JSON)
     * - nouvelle_valeur : Valeur après modification (JSON)
     * - commentaire : Commentaire optionnel sur l'action
     * 
     * Ces champs permettent une traçabilité complète :
     * - Avant : "L'utilisateur X a modifié l'utilisateur Y"
     * - Après : "L'utilisateur X a modifié l'utilisateur Y : statut ACTIF → SUSPENDU"
     */
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // Ajouter ancienne_valeur (valeurs avant modification)
            if (!Schema::hasColumn('audit_logs', 'ancienne_valeur')) {
                $table->json('ancienne_valeur')->nullable()->after('payload_json')->comment('Valeurs avant modification (JSON)');
            }

            // Ajouter nouvelle_valeur (valeurs après modification)
            if (!Schema::hasColumn('audit_logs', 'nouvelle_valeur')) {
                $table->json('nouvelle_valeur')->nullable()->after('ancienne_valeur')->comment('Valeurs après modification (JSON)');
            }

            // Ajouter commentaire (commentaire optionnel)
            if (!Schema::hasColumn('audit_logs', 'commentaire')) {
                $table->text('commentaire')->nullable()->after('nouvelle_valeur')->comment('Commentaire optionnel sur l\'action');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $columns = ['commentaire', 'nouvelle_valeur', 'ancienne_valeur'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('audit_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
