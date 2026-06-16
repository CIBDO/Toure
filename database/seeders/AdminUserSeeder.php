<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@canam.ml'],
            [
                'name'          => 'Administrateur CANAM',
                'nom'           => 'CANAM',
                'prenom'        => 'Administrateur',
                'email'         => 'admin@canam.ml',
                'password'      => Hash::make('Admin@2026'),
                'type_compte'   => 'SYSTEME',
                'statut'        => 'ACTIF',
                'email_verified_at' => now(),
            ]
        );

        $roleAdmin = Role::where('code', 'ADMIN_CANAM')->first();
        if ($roleAdmin && !$admin->roles()->where('roles.id', $roleAdmin->id)->exists()) {
            $admin->roles()->attach($roleAdmin->id);
        }

        if ($admin->wasRecentlyCreated) {
            $this->command->info("Utilisateur admin créé : admin@canam.ml / Admin@2026");
        } else {
            $this->command->info("Utilisateur admin déjà existant : admin@canam.ml");
        }
    }
}
