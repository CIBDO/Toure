<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pieces_jointes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('entite_type'); // App\Models\Avis, App\Models\Pv, App\Models\Contrat
            $table->unsignedBigInteger('entite_id');
            $table->string('nom_original');
            $table->string('nom_stockage');
            $table->string('chemin');
            $table->string('type_mime')->nullable();
            $table->unsignedBigInteger('taille')->nullable(); // en octets
            $table->string('categorie')->nullable(); // offre, rapport, contrat, avenant, autre
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['entite_type', 'entite_id']);
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pieces_jointes');
    }
};
