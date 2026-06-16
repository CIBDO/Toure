<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            $table->integer('delai')->nullable()->after('duree')->comment('Délai de livraison en jours');
            $table->date('date_publication')->nullable()->after('date_ouverture_plis');
        });

        foreach (DB::table('avis')->select('id')->orderBy('id')->cursor() as $avis) {
            $itemDelai = DB::table('avis_items')
                ->where('avis_id', $avis->id)
                ->whereNotNull('delai')
                ->orderBy('ordre')
                ->value('delai');

            if ($itemDelai !== null) {
                DB::table('avis')->where('id', $avis->id)->update(['delai' => $itemDelai]);
            }
        }

        DB::table('avis')
            ->where('statut', 'published')
            ->whereNull('date_publication')
            ->update(['date_publication' => DB::raw('DATE(updated_at)')]);

        Schema::table('avis_items', function (Blueprint $table) {
            $table->dropColumn('delai');
        });
    }

    public function down(): void
    {
        Schema::table('avis_items', function (Blueprint $table) {
            $table->integer('delai')->nullable()->after('unite');
        });

        foreach (DB::table('avis')->select('id', 'delai')->orderBy('id')->cursor() as $avis) {
            if ($avis->delai !== null) {
                DB::table('avis_items')
                    ->where('avis_id', $avis->id)
                    ->orderBy('ordre')
                    ->limit(1)
                    ->update(['delai' => $avis->delai]);
            }
        }

        Schema::table('avis', function (Blueprint $table) {
            $table->dropColumn(['delai', 'date_publication']);
        });
    }
};
