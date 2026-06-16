<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Champs pour le template Vuexy
            $table->string('role')->default('subscriber')->after('email');
            $table->string('current_plan')->nullable()->after('role');
            $table->string('status')->default('pending')->after('current_plan');
            $table->string('company')->nullable()->after('status');
            $table->string('country')->nullable()->after('company');
            $table->string('contact')->nullable()->after('country');
            $table->string('avatar')->nullable()->after('contact');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'current_plan',
                'status',
                'company',
                'country',
                'contact',
                'avatar',
            ]);
        });
    }
};
