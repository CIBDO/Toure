<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('engagement_id')->constrained('engagements')->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->date('date_paiement');
            $table->bigInteger('montant');
            $table->string('mode_paiement')->default('virement');
            $table->foreignId('banque_id')->nullable()->constrained('banques')->nullOnDelete();
            $table->text('observation')->nullable();
            $table->string('statut')->default('draft');
            $table->text('commentaire_validation')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
