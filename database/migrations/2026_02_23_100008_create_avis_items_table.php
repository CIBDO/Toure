<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avis_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('avis_id');
            $table->integer('ordre')->default(1);
            $table->string('designation');
            $table->text('description_detaillee')->nullable();
            $table->decimal('quantite', 10, 2)->default(1);
            $table->string('unite')->nullable();
            $table->integer('delai')->nullable(); // en jours
            $table->string('lieu')->nullable();
            $table->timestamps();

            $table->foreign('avis_id')->references('id')->on('avis')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avis_items');
    }
};
