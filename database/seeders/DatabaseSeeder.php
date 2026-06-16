<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder principal de la base de données
 * 
 * Ce seeder appelle tous les autres seeders dans l'ordre approprié
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // IMPORTANT : L'ordre est important !
        // On doit d'abord créer les permissions, puis les rôles (qui dépendent des permissions)

        $seeders = [
            PermissionSeeder::class,      // 1. Permissions
            RoleSeeder::class,            // 2. Rôles
            AdminUserSeeder::class,       // 3. Utilisateur administrateur
            AssignRolesToFirstUsersSeeder::class, // 4. Rôle ADMIN_CANAM pour les premiers utilisateurs (id 1, 2, 3)
            BanqueSeeder::class,          // 5. Banques
            DomaineActiviteSeeder::class, // 6. Domaines d'activité
            CompteBudgetSeeder::class,    // 7. Comptes budget
            FournisseurSeeder::class,     // 8. Fournisseurs
        ];

        // Données de démo : uniquement si APP_SEED_DEMO=true (dev/staging)
        if (config('app.env') !== 'production' || filter_var(env('APP_SEED_DEMO', false), FILTER_VALIDATE_BOOLEAN)) {
            $seeders[] = DemoDataSeeder::class;
        }

        $this->call($seeders);

        $this->command->info('Seeders exécutés avec succès !');
    }
}
