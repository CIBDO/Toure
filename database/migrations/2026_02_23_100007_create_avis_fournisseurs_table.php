<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avis_fournisseurs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('avis_id');
            $table->unsignedBigInteger('fournisseur_id');
            $table->date('date_invitation')->nullable();
            $table->boolean('a_soumis')->default(false);
            $table->date('date_soumission')->nullable();
            $table->timestamps();

            $table->unique(['avis_id', 'fournisseur_id']);
            $table->foreign('avis_id')->references('id')->on('avis')->cascadeOnDelete();
            $table->foreign('fournisseur_id')->references('id')->on('fournisseurs')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avis_fournisseurs');
    }
};
