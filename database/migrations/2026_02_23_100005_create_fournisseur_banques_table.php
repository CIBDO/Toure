<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fournisseur_banques', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fournisseur_id');
            $table->unsignedBigInteger('banque_id');
            $table->string('numero_compte');
            $table->string('intitule_compte')->nullable();
            $table->boolean('principal')->default(false);
            $table->timestamps();

            $table->foreign('fournisseur_id')->references('id')->on('fournisseurs')->cascadeOnDelete();
            $table->foreign('banque_id')->references('id')->on('banques')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fournisseur_banques');
    }
};
