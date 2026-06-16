<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Mettre à jour les valeurs 'DM' existantes vers 'CANAM' avant de modifier l'enum
        DB::statement("UPDATE users SET type_compte = 'CANAM' WHERE type_compte = 'DM'");

        // Modifier l'enum pour remplacer 'DM' par 'CANAM'
        DB::statement("ALTER TABLE users MODIFY COLUMN type_compte ENUM('CANAM','CONTRAT','SYSTEME') NOT NULL DEFAULT 'CANAM'");
    }

    public function down(): void
    {
        DB::statement("UPDATE users SET type_compte = 'DM' WHERE type_compte = 'CANAM'");
        DB::statement("ALTER TABLE users MODIFY COLUMN type_compte ENUM('DM','CONTRAT','SYSTEME') NOT NULL DEFAULT 'DM'");
    }
};
