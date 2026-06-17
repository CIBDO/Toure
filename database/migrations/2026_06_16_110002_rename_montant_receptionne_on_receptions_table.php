<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            $table->decimal('quantite_receptionnee', 12, 2)->nullable()->after('statut_conformite');
        });

        if (Schema::hasColumn('receptions', 'montant_receptionne')) {
            DB::table('receptions')->update([
                'quantite_receptionnee' => DB::raw('montant_receptionne'),
            ]);

            Schema::table('receptions', function (Blueprint $table) {
                $table->dropColumn('montant_receptionne');
            });
        }
    }

    public function down(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            $table->unsignedBigInteger('montant_receptionne')->nullable()->after('statut_conformite');
        });

        if (Schema::hasColumn('receptions', 'quantite_receptionnee')) {
            DB::table('receptions')->update([
                'montant_receptionne' => DB::raw('CAST(quantite_receptionnee AS UNSIGNED)'),
            ]);

            Schema::table('receptions', function (Blueprint $table) {
                $table->dropColumn('quantite_receptionnee');
            });
        }
    }
};
