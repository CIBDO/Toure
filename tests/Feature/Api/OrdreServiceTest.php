<?php

namespace Tests\Feature\Api;

use App\Models\Contrat;
use App\Models\Fournisseur;
use App\Models\OrdreService;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrdreServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    private Contrat $contrat;

    protected function setUp(): void
    {
        parent::setUp();

        $perms = ['OS_READ', 'OS_CREATE', 'OS_EDIT', 'OS_DELETE', 'OS_SUBMIT', 'OS_APPROVE', 'OS_EXECUTE'];
        foreach ($perms as $code) {
            Permission::firstOrCreate(['code' => $code], ['libelle' => $code]);
        }

        $role = Role::create(['code' => 'OS_MANAGER', 'libelle' => 'Gestionnaire OS']);
        $role->permissions()->sync(Permission::whereIn('code', $perms)->pluck('id'));

        $this->user = User::factory()->create(['statut' => 'ACTIF', 'type_compte' => 'CANAM']);
        $this->user->roles()->attach($role);
        $this->token = $this->user->createToken('test')->plainTextToken;

        $fournisseur = Fournisseur::create([
            'uuid' => Str::uuid(),
            'code' => 'F-OS-001',
            'raison_sociale' => 'Fournisseur OS Test',
            'statut' => 'actif',
        ]);

        $this->contrat = Contrat::create([
            'uuid' => Str::uuid(),
            'reference' => 'CONT/OS/2026/001',
            'numero' => 'C-2026-001',
            'objet' => 'Contrat pour tests OS',
            'fournisseur_id' => $fournisseur->id,
            'montant_initial' => 50000000,
            'montant_actuel' => 50000000,
            'devise' => 'GNF',
            'exercice' => '2026',
            'statut' => 'approved',
            'date_debut' => now(),
            'date_fin' => now()->addMonths(3),
            'created_by' => $this->user->id,
        ]);
    }

    private function authHeader(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_can_create_ordre_service(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/contrats/{$this->contrat->id}/ordre-services", [
                'type_os' => 'demarrage',
                'objet' => 'Démarrage des travaux',
                'date_emission' => now()->toDateString(),
                'impact_delai' => 'none',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['statut' => 'draft'])
            ->assertJsonFragment(['type_os' => 'demarrage']);

        $this->assertDatabaseHas('ordre_services', [
            'contrat_id' => $this->contrat->id,
            'objet' => 'Démarrage des travaux',
            'statut' => 'draft',
        ]);
    }

    public function test_approve_os_extend_updates_contract_date_fin(): void
    {
        $oldDateFin = $this->contrat->date_fin->format('Y-m-d');

        $os = OrdreService::create([
            'uuid' => Str::uuid(),
            'contrat_id' => $this->contrat->id,
            'numero' => 'OS-2026-C-2026-001-001',
            'type_os' => 'modification',
            'objet' => 'Prolongation 15 jours',
            'date_emission' => now(),
            'impact_delai' => 'extend',
            'delai_jours' => 15,
            'statut' => 'submitted',
            'issued_by' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/ordre-services/{$os->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('statut', 'approved');

        $this->contrat->refresh();
        $expectedNewDate = \Carbon\Carbon::parse($oldDateFin)->addDays(15)->format('Y-m-d');
        $this->assertEquals($expectedNewDate, $this->contrat->date_fin->format('Y-m-d'));
    }

    public function test_reject_os(): void
    {
        $os = OrdreService::create([
            'uuid' => Str::uuid(),
            'contrat_id' => $this->contrat->id,
            'numero' => 'OS-2026-001',
            'type_os' => 'demarrage',
            'objet' => 'OS rejeté',
            'date_emission' => now(),
            'statut' => 'submitted',
            'issued_by' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/ordre-services/{$os->id}/reject", [
                'commentaire_validation' => 'Refusé',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('statut', 'rejected');

        $os->refresh();
        $this->assertEquals('rejected', $os->statut);
        $this->assertEquals('Refusé', $os->commentaire_validation);
    }

    public function test_execute_os(): void
    {
        $os = OrdreService::create([
            'uuid' => Str::uuid(),
            'contrat_id' => $this->contrat->id,
            'numero' => 'OS-2026-002',
            'type_os' => 'demarrage',
            'objet' => 'OS exécuté',
            'date_emission' => now(),
            'statut' => 'approved',
            'approved_by' => $this->user->id,
            'approved_at' => now(),
            'issued_by' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/ordre-services/{$os->id}/execute");

        $response->assertStatus(200)
            ->assertJsonPath('statut', 'executed');

        $os->refresh();
        $this->assertEquals('executed', $os->statut);
        $this->assertNotNull($os->executed_at);
    }

    public function test_cannot_approve_os_on_archived_contract(): void
    {
        $this->contrat->update(['statut' => 'archived']);

        $os = OrdreService::create([
            'uuid' => Str::uuid(),
            'contrat_id' => $this->contrat->id,
            'numero' => 'OS-2026-003',
            'type_os' => 'modification',
            'objet' => 'OS sur contrat archivé',
            'date_emission' => now(),
            'statut' => 'submitted',
            'issued_by' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/ordre-services/{$os->id}/approve");

        $response->assertStatus(422);
    }

    public function test_permissions_enforcement_list(): void
    {
        $roleNoOs = Role::create(['code' => 'NO_OS', 'libelle' => 'Sans OS']);
        $userNoOs = User::factory()->create(['statut' => 'ACTIF']);
        $userNoOs->roles()->attach($roleNoOs);
        $tokenNoOs = $userNoOs->createToken('test')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => "Bearer {$tokenNoOs}"])
            ->getJson("/api/contrats/{$this->contrat->id}/ordre-services");

        $response->assertStatus(403);
    }
}
