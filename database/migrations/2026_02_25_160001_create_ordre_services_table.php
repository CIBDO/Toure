<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordre_services', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('contrat_id')->constrained('contrats')->cascadeOnDelete();
            $table->string('numero', 80)->comment('Format OS-{année}-{contract_num}-{sequence}');
            $table->string('type_os', 30)->comment('demarrage, suspension, reprise, arret, modification, autre');
            $table->string('objet');
            $table->text('description')->nullable();
            $table->date('date_emission');
            $table->date('date_effet')->nullable();
            $table->string('impact_delai', 10)->default('none')->comment('none, extend, reduce');
            $table->integer('delai_jours')->nullable()->comment('Impact en jours (+/-)');
            $table->string('statut', 20)->default('draft')->comment('draft, submitted, approved, rejected, executed, archived');
            $table->text('commentaire_validation')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['contrat_id', 'statut']);
            $table->index('date_emission');
            $table->index('type_os');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordre_services');
    }
};
