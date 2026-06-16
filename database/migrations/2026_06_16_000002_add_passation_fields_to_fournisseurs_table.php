<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ALL_MODES = [
        'AO_OUVERT', 'AO_RESTREINT', 'CONSULTATION', 'GRE_A_GRE', 'ENTENTE_DIRECTE',
    ];

    public function up(): void
    {
        Schema::table('fournisseurs', function (Blueprint $table) {
            $table->json('modes_passation')->nullable()->after('domaine_activite_id');
            $table->unsignedInteger('duree_min')->nullable()->after('modes_passation')
                ->comment('Durée min. de consultation (jours) acceptée');
            $table->unsignedInteger('duree_max')->nullable()->after('duree_min')
                ->comment('Durée max. de consultation (jours) acceptée');
        });

        DB::table('fournisseurs')->update([
            'modes_passation' => json_encode(self::ALL_MODES),
        ]);
    }

    public function down(): void
    {
        Schema::table('fournisseurs', function (Blueprint $table) {
            $table->dropColumn(['modes_passation', 'duree_min', 'duree_max']);
        });
    }
};
