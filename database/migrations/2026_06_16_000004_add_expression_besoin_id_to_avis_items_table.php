<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avis_items', function (Blueprint $table) {
            $table->unsignedBigInteger('expression_besoin_id')->nullable()->after('avis_id');
            $table->foreign('expression_besoin_id')->references('id')->on('expressions_besoin')->nullOnDelete();
        });

        $ordre = 1;
        foreach (DB::table('avis_items')->select('designation')->distinct()->pluck('designation') as $designation) {
            $code = 'EB-MIG-' . str_pad((string) $ordre, 4, '0', STR_PAD_LEFT);
            $expressionId = DB::table('expressions_besoin')->insertGetId([
                'uuid'       => (string) Str::uuid(),
                'code'       => $code,
                'libelle'    => $designation,
                'actif'      => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('avis_items')
                ->where('designation', $designation)
                ->update(['expression_besoin_id' => $expressionId]);

            $ordre++;
        }
    }

    public function down(): void
    {
        Schema::table('avis_items', function (Blueprint $table) {
            $table->dropForeign(['expression_besoin_id']);
            $table->dropColumn('expression_besoin_id');
        });
    }
};
