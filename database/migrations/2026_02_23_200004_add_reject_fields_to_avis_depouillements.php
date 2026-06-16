<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            $table->string('motif_rejet')->nullable()->after('observations');
        });

        Schema::table('depouillements', function (Blueprint $table) {
            $table->string('motif_rejet')->nullable()->after('observations');
        });
    }

    public function down(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            $table->dropColumn('motif_rejet');
        });
        Schema::table('depouillements', function (Blueprint $table) {
            $table->dropColumn('motif_rejet');
        });
    }
};
