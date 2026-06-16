<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('engagements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('contrat_id')->constrained('contrats')->cascadeOnDelete();
            $table->string('numero')->unique();
            $table->date('date_engagement');
            $table->string('exercice', 4);
            $table->foreignId('compte_budget_id')->nullable()->constrained('comptes_budget')->nullOnDelete();
            $table->bigInteger('montant_engage');
            $table->string('statut')->default('draft');
            $table->text('commentaire_validation')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('engagements');
    }
};
