<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fournisseurs', function (Blueprint $table) {
            $table->string('civilite')->nullable()->after('code');          // M., Mme, Dr, etc.
            $table->string('qualite_fonction')->nullable()->after('civilite'); // PDG, DG, Gérant, etc.
            $table->string('region')->nullable()->after('ville');
            $table->string('fax')->nullable()->after('telephone');
        });
    }

    public function down(): void
    {
        Schema::table('fournisseurs', function (Blueprint $table) {
            $table->dropColumn(['civilite', 'qualite_fonction', 'region', 'fax']);
        });
    }
};
