<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pvs', function (Blueprint $table) {
            $table->unsignedInteger('nb_soumission')->default(0)->after('montant_retenu')
                  ->comment('Nombre de soumissions reçues');
            $table->string('fichier_pv_signe')->nullable()->after('fichier_pdf')
                  ->comment('Chemin du PV signé uploadé');
            $table->timestamp('date_signature')->nullable()->after('fichier_pv_signe')
                  ->comment('Date de signature du PV');
            $table->string('motif_rejet')->nullable()->after('observations')
                  ->comment('Motif de rejet si statut = rejected');
        });
    }

    public function down(): void
    {
        Schema::table('pvs', function (Blueprint $table) {
            $table->dropColumn(['nb_soumission', 'fichier_pv_signe', 'date_signature', 'motif_rejet']);
        });
    }
};
