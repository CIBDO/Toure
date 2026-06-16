<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avis', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference')->unique();
            $table->string('objet');
            $table->string('mode_passation'); // AO, CONSULTATION, GRE_A_GRE, etc.
            $table->string('article_pour')->nullable();
            $table->string('article_relatif')->nullable();
            $table->string('exercice', 4);
            $table->integer('duree')->nullable(); // en jours
            $table->date('date_limite_depot')->nullable();
            $table->date('date_ouverture_plis')->nullable();
            $table->string('statut')->default('draft'); // draft, published, closed, cancelled
            $table->text('observations')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avis');
    }
};
