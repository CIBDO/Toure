<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fournisseurs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code')->unique();
            $table->string('raison_sociale');
            $table->string('sigle')->nullable();
            $table->string('nif')->nullable()->unique();
            $table->string('rc')->nullable();
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->string('adresse')->nullable();
            $table->string('ville')->nullable();
            $table->string('pays')->default('Mali');
            $table->string('representant')->nullable();
            $table->string('fonction_representant')->nullable();
            $table->unsignedBigInteger('domaine_activite_id')->nullable();
            $table->string('statut')->default('actif'); // actif, suspendu, blackliste
            $table->text('observations')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('domaine_activite_id')->references('id')->on('domaines_activite')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fournisseurs');
    }
};
