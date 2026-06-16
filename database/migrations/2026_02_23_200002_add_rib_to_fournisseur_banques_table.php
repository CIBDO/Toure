<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fournisseur_banques', function (Blueprint $table) {
            $table->string('rib')->nullable()->after('numero_compte'); // Relevé d'Identité Bancaire
            $table->string('swift')->nullable()->after('rib');
            $table->string('iban')->nullable()->after('swift');
        });
    }

    public function down(): void
    {
        Schema::table('fournisseur_banques', function (Blueprint $table) {
            $table->dropColumn(['rib', 'swift', 'iban']);
        });
    }
};
