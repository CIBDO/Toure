<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrats', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference')->unique();
            $table->string('objet');
            $table->unsignedBigInteger('pv_id')->nullable();
            $table->unsignedBigInteger('avis_id')->nullable();
            $table->unsignedBigInteger('fournisseur_id');
            $table->unsignedBigInteger('compte_budget_id')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable(); // agent responsable
            $table->decimal('montant_initial', 18, 2);
            $table->decimal('montant_actuel', 18, 2)->nullable();
            $table->string('devise')->default('GNF');
            $table->date('date_signature')->nullable();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->integer('duree_execution')->nullable(); // en jours
            $table->string('mode_passation')->nullable();
            $table->string('exercice', 4)->nullable();
            $table->string('statut')->default('draft'); // draft, submitted, approved, rejected, archived
            $table->text('observations')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('pv_id')->references('id')->on('pvs')->nullOnDelete();
            $table->foreign('avis_id')->references('id')->on('avis')->nullOnDelete();
            $table->foreign('fournisseur_id')->references('id')->on('fournisseurs')->restrictOnDelete();
            $table->foreign('compte_budget_id')->references('id')->on('comptes_budget')->nullOnDelete();
            $table->foreign('agent_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrats');
    }
};
