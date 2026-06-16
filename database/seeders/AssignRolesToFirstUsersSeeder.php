<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Attribue le rôle "Administrateur CANAM" aux premiers utilisateurs
 * qui n'ont pas encore de rôle (ex. créés avant la mise en place IAM).
 *
 * À exécuter après PermissionSeeder et RoleSeeder :
 *   php artisan db:seed --class=AssignRolesToFirstUsersSeeder
 */
class AssignRolesToFirstUsersSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::where('code', 'ADMIN_CANAM')->first();

        if (!$role) {
            $this->command->warn('Le rôle ADMIN_CANAM n\'existe pas. Exécutez d\'abord : php artisan db:seed --class=RoleSeeder');
            return;
        }

        // Utilisateurs à qui attribuer le rôle (IDs ou emails)
        $userIds = [1, 2, 3];
        $users = User::whereIn('id', $userIds)->get();

        if ($users->isEmpty()) {
            $this->command->warn('Aucun utilisateur trouvé avec les IDs : ' . implode(', ', $userIds));
            return;
        }

        foreach ($users as $user) {
            if ($user->roles()->where('roles.id', $role->id)->exists()) {
                $this->command->info("  [{$user->email}] a déjà le rôle {$role->libelle}");
                continue;
            }
            $user->roles()->attach($role->id);
            $this->command->info("  [{$user->email}] → rôle « {$role->libelle} » assigné.");
        }

        $this->command->info('Attribution des rôles terminée.');
    }
}
