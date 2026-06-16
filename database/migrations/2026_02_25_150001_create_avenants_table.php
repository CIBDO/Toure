<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avenants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('contrat_id')->constrained('contrats')->cascadeOnDelete();
            $table->string('numero', 50)->comment('Numéro séquentiel par contrat : A1, A2...');
            $table->string('type_avenant', 20)->comment('montant, delai, objet, mixte');
            $table->bigInteger('montant_variation')->nullable()->comment('Variation en unités (positif = augmentation)');
            $table->bigInteger('ancien_montant')->comment('Montant contrat avant avenant');
            $table->bigInteger('nouveau_montant')->comment('Montant après application');
            $table->date('ancienne_date_fin')->nullable();
            $table->date('nouvelle_date_fin')->nullable();
            $table->integer('prolongation_jours')->nullable();
            $table->text('ancienne_description_objet')->nullable();
            $table->text('nouvelle_description_objet')->nullable();
            $table->text('justification');
            $table->date('date_signature');
            $table->string('statut', 20)->default('draft')->comment('draft, submitted, approved, rejected');
            $table->text('commentaire_validation')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['contrat_id', 'numero']);
            $table->index(['contrat_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avenants');
    }
};
