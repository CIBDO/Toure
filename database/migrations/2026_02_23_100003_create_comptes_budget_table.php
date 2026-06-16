<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comptes_budget', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code')->unique();
            $table->string('libelle');
            $table->string('exercice', 4);
            $table->decimal('montant_alloue', 18, 2)->default(0);
            $table->decimal('montant_engage', 18, 2)->default(0);
            $table->decimal('montant_disponible', 18, 2)->default(0);
            $table->text('description')->nullable();
            $table->boolean('actif')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comptes_budget');
    }
};
