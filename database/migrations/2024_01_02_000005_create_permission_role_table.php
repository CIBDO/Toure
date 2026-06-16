<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Étape 5 : Crée la table pivot permission_role
     * 
     * Cette table de liaison permet à un rôle d'avoir plusieurs permissions
     * Relation many-to-many : Roles ↔ Permissions
     */
    public function up(): void
    {
        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->timestamps();

            // Index unique pour éviter les doublons
            $table->unique(['role_id', 'permission_id']);

            // Index pour améliorer les performances
            $table->index('role_id');
            $table->index('permission_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_role');
    }
};
