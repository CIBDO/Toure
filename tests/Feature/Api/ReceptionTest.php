<?php

namespace Tests\Feature\Api;

use App\Models\Contrat;
use App\Models\Fournisseur;
use App\Models\Permission;
use App\Models\Reception;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReceptionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    private Contrat $contrat;

    protected function setUp(): void
    {
        parent::setUp();

        $perms = [
            'RECEPTION_READ', 'RECEPTION_CREATE', 'RECEPTION_EDIT', 'RECEPTION_DELETE',
            'RECEPTION_SUBMIT', 'RECEPTION_APPROVE',
        ];
        foreach ($perms as $code) {
            Permission::firstOrCreate(['code' => $code], ['libelle' => $code]);
        }

        $role = Role::create(['code' => 'RECEPTION_MANAGER', 'libelle' => 'Gestionnaire réceptions']);
        $role->permissions()->sync(Permission::whereIn('code', $perms)->pluck('id'));

        $this->user = User::factory()->create(['statut' => 'ACTIF', 'type_compte' => 'CANAM']);
        $this->user->roles()->attach($role);
        $this->token = $this->user->createToken('test')->plainTextToken;

        $fournisseur = Fournisseur::create([
            'uuid' => Str::uuid(),
            'code' => 'F-REC-001',
            'raison_sociale' => 'Fournisseur Réception Test',
            'statut' => 'actif',
        ]);

        $this->contrat = Contrat::create([
            'uuid' => Str::uuid(),
            'reference' => 'CONT/REC/2026/001',
            'objet' => 'Contrat pour tests réceptions',
            'fournisseur_id' => $fournisseur->id,
            'montant_initial' => 50000000,
            'montant_actuel' => 50000000,
            'devise' => 'GNF',
            'exercice' => '2026',
            'statut' => 'approved',
            'date_fin' => now()->addMonths(6),
            'created_by' => $this->user->id,
        ]);
    }

    private function authHeader(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_can_create_reception_provisoire(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/contrats/{$this->contrat->id}/receptions", [
                'type_reception' => 'provisoire',
                'date_reception' => now()->toDateString(),
                'lieu_reception' => 'Entrepôt principal',
                'statut_conformite' => 'conforme',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['numero' => 'R1'])
            ->assertJsonFragment(['statut' => 'draft'])
            ->assertJsonFragment(['type_reception' => 'provisoire']);

        $this->assertDatabaseHas('receptions', [
            'contrat_id' => $this->contrat->id,
            'numero' => 'R1',
            'type_reception' => 'provisoire',
        ]);
    }

    public function test_approve_provisoire_updates_contract_status_execution(): void
    {
        $reception = Reception::create([
            'uuid' => Str::uuid(),
            'contrat_id' => $this->contrat->id,
            'numero' => 'R1',
            'type_reception' => 'provisoire',
            'date_reception' => now(),
            'statut' => 'submitted',
            'statut_conformite' => 'conforme',
            'created_by' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/receptions/{$reception->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('statut', 'approved');

        $this->contrat->refresh();
        $this->assertEquals('reception_provisoire', $this->contrat->status_execution);
    }

    public function test_block_definitive_without_provisoire_approved(): void
    {
        $reception = Reception::create([
            'uuid' => Str::uuid(),
            'contrat_id' => $this->contrat->id,
            'numero' => 'R1',
            'type_reception' => 'definitive',
            'date_reception' => now(),
            'statut' => 'submitted',
            'statut_conformite' => 'conforme',
            'created_by' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/receptions/{$reception->id}/approve");

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Une réception définitive ne peut être approuvée qu\'après au moins une réception provisoire approuvée (ou avec permission dérogatoire).']);
    }

    public function test_approve_definitive_after_provisoire_sets_contract_cloturable(): void
    {
        Reception::create([
            'uuid' => Str::uuid(),
            'contrat_id' => $this->contrat->id,
            'numero' => 'R1',
            'type_reception' => 'provisoire',
            'date_reception' => now()->subDays(10),
            'statut' => 'approved',
            'statut_conformite' => 'conforme',
            'approved_by' => $this->user->id,
            'approved_at' => now(),
            'created_by' => $this->user->id,
        ]);

        $receptionDef = Reception::create([
            'uuid' => Str::uuid(),
            'contrat_id' => $this->contrat->id,
            'numero' => 'R2',
            'type_reception' => 'definitive',
            'date_reception' => now(),
            'statut' => 'submitted',
            'statut_conformite' => 'conforme',
            'created_by' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/receptions/{$receptionDef->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('statut', 'approved');

        $this->contrat->refresh();
        $this->assertEquals('reception_definitive', $this->contrat->status_execution);
        $this->assertTrue((bool) $this->contrat->cloturable);
    }

    public function test_cannot_create_reception_on_archived_contract(): void
    {
        $this->contrat->update(['statut' => 'archived']);

        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/contrats/{$this->contrat->id}/receptions", [
                'type_reception' => 'provisoire',
                'date_reception' => now()->toDateString(),
            ]);

        $response->assertStatus(403);
    }

    public function test_can_submit_and_reject_reception(): void
    {
        $reception = Reception::create([
            'uuid' => Str::uuid(),
            'contrat_id' => $this->contrat->id,
            'numero' => 'R1',
            'type_reception' => 'partielle',
            'date_reception' => now(),
            'statut' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $this->withHeaders($this->authHeader())
            ->postJson("/api/receptions/{$reception->id}/submit")
            ->assertStatus(200);

        $reception->refresh();
        $this->assertEquals('submitted', $reception->statut);

        $this->withHeaders($this->authHeader())
            ->postJson("/api/receptions/{$reception->id}/reject", [
                'commentaire_validation' => 'Non conforme au cahier des charges',
            ])
            ->assertStatus(200);

        $reception->refresh();
        $this->assertEquals('rejected', $reception->statut);
        $this->assertEquals('Non conforme au cahier des charges', $reception->commentaire_validation);
    }

    public function test_can_list_receptions_by_contrat(): void
    {
        Reception::create([
            'uuid' => Str::uuid(),
            'contrat_id' => $this->contrat->id,
            'numero' => 'R1',
            'type_reception' => 'provisoire',
            'date_reception' => now(),
            'statut' => 'approved',
            'created_by' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/contrats/{$this->contrat->id}/receptions");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
