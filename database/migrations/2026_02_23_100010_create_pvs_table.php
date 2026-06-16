<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pvs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference')->unique();
            $table->unsignedBigInteger('depouillement_id')->nullable();
            $table->unsignedBigInteger('avis_id');
            $table->unsignedBigInteger('fournisseur_attributaire_id')->nullable();
            $table->date('date_pv');
            $table->string('type_pv')->default('attribution'); // attribution, infructueux, annulation
            $table->decimal('montant_retenu', 18, 2)->nullable();
            $table->text('contenu')->nullable();
            $table->string('statut')->default('draft'); // draft, submitted, approved, rejected, archived
            $table->string('fichier_pdf')->nullable();
            $table->text('observations')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('depouillement_id')->references('id')->on('depouillements')->nullOnDelete();
            $table->foreign('avis_id')->references('id')->on('avis')->cascadeOnDelete();
            $table->foreign('fournisseur_attributaire_id')->references('id')->on('fournisseurs')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pvs');
    }
};
