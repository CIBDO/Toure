<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('contrat_id')->constrained('contrats')->cascadeOnDelete();
            $table->string('numero');
            $table->string('type_reception'); // provisoire, partielle, definitive
            $table->date('date_reception');
            $table->string('lieu_reception')->nullable();
            $table->string('responsable_reception')->nullable();
            $table->text('constatations')->nullable();
            $table->text('reserves')->nullable();
            $table->string('statut_conformite')->default('conforme'); // conforme, non_conforme, conforme_avec_reserves
            $table->unsignedBigInteger('montant_receptionne')->nullable();
            $table->decimal('taux_execution', 5, 2)->nullable();
            $table->string('statut')->default('draft'); // draft, submitted, approved, rejected
            $table->text('commentaire_validation')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->index('contrat_id');
            $table->index('type_reception');
            $table->index('statut');
            $table->index('date_reception');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receptions');
    }
};
