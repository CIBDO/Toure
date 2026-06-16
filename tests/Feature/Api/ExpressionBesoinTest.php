<?php

namespace Tests\Feature\Api;

use App\Models\DomaineActivite;
use App\Models\ExpressionBesoin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpressionBesoinTest extends TestCase
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

    public function test_can_list_expressions_besoin(): void
    {
        ExpressionBesoin::create([
            'uuid'    => \Illuminate\Support\Str::uuid(),
            'code'    => 'EB-TEST-001',
            'libelle' => 'Mobilier de bureau',
            'actif'   => true,
        ]);

        $response = $this->withHeaders($this->authHeader())->getJson('/api/expressions-besoin');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'total']);
    }

    public function test_can_create_expression_besoin(): void
    {
        $domaine = DomaineActivite::create([
            'uuid'    => \Illuminate\Support\Str::uuid(),
            'code'    => 'FOURNITURES',
            'libelle' => 'Fournitures',
        ]);

        $response = $this->withHeaders($this->authHeader())->postJson('/api/expressions-besoin', [
            'code'                => 'EB-TEST-002',
            'libelle'             => 'Ordinateurs portables',
            'description'         => 'Acquisition de PC portables',
            'unite_defaut'        => 'unité',
            'domaine_activite_id' => $domaine->id,
            'actif'               => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['code' => 'EB-TEST-002']);

        $this->assertDatabaseHas('expressions_besoin', ['code' => 'EB-TEST-002']);
    }

    public function test_cannot_delete_expression_used_in_avis_item(): void
    {
        $expression = ExpressionBesoin::create([
            'uuid'    => \Illuminate\Support\Str::uuid(),
            'code'    => 'EB-USED',
            'libelle' => 'Expression utilisée',
            'actif'   => true,
        ]);

        \App\Models\Avis::create([
            'uuid'                => \Illuminate\Support\Str::uuid(),
            'reference'           => 'AVIS-EXPR-001',
            'objet'               => 'Test',
            'mode_passation'      => 'AO_OUVERT',
            'exercice'            => '2026',
            'duree'               => 10,
            'date_limite_depot'   => '2026-08-01',
            'date_ouverture_plis' => '2026-08-03',
            'date_publication'    => '2026-07-01',
            'statut'              => 'draft',
            'created_by'          => $this->user->id,
        ]);

        $avis = \App\Models\Avis::first();
        \App\Models\AvisItem::create([
            'avis_id'              => $avis->id,
            'expression_besoin_id' => $expression->id,
            'ordre'                => 1,
            'designation'          => $expression->libelle,
            'quantite'             => 1,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/expressions-besoin/{$expression->id}");

        $response->assertStatus(422);
    }
}
