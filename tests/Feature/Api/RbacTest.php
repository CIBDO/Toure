<?php

namespace Tests\Feature\Api;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $lecteur;
    private string $adminToken;
    private string $lecteurToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer les permissions de base
        $permissions = [
            ['code' => 'USERS_READ',   'libelle' => 'Lire les utilisateurs'],
            ['code' => 'USERS_CREATE', 'libelle' => 'Créer des utilisateurs'],
            ['code' => 'USERS_EDIT',   'libelle' => 'Modifier les utilisateurs'],
            ['code' => 'USERS_DELETE', 'libelle' => 'Supprimer les utilisateurs'],
            ['code' => 'CONTRATS_READ',  'libelle' => 'Lire les contrats'],
            ['code' => 'CONTRATS_WRITE', 'libelle' => 'Créer/Modifier les contrats'],
            ['code' => 'ROLES_READ',   'libelle' => 'Lire les rôles'],
        ];

        foreach ($permissions as $p) {
            Permission::create($p);
        }

        // Rôle admin avec toutes les permissions
        $adminRole = Role::create(['code' => 'ADMIN', 'libelle' => 'Administrateur']);
        $adminRole->permissions()->sync(Permission::all()->pluck('id'));

        // Rôle lecteur avec lecture seule
        $lecteurRole = Role::create(['code' => 'LECTEUR', 'libelle' => 'Lecteur']);
        $lecteurRole->permissions()->sync(
            Permission::whereIn('code', ['USERS_READ', 'CONTRATS_READ', 'ROLES_READ'])->pluck('id')
        );

        $this->admin = User::factory()->create([
            'statut' => 'ACTIF',
            'type_compte' => 'SYSTEME',
        ]);
        $this->admin->roles()->attach($adminRole);
        $this->adminToken = $this->admin->createToken('test')->plainTextToken;

        $this->lecteur = User::factory()->create([
            'statut' => 'ACTIF',
            'type_compte' => 'CANAM',
        ]);
        $this->lecteur->roles()->attach($lecteurRole);
        $this->lecteurToken = $this->lecteur->createToken('test')->plainTextToken;
    }

    public function test_admin_can_list_users(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->getJson('/api/users');

        $response->assertStatus(200);
    }

    public function test_lecteur_can_list_users(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->lecteurToken}")
            ->getJson('/api/users');

        $response->assertStatus(200);
    }

    public function test_unauthenticated_cannot_access_users(): void
    {
        $response = $this->getJson('/api/users');
        $response->assertStatus(401);
    }

    public function test_admin_can_create_user(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson('/api/users', [
                'nom'         => 'Nouveau',
                'prenom'      => 'Utilisateur',
                'email'       => 'nouveau@canam.ml',
                'type_compte' => 'CANAM',
                'statut'      => 'ACTIF',
            ]);

        $response->assertStatus(201);
    }

    public function test_admin_can_assign_role_to_user(): void
    {
        $role = Role::create(['code' => 'TEST_ROLE', 'libelle' => 'Test Role']);
        $user = User::factory()->create(['statut' => 'ACTIF']);

        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson("/api/users/{$user->id}/roles", ['role_id' => $role->id]);

        $response->assertStatus(200);
    }

    public function test_admin_can_list_roles(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->getJson('/api/roles');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_role(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson('/api/roles', [
                'code'    => 'NEW_ROLE',
                'libelle' => 'Nouveau Rôle',
            ]);

        $response->assertStatus(201);
    }

    public function test_user_has_correct_permissions(): void
    {
        $this->assertTrue($this->admin->hasPermission('USERS_READ'));
        $this->assertTrue($this->admin->hasPermission('USERS_CREATE'));
        $this->assertTrue($this->lecteur->hasPermission('USERS_READ'));
        $this->assertFalse($this->lecteur->hasPermission('USERS_CREATE'));
    }
}
