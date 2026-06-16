<?php

namespace Tests\Feature\Api;

use App\Models\Contrat;
use App\Models\Document;
use App\Models\Fournisseur;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    private User $userWithGed;
    private User $userWithoutGed;
    private string $tokenWithGed;
    private string $tokenWithoutGed;
    private Contrat $contrat;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $gedPermissions = ['GED_READ', 'GED_EDIT', 'GED_UPLOAD', 'GED_DOWNLOAD', 'GED_DELETE'];
        foreach ($gedPermissions as $code) {
            Permission::firstOrCreate(['code' => $code], ['libelle' => $code]);
        }
        $roleGed = Role::create(['code' => 'AGENT_GED', 'libelle' => 'Agent GED']);
        $roleGed->permissions()->sync(Permission::whereIn('code', $gedPermissions)->pluck('id'));

        $roleLecteur = Role::create(['code' => 'LECTEUR', 'libelle' => 'Lecteur']);
        $roleLecteur->permissions()->sync(Permission::where('code', 'GED_READ')->pluck('id'));

        $this->userWithGed = User::factory()->create(['statut' => 'ACTIF', 'type_compte' => 'CANAM']);
        $this->userWithGed->roles()->attach($roleGed);
        $this->tokenWithGed = $this->userWithGed->createToken('test')->plainTextToken;

        $this->userWithoutGed = User::factory()->create(['statut' => 'ACTIF', 'type_compte' => 'CANAM']);
        $this->userWithoutGed->roles()->attach($roleLecteur);

        $this->tokenWithoutGed = $this->userWithoutGed->createToken('test')->plainTextToken;

        $fournisseur = Fournisseur::create([
            'uuid' => Str::uuid(),
            'code' => 'F-001',
            'raison_sociale' => 'Fournisseur Test',
            'statut' => 'actif',
        ]);
        $this->contrat = Contrat::create([
            'uuid' => Str::uuid(),
            'reference' => 'CONT/TEST/001',
            'objet' => 'Objet test',
            'fournisseur_id' => $fournisseur->id,
            'montant_initial' => 1000000,
            'montant_actuel' => 1000000,
            'devise' => 'GNF',
            'exercice' => '2026',
            'statut' => 'draft',
            'created_by' => $this->userWithGed->id,
        ]);
    }

    private function authHeader(string $token): array
    {
        return ['Authorization' => "Bearer {$token}"];
    }

    public function test_list_documents_authorized(): void
    {
        $response = $this->withHeaders($this->authHeader($this->tokenWithGed))
            ->getJson('/api/documents');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'current_page']);
    }

    public function test_list_documents_unauthorized_without_ged_read(): void
    {
        $userNoPerm = User::factory()->create(['statut' => 'ACTIF']);
        $userNoPerm->roles()->detach();
        $token = $userNoPerm->createToken('test')->plainTextToken;

        $response = $this->withHeaders($this->authHeader($token))
            ->getJson('/api/documents');

        $response->assertStatus(403);
    }

    public function test_upload_document_authorized(): void
    {
        $file = UploadedFile::fake()->create('contrat.pdf', 100, 'application/pdf');

        $response = $this->withHeaders($this->authHeader($this->tokenWithGed))
            ->post('/api/documents', [
                'documentable_type' => 'contrats',
                'documentable_id' => $this->contrat->id,
                'category' => 'contrat_signe',
                'title' => 'Contrat signé test',
                'file' => $file,
            ], ['Accept' => 'application/json']);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'Contrat signé test']);
        $this->assertDatabaseHas('documents', ['title' => 'Contrat signé test']);
    }

    public function test_upload_document_unauthorized(): void
    {
        $file = UploadedFile::fake()->create('contrat.pdf', 100, 'application/pdf');

        $response = $this->withHeaders($this->authHeader($this->tokenWithoutGed))
            ->post('/api/documents', [
                'documentable_type' => 'contrats',
                'documentable_id' => $this->contrat->id,
                'category' => 'contrat_signe',
                'title' => 'Contrat signé',
                'file' => $file,
            ], ['Accept' => 'application/json']);

        $response->assertStatus(403);
    }

    public function test_download_authorized(): void
    {
        $doc = Document::create([
            'uuid' => Str::uuid(),
            'documentable_type' => Contrat::class,
            'documentable_id' => $this->contrat->id,
            'category' => 'contrat_signe',
            'title' => 'Doc test',
            'file_path' => 'ged/contrat/1/test.pdf',
            'original_name' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'created_by' => $this->userWithGed->id,
        ]);
        Storage::disk('local')->put($doc->file_path, 'fake pdf content');

        $response = $this->withHeaders($this->authHeader($this->tokenWithGed))
            ->get("/api/documents/{$doc->id}/download");

        $response->assertStatus(200);
    }

    public function test_download_unauthorized(): void
    {
        $userNoDownload = User::factory()->create(['statut' => 'ACTIF']);
        $role = Role::create(['code' => 'R', 'libelle' => 'R']);
        $role->permissions()->sync(Permission::where('code', 'GED_READ')->pluck('id'));
        $userNoDownload->roles()->attach($role);
        $token = $userNoDownload->createToken('test')->plainTextToken;

        $doc = Document::create([
            'uuid' => Str::uuid(),
            'documentable_type' => Contrat::class,
            'documentable_id' => $this->contrat->id,
            'category' => 'contrat_signe',
            'title' => 'Doc',
            'file_path' => 'ged/contrat/1/doc.pdf',
            'original_name' => 'doc.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'created_by' => $this->userWithGed->id,
        ]);
        Storage::disk('local')->put($doc->file_path, 'content');

        $response = $this->withHeaders($this->authHeader($token))
            ->get("/api/documents/{$doc->id}/download");

        $response->assertStatus(403);
    }

    public function test_soft_delete_document(): void
    {
        $doc = Document::create([
            'uuid' => Str::uuid(),
            'documentable_type' => Contrat::class,
            'documentable_id' => $this->contrat->id,
            'category' => 'autres',
            'title' => 'À supprimer',
            'file_path' => 'ged/contrat/1/del.pdf',
            'original_name' => 'del.pdf',
            'mime_type' => 'application/pdf',
            'size' => 50,
            'created_by' => $this->userWithGed->id,
        ]);
        Storage::disk('local')->put($doc->file_path, 'x');

        $response = $this->withHeaders($this->authHeader($this->tokenWithGed))
            ->deleteJson("/api/documents/{$doc->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('documents', ['id' => $doc->id]);
    }

    public function test_filter_documents_by_entity_and_category(): void
    {
        Document::create([
            'uuid' => Str::uuid(),
            'documentable_type' => Contrat::class,
            'documentable_id' => $this->contrat->id,
            'category' => 'contrat_signe',
            'title' => 'Contrat A',
            'file_path' => 'ged/contrat/1/a.pdf',
            'original_name' => 'a.pdf',
            'mime_type' => 'application/pdf',
            'size' => 10,
            'created_by' => $this->userWithGed->id,
        ]);
        Document::create([
            'uuid' => Str::uuid(),
            'documentable_type' => Contrat::class,
            'documentable_id' => $this->contrat->id,
            'category' => 'facture',
            'title' => 'Facture B',
            'file_path' => 'ged/contrat/1/b.pdf',
            'original_name' => 'b.pdf',
            'mime_type' => 'application/pdf',
            'size' => 10,
            'created_by' => $this->userWithGed->id,
        ]);

        $response = $this->withHeaders($this->authHeader($this->tokenWithGed))
            ->getJson('/api/documents?documentable_type=contrats&documentable_id=' . $this->contrat->id . '&category=contrat_signe');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('contrat_signe', $data[0]['category']);
    }
}
