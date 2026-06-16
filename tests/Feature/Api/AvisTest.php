<?php

namespace Tests\Feature\Api;

use App\Models\Avis;
use App\Models\Fournisseur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvisTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['statut' => 'ACTIF', 'type_compte' => 'CANAM']);
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    private function authHeader(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_can_list_avis(): void
    {
        Avis::create([
            'uuid'           => \Illuminate\Support\Str::uuid(),
            'reference'      => 'CANAM/AO/2026/TEST-001',
            'objet'          => 'Test avis',
            'mode_passation' => 'AO_OUVERT',
            'exercice'       => '2026',
            'statut'         => 'draft',
            'created_by'     => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeader())->getJson('/api/avis');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'total']);
    }

    public function test_can_create_avis(): void
    {
        $response = $this->withHeaders($this->authHeader())->postJson('/api/avis', [
            'reference'           => 'CANAM/AO/2026/TEST-002',
            'objet'               => 'Acquisition de matériel de bureau',
            'mode_passation'      => 'CONSULTATION',
            'exercice'            => '2026',
            'duree'               => 15,
            'date_limite_depot'   => '2026-07-15',
            'date_ouverture_plis' => '2026-07-17',
            'date_publication'    => '2026-06-01',
            'statut'              => 'draft',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['reference' => 'CANAM/AO/2026/TEST-002']);

        $this->assertDatabaseHas('avis', ['reference' => 'CANAM/AO/2026/TEST-002']);
    }

    public function test_can_create_avis_with_items(): void
    {
        $response = $this->withHeaders($this->authHeader())->postJson('/api/avis', [
            'reference'           => 'CANAM/AO/2026/TEST-003',
            'objet'               => 'Avis avec lignes',
            'mode_passation'      => 'AO_OUVERT',
            'exercice'            => '2026',
            'duree'               => 30,
            'date_limite_depot'   => '2026-08-01',
            'date_ouverture_plis' => '2026-08-03',
            'date_publication'    => '2026-06-15',
            'items'               => [
                ['designation' => 'Ordinateurs portables', 'quantite' => 10, 'unite' => 'unité'],
                ['designation' => 'Imprimantes', 'quantite' => 3, 'unite' => 'unité'],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('avis_items', 2);
    }

    public function test_can_update_avis(): void
    {
        $avis = Avis::create([
            'uuid'           => \Illuminate\Support\Str::uuid(),
            'reference'      => 'CANAM/AO/2026/TEST-004',
            'objet'          => 'Objet initial',
            'mode_passation' => 'AO_OUVERT',
            'exercice'       => '2026',
            'statut'         => 'draft',
            'created_by'     => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->putJson("/api/avis/{$avis->id}", [
                'reference'           => 'CANAM/AO/2026/TEST-004',
                'objet'               => 'Objet modifié',
                'mode_passation'      => 'AO_OUVERT',
                'exercice'            => '2026',
                'duree'               => 20,
                'date_limite_depot'   => '2026-09-01',
                'date_ouverture_plis' => '2026-09-03',
                'date_publication'    => '2026-06-20',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['objet' => 'Objet modifié']);
    }

    public function test_can_delete_avis(): void
    {
        $avis = Avis::create([
            'uuid'           => \Illuminate\Support\Str::uuid(),
            'reference'      => 'CANAM/AO/2026/TEST-005',
            'objet'          => 'À supprimer',
            'mode_passation' => 'AO_OUVERT',
            'exercice'       => '2026',
            'statut'         => 'draft',
            'created_by'     => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/avis/{$avis->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('avis', ['id' => $avis->id]);
    }

    public function test_can_publish_avis(): void
    {
        $avis = Avis::create([
            'uuid'           => \Illuminate\Support\Str::uuid(),
            'reference'      => 'CANAM/AO/2026/TEST-006',
            'objet'          => 'À publier',
            'mode_passation' => 'AO_OUVERT',
            'exercice'       => '2026',
            'statut'         => 'draft',
            'created_by'     => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/avis/{$avis->id}/publish");

        $response->assertStatus(200)
            ->assertJsonFragment(['statut' => 'published']);
    }

    public function test_avis_reference_must_be_unique(): void
    {
        Avis::create([
            'uuid'           => \Illuminate\Support\Str::uuid(),
            'reference'      => 'CANAM/AO/2026/UNIQUE',
            'objet'          => 'Premier',
            'mode_passation' => 'AO_OUVERT',
            'exercice'       => '2026',
            'statut'         => 'draft',
            'created_by'     => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeader())->postJson('/api/avis', [
            'reference'           => 'CANAM/AO/2026/UNIQUE',
            'objet'               => 'Doublon',
            'mode_passation'      => 'AO_OUVERT',
            'exercice'            => '2026',
            'duree'               => 10,
            'date_limite_depot'   => '2026-10-01',
            'date_ouverture_plis' => '2026-10-03',
            'date_publication'    => '2026-07-01',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reference']);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/avis');
        $response->assertStatus(401);
    }
}
