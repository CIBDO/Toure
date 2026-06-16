<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index pour optimiser les requêtes des rapports (agrégations, filtres).
     */
    public function up(): void
    {
        Schema::table('contrats', function (Blueprint $table) {
            $table->index('date_signature');
            $table->index('exercice');
            $table->index('fournisseur_id');
            $table->index('statut');
            $table->index('mode_passation');
            $table->index('compte_budget_id');
        });

        Schema::table('engagements', function (Blueprint $table) {
            $table->index('date_engagement');
            $table->index('exercice');
            $table->index('compte_budget_id');
            $table->index('statut');
            $table->index('contrat_id');
        });

        Schema::table('paiements', function (Blueprint $table) {
            $table->index('date_paiement');
            $table->index('mode_paiement');
            $table->index('statut');
            $table->index('engagement_id');
        });
    }

    public function down(): void
    {
        Schema::table('contrats', function (Blueprint $table) {
            $table->dropIndex(['date_signature']);
            $table->dropIndex(['exercice']);
            $table->dropIndex(['fournisseur_id']);
            $table->dropIndex(['statut']);
            $table->dropIndex(['mode_passation']);
            $table->dropIndex(['compte_budget_id']);
        });

        Schema::table('engagements', function (Blueprint $table) {
            $table->dropIndex(['date_engagement']);
            $table->dropIndex(['exercice']);
            $table->dropIndex(['compte_budget_id']);
            $table->dropIndex(['statut']);
            $table->dropIndex(['contrat_id']);
        });

        Schema::table('paiements', function (Blueprint $table) {
            $table->dropIndex(['date_paiement']);
            $table->dropIndex(['mode_paiement']);
            $table->dropIndex(['statut']);
            $table->dropIndex(['engagement_id']);
        });
    }
};
