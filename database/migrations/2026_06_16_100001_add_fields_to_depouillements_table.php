<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('depouillements', function (Blueprint $table) {
            $table->time('heure_depouillement')->nullable()->after('date_depouillement');
            $table->unsignedBigInteger('compte_budget_id')->nullable()->after('avis_id');
            $table->boolean('notification_reunion_envoyee')->default(false)->after('observations');

            $table->foreign('compte_budget_id')->references('id')->on('comptes_budget')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('depouillements', function (Blueprint $table) {
            $table->dropForeign(['compte_budget_id']);
            $table->dropColumn(['heure_depouillement', 'compte_budget_id', 'notification_reunion_envoyee']);
        });
    }
};
