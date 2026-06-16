<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Contrats : motif_rejet + numero (alias lisible)
        Schema::table('contrats', function (Blueprint $table) {
            $table->string('motif_rejet')->nullable()->after('observations')
                  ->comment('Motif de rejet si statut = rejected');
            $table->string('numero')->nullable()->after('reference')
                  ->comment('Numéro officiel du contrat (distinct de la référence interne)');
        });

        // ContratEtapes : date_limite pour détection retard
        Schema::table('contrat_etapes', function (Blueprint $table) {
            $table->date('date_limite')->nullable()->after('date_prevue')
                  ->comment('Date limite de réalisation de l\'étape (pour calcul retard)');
            $table->string('piece_jointe')->nullable()->after('commentaire')
                  ->comment('Chemin du fichier justificatif de l\'étape');
        });
    }

    public function down(): void
    {
        Schema::table('contrats', function (Blueprint $table) {
            $table->dropColumn(['motif_rejet', 'numero']);
        });
        Schema::table('contrat_etapes', function (Blueprint $table) {
            $table->dropColumn(['date_limite', 'piece_jointe']);
        });
    }
};
