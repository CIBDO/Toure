<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reception_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reception_id')->constrained('receptions')->cascadeOnDelete();
            $table->unsignedBigInteger('contrat_item_id')->nullable();
            $table->string('label')->nullable();
            $table->decimal('quantite_prevue', 15, 4)->nullable();
            $table->decimal('quantite_recue', 15, 4)->nullable();
            $table->boolean('conforme')->default(true);
            $table->text('observation')->nullable();
            $table->timestamps();

            $table->index('reception_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reception_items');
    }
};
