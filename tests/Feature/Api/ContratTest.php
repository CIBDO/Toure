<?php

namespace Tests\Feature\Api;

use App\Models\Avis;
use App\Models\Contrat;
use App\Models\ContratEtape;
use App\Models\Fournisseur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContratTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;
    private Fournisseur $fournisseur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['statut' => 'ACTIF', 'type_compte' => 'CANAM']);
        $this->token = $this->user->createToken('test')->plainTextToken;

        $this->fournisseur = Fournisseur::create([
            'uuid'           => Str::uuid(),
            'code'           => 'F-CONTRAT-001',
            'raison_sociale' => 'Fournisseur Test SARL',
            'statut'         => 'actif',
        ]);
    }

    private function authHeader(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_can_list_contrats(): void
    {
        $response = $this->withHeaders($this->authHeader())->getJson('/api/contrats');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'total']);
    }

    public function test_can_create_contrat(): void
    {
        $response = $this->withHeaders($this->authHeader())->postJson('/api/contrats', [
            'reference'      => 'CONT/CANAM/2026/TEST-001',
            'objet'          => 'Contrat de test',
            'fournisseur_id' => $this->fournisseur->id,
            'montant_initial'=> 100000000,
            'devise'         => 'GNF',
            'exercice'       => '2026',
            'statut'         => 'draft',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['reference' => 'CONT/CANAM/2026/TEST-001']);

        $this->assertDatabaseHas('contrats', ['reference' => 'CONT/CANAM/2026/TEST-001']);
    }

    public function test_creating_contrat_auto_creates_etapes(): void
    {
        $this->withHeaders($this->authHeader())->postJson('/api/contrats', [
            'reference'      => 'CONT/CANAM/2026/TEST-002',
            'objet'          => 'Contrat avec étapes',
            'fournisseur_id' => $this->fournisseur->id,
            'montant_initial'=> 50000000,
            'exercice'       => '2026',
        ]);

        $contrat = Contrat::where('reference', 'CONT/CANAM/2026/TEST-002')->first();
        $this->assertNotNull($contrat);
        $this->assertCount(5, $contrat->etapes);

        $etapeTypes = $contrat->etapes->pluck('type_etape')->toArray();
        $this->assertContains('elaboration', $etapeTypes);
        $this->assertContains('engagement', $etapeTypes);
        $this->assertContains('oem', $etapeTypes);
        $this->assertContains('mandat', $etapeTypes);
        $this->assertContains('paie', $etapeTypes);
    }

    public function test_can_update_contrat(): void
    {
        $contrat = Contrat::create([
            'uuid'           => Str::uuid(),
            'reference'      => 'CONT/CANAM/2026/TEST-003',
            'objet'          => 'Objet initial',
            'fournisseur_id' => $this->fournisseur->id,
            'montant_initial'=> 100000000,
            'montant_actuel' => 100000000,
            'devise'         => 'GNF',
            'exercice'       => '2026',
            'statut'         => 'draft',
            'created_by'     => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->putJson("/api/contrats/{$contrat->id}", [
                'reference'      => 'CONT/CANAM/2026/TEST-003',
                'objet'          => 'Objet modifié',
                'fournisseur_id' => $this->fournisseur->id,
                'montant_initial'=> 120000000,
                'exercice'       => '2026',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['objet' => 'Objet modifié']);
    }

    public function test_can_approve_contrat(): void
    {
        $contrat = Contrat::create([
            'uuid'           => Str::uuid(),
            'reference'      => 'CONT/CANAM/2026/TEST-004',
            'objet'          => 'À approuver',
            'fournisseur_id' => $this->fournisseur->id,
            'montant_initial'=> 100000000,
            'montant_actuel' => 100000000,
            'devise'         => 'GNF',
            'exercice'       => '2026',
            'statut'         => 'submitted',
            'created_by'     => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/contrats/{$contrat->id}/approve");

        $response->assertStatus(200)
            ->assertJsonFragment(['statut' => 'approved']);
    }

    public function test_can_update_contrat_etape(): void
    {
        $contrat = Contrat::create([
            'uuid'           => Str::uuid(),
            'reference'      => 'CONT/CANAM/2026/TEST-005',
            'objet'          => 'Contrat avec étapes',
            'fournisseur_id' => $this->fournisseur->id,
            'montant_initial'=> 100000000,
            'montant_actuel' => 100000000,
            'devise'         => 'GNF',
            'exercice'       => '2026',
            'statut'         => 'approved',
            'created_by'     => $this->user->id,
        ]);

        $etape = ContratEtape::create([
            'contrat_id' => $contrat->id,
            'type_etape' => 'elaboration',
            'statut'     => 'pending',
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->putJson("/api/contrats/{$contrat->id}/etapes/{$etape->id}", [
                'statut'         => 'completed',
                'date_effective' => now()->toDateString(),
                'commentaire'    => 'Élaboration terminée',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['statut' => 'completed']);
    }

    public function test_can_delete_contrat(): void
    {
        $contrat = Contrat::create([
            'uuid'           => Str::uuid(),
            'reference'      => 'CONT/CANAM/2026/TEST-006',
            'objet'          => 'À supprimer',
            'fournisseur_id' => $this->fournisseur->id,
            'montant_initial'=> 100000000,
            'montant_actuel' => 100000000,
            'devise'         => 'GNF',
            'exercice'       => '2026',
            'statut'         => 'draft',
            'created_by'     => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/contrats/{$contrat->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('contrats', ['id' => $contrat->id]);
    }

    public function test_can_filter_contrats_by_statut(): void
    {
        Contrat::create(['uuid' => Str::uuid(), 'reference' => 'C-DRAFT', 'objet' => 'Draft', 'fournisseur_id' => $this->fournisseur->id, 'montant_initial' => 1000, 'montant_actuel' => 1000, 'devise' => 'GNF', 'exercice' => '2026', 'statut' => 'draft']);
        Contrat::create(['uuid' => Str::uuid(), 'reference' => 'C-APPROVED', 'objet' => 'Approved', 'fournisseur_id' => $this->fournisseur->id, 'montant_initial' => 1000, 'montant_actuel' => 1000, 'devise' => 'GNF', 'exercice' => '2026', 'statut' => 'approved']);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/contrats?statut=approved');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('approved', $data[0]['statut']);
    }
}
