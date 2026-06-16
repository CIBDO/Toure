<?php

namespace Tests\Feature\Api;

use App\Models\Avenant;
use App\Models\Contrat;
use App\Models\Fournisseur;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AvenantTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    private Contrat $contrat;

    protected function setUp(): void
    {
        parent::setUp();

        $perms = ['AVENANTS_READ', 'AVENANTS_CREATE', 'AVENANTS_EDIT', 'AVENANTS_DELETE', 'AVENANTS_SUBMIT', 'AVENANTS_APPROVE'];
        foreach ($perms as $code) {
            Permission::firstOrCreate(['code' => $code], ['libelle' => $code]);
        }

        $role = Role::create(['code' => 'AVENANT_MANAGER', 'libelle' => 'Gestionnaire avenants']);
        $role->permissions()->sync(Permission::whereIn('code', $perms)->pluck('id'));

        $this->user = User::factory()->create(['statut' => 'ACTIF', 'type_compte' => 'CANAM']);
        $this->user->roles()->attach($role);
        $this->token = $this->user->createToken('test')->plainTextToken;

        $fournisseur = Fournisseur::create([
            'uuid' => Str::uuid(),
            'code' => 'F-AV-001',
            'raison_sociale' => 'Fournisseur Avenant Test',
            'statut' => 'actif',
        ]);

        $this->contrat = Contrat::create([
            'uuid' => Str::uuid(),
            'reference' => 'CONT/AV/2026/001',
            'objet' => 'Contrat pour tests avenants',
            'fournisseur_id' => $fournisseur->id,
            'montant_initial' => 100000000,
            'montant_actuel' => 100000000,
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

    public function test_can_create_avenant(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/contrats/{$this->contrat->id}/avenants", [
                'type_avenant' => 'montant',
                'montant_variation' => 20000000,
                'justification' => 'Ajustement budget validé',
                'date_signature' => now()->toDateString(),
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['numero' => 'A1'])
            ->assertJsonFragment(['statut' => 'draft']);

        $this->assertDatabaseHas('avenants', [
            'contrat_id' => $this->contrat->id,
            'numero' => 'A1',
            'ancien_montant' => 100000000,
            'nouveau_montant' => 120000000,
        ]);
    }

    public function test_approve_avenant_updates_contract(): void
    {
        $avenant = Avenant::create([
            'uuid' => Str::uuid(),
            'contrat_id' => $this->contrat->id,
            'numero' => 'A1',
            'type_avenant' => 'montant',
            'montant_variation' => 15000000,
            'ancien_montant' => 100000000,
            'nouveau_montant' => 115000000,
            'justification' => 'Extension périmètre',
            'date_signature' => now(),
            'statut' => 'submitted',
            'created_by' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/avenants/{$avenant->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('avenant.statut', 'approved');

        $this->contrat->refresh();
        $this->assertEquals(115000000, (int) $this->contrat->montant_initial);
        $this->assertEquals(115000000, (int) $this->contrat->montant_actuel);
    }

    public function test_block_double_pending_avenant(): void
    {
        Avenant::create([
            'uuid' => Str::uuid(),
            'contrat_id' => $this->contrat->id,
            'numero' => 'A1',
            'type_avenant' => 'montant',
            'montant_variation' => 10000000,
            'ancien_montant' => 100000000,
            'nouveau_montant' => 110000000,
            'justification' => 'Premier avenant',
            'date_signature' => now(),
            'statut' => 'submitted',
            'created_by' => $this->user->id,
        ]);

        $a2 = Avenant::create([
            'uuid' => Str::uuid(),
            'contrat_id' => $this->contrat->id,
            'numero' => 'A2',
            'type_avenant' => 'montant',
            'montant_variation' => 5000000,
            'ancien_montant' => 100000000,
            'nouveau_montant' => 105000000,
            'justification' => 'Deuxième avenant',
            'date_signature' => now(),
            'statut' => 'submitted',
            'created_by' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/avenants/{$a2->id}/approve");

        $response->assertStatus(422);
        $this->contrat->refresh();
        $this->assertEquals(100000000, (int) $this->contrat->montant_initial);
    }

    public function test_cannot_approve_avenant_on_archived_contract(): void
    {
        $this->contrat->update(['statut' => 'archived']);

        $avenant = Avenant::create([
            'uuid' => Str::uuid(),
            'contrat_id' => $this->contrat->id,
            'numero' => 'A1',
            'type_avenant' => 'montant',
            'montant_variation' => 10000000,
            'ancien_montant' => 100000000,
            'nouveau_montant' => 110000000,
            'justification' => 'Avenant sur contrat archivé',
            'date_signature' => now(),
            'statut' => 'submitted',
            'created_by' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/avenants/{$avenant->id}/approve");

        $response->assertStatus(422);
    }

    public function test_can_list_avenants_by_contrat(): void
    {
        Avenant::create([
            'uuid' => Str::uuid(),
            'contrat_id' => $this->contrat->id,
            'numero' => 'A1',
            'type_avenant' => 'montant',
            'montant_variation' => 10000000,
            'ancien_montant' => 100000000,
            'nouveau_montant' => 110000000,
            'justification' => 'J1',
            'date_signature' => now(),
            'statut' => 'approved',
            'created_by' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/contrats/{$this->contrat->id}/avenants");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_submit_and_reject_avenant(): void
    {
        $avenant = Avenant::create([
            'uuid' => Str::uuid(),
            'contrat_id' => $this->contrat->id,
            'numero' => 'A1',
            'type_avenant' => 'objet',
            'ancien_montant' => 100000000,
            'nouveau_montant' => 100000000,
            'nouvelle_description_objet' => 'Nouvel objet',
            'justification' => 'Mise à jour objet',
            'date_signature' => now(),
            'statut' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $this->withHeaders($this->authHeader())
            ->postJson("/api/avenants/{$avenant->id}/submit")
            ->assertStatus(200);

        $avenant->refresh();
        $this->assertEquals('submitted', $avenant->statut);

        $this->withHeaders($this->authHeader())
            ->postJson("/api/avenants/{$avenant->id}/reject", [
                'commentaire_validation' => 'Refusé après examen',
            ])
            ->assertStatus(200);

        $avenant->refresh();
        $this->assertEquals('rejected', $avenant->statut);
        $this->assertEquals('Refusé après examen', $avenant->commentaire_validation);
    }
}
