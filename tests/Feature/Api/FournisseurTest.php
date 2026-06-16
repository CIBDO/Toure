<?php

namespace Tests\Feature\Api;

use App\Models\Banque;
use App\Models\DomaineActivite;
use App\Models\Fournisseur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FournisseurTest extends TestCase
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

    public function test_can_list_fournisseurs(): void
    {
        Fournisseur::create([
            'uuid'           => \Illuminate\Support\Str::uuid(),
            'code'           => 'F-TEST-001',
            'raison_sociale' => 'Test SARL',
            'statut'         => 'actif',
            'created_by'     => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeader())->getJson('/api/fournisseurs');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'total']);
    }

    public function test_can_create_fournisseur(): void
    {
        $domaine = DomaineActivite::create([
            'uuid'    => \Illuminate\Support\Str::uuid(),
            'code'    => 'TRAVAUX',
            'libelle' => 'Travaux',
        ]);

        $response = $this->withHeaders($this->authHeader())->postJson('/api/fournisseurs', [
            'code'                => 'F-TEST-002',
            'raison_sociale'      => 'Nouveau Fournisseur SARL',
            'nif'                 => '123456789',
            'telephone'           => '+223 20 22 00 01',
                'email'               => 'contact@nouveau.ml',
                'ville'               => 'Bamako',
            'domaine_activite_id' => $domaine->id,
            'modes_passation'     => ['CONSULTATION', 'AO_OUVERT'],
            'statut'              => 'actif',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['code' => 'F-TEST-002']);

        $this->assertDatabaseHas('fournisseurs', ['code' => 'F-TEST-002']);
    }

    public function test_can_create_fournisseur_with_banques(): void
    {
        $banque = Banque::create([
            'uuid'    => \Illuminate\Support\Str::uuid(),
            'code'    => 'BDM',
            'libelle' => 'BDM',
        ]);

        $response = $this->withHeaders($this->authHeader())->postJson('/api/fournisseurs', [
            'code'           => 'F-TEST-003',
            'raison_sociale' => 'Fournisseur avec banque',
            'modes_passation'=> ['AO_OUVERT'],
            'statut'         => 'actif',
            'banques'        => [
                [
                    'banque_id'      => $banque->id,
                    'numero_compte'  => 'GN-0001-0000-0001234-56',
                    'principal'      => true,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('fournisseur_banques', ['numero_compte' => 'GN-0001-0000-0001234-56']);
    }

    public function test_can_update_fournisseur(): void
    {
        $fournisseur = Fournisseur::create([
            'uuid'           => \Illuminate\Support\Str::uuid(),
            'code'           => 'F-TEST-004',
            'raison_sociale' => 'Ancien nom',
            'statut'         => 'actif',
            'created_by'     => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->putJson("/api/fournisseurs/{$fournisseur->id}", [
                'code'           => 'F-TEST-004',
                'raison_sociale' => 'Nouveau nom SARL',
                'modes_passation'=> ['CONSULTATION'],
                'statut'         => 'actif',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['raison_sociale' => 'Nouveau nom SARL']);
    }

    public function test_can_delete_fournisseur(): void
    {
        $fournisseur = Fournisseur::create([
            'uuid'           => \Illuminate\Support\Str::uuid(),
            'code'           => 'F-TEST-005',
            'raison_sociale' => 'À supprimer',
            'statut'         => 'actif',
            'created_by'     => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/fournisseurs/{$fournisseur->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('fournisseurs', ['id' => $fournisseur->id]);
    }

    public function test_fournisseur_code_must_be_unique(): void
    {
        Fournisseur::create([
            'uuid'           => \Illuminate\Support\Str::uuid(),
            'code'           => 'F-UNIQUE',
            'raison_sociale' => 'Premier',
            'statut'         => 'actif',
        ]);

        $response = $this->withHeaders($this->authHeader())->postJson('/api/fournisseurs', [
            'code'           => 'F-UNIQUE',
            'raison_sociale' => 'Doublon',
            'modes_passation'=> ['AO_OUVERT'],
            'statut'         => 'actif',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_can_filter_fournisseurs_by_statut(): void
    {
        Fournisseur::create(['uuid' => \Illuminate\Support\Str::uuid(), 'code' => 'F-ACTIF', 'raison_sociale' => 'Actif', 'statut' => 'actif']);
        Fournisseur::create(['uuid' => \Illuminate\Support\Str::uuid(), 'code' => 'F-SUSP', 'raison_sociale' => 'Suspendu', 'statut' => 'suspendu']);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/fournisseurs?statut=actif');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('actif', $data[0]['statut']);
    }

    public function test_can_filter_fournisseurs_by_mode_and_duree(): void
    {
        Fournisseur::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'code' => 'F-MODE-1',
            'raison_sociale' => 'Consultation only',
            'modes_passation' => ['CONSULTATION'],
            'duree_min' => 10,
            'duree_max' => 30,
            'statut' => 'actif',
        ]);
        Fournisseur::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'code' => 'F-MODE-2',
            'raison_sociale' => 'AO only',
            'modes_passation' => ['AO_OUVERT'],
            'duree_min' => 20,
            'duree_max' => 60,
            'statut' => 'actif',
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/fournisseurs?mode_passation=CONSULTATION&duree=15&itemsPerPage=-1');

        $response->assertStatus(200);
        $codes = collect($response->json('data'))->pluck('code')->all();
        $this->assertContains('F-MODE-1', $codes);
        $this->assertNotContains('F-MODE-2', $codes);
    }
}
