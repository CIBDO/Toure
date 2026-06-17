<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('depouillements', function (Blueprint $table) {
            $table->string('fichier_bordereau')->nullable()->after('notification_reunion_envoyee');
        });
    }

    public function down(): void
    {
        Schema::table('depouillements', function (Blueprint $table) {
            $table->dropColumn('fichier_bordereau');
        });
    }
};
