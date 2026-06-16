<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depouillements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference')->unique();
            $table->unsignedBigInteger('avis_id');
            $table->date('date_depouillement');
            $table->string('lieu')->nullable();
            $table->json('resultats')->nullable(); // structure extensible JSON
            $table->string('statut')->default('draft'); // draft, submitted, approved, rejected
            $table->text('observations')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('avis_id')->references('id')->on('avis')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depouillements');
    }
};
