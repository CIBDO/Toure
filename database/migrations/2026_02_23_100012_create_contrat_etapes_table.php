<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrat_etapes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contrat_id');
            $table->string('type_etape'); // elaboration, engagement, oem, mandat, paie
            $table->date('date_prevue')->nullable();
            $table->date('date_effective')->nullable();
            $table->string('statut')->default('pending'); // pending, in_progress, completed, blocked
            $table->text('commentaire')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('contrat_id')->references('id')->on('contrats')->cascadeOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrat_etapes');
    }
};
