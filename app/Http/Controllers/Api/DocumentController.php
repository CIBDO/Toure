<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Models\AuditLog;
use App\Models\Document;
use App\Services\GedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!$request->user()?->hasPermission('GED_READ')) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $query = Document::query()->with(['documentable', 'createdBy']);

        if ($request->filled('documentable_type')) {
            $class = GedService::documentableTypeToClass($request->documentable_type);
            if ($class) {
                $query->where('documentable_type', $class);
            }
        }
        if ($request->filled('documentable_id')) {
            $query->where('documentable_id', (int) $request->documentable_id);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('q')) {
            $q = '%' . $request->q . '%';
            $query->where(function ($qry) use ($q, $request) {
                $qry->where('title', 'ilike', $q)
                    ->orWhere('description', 'ilike', $q);
                if (DB::connection()->getDriverName() === 'pgsql') {
                    $qry->orWhereRaw('tags::text ilike ?', [$q]);
                }
            });
        }
        if ($request->filled('from')) {
            $query->whereDate('date_document', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('date_document', '<=', $request->to);
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        $documents = $query->latest()->paginate($perPage);

        return response()->json($documents);
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $user = $request->user();
        $typeKey = $request->validated('documentable_type');
        $documentableId = (int) $request->validated('documentable_id');
        $documentableClass = GedService::documentableTypeToClass($typeKey);
        $file = $request->file('file');

        if (! $documentableClass || $documentableClass::find($documentableId) === null) {
            return response()->json([
                'message' => 'Entité cible introuvable.',
                'errors' => ['documentable_id' => ['Entité cible introuvable.']],
            ], 422);
        }

        $document = new Document([
            'documentable_type' => $documentableClass,
            'documentable_id'   => $documentableId,
            'category'          => $request->validated('category'),
            'title'             => $request->validated('title'),
            'description'       => $request->validated('description'),
            'date_document'     => $request->validated('date_document'),
            'tags'              => $request->validated('tags'),
            'original_name'     => $file->getClientOriginalName(),
            'mime_type'         => $file->getMimeType(),
            'size'              => $file->getSize(),
            'checksum'          => hash_file('sha256', $file->getRealPath()),
            'is_private'        => true,
            'created_by'        => $user->id,
        ]);
        $document->uuid = (string) Str::uuid();
        $path = GedService::storeFile($document, $file);
        $document->file_path = $path;
        $document->save();

        AuditLog::logChange(
            'DOCUMENT_CREATE',
            Document::class,
            $document->id,
            null,
            $document->only(['title', 'category', 'documentable_type', 'documentable_id', 'file_path']),
            null,
            null,
            $user->id
        );

        return response()->json($document->load(['documentable', 'createdBy']), 201);
    }

    public function show(Request $request, Document $document): JsonResponse
    {
        if (!$request->user()?->hasPermission('GED_READ')) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        $document->load(['documentable', 'createdBy', 'updatedBy']);
        return response()->json($document);
    }

    public function update(UpdateDocumentRequest $request, Document $document): JsonResponse
    {
        $user = $request->user();
        $old = $document->only(['title', 'category', 'description', 'date_document', 'tags']);
        $document->fill($request->validated());
        $document->updated_by = $user->id;
        $document->save();

        AuditLog::logChange(
            'DOCUMENT_UPDATE',
            Document::class,
            $document->id,
            $old,
            $document->only(['title', 'category', 'description', 'date_document', 'tags']),
            null,
            null,
            $user->id
        );

        return response()->json($document->load(['documentable', 'createdBy', 'updatedBy']));
    }

    public function destroy(Request $request, Document $document): JsonResponse
    {
        if (!$request->user()?->hasPermission('GED_DELETE')) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        $user = $request->user();
        $old = $document->only(['title', 'file_path']);
        GedService::deleteFile($document->file_path);
        $document->delete();

        AuditLog::logChange(
            'DOCUMENT_DELETE',
            Document::class,
            $document->id,
            $old,
            null,
            null,
            null,
            $user->id
        );

        return response()->json(['message' => 'Document supprimé']);
    }

    public function download(Request $request, Document $document): StreamedResponse|JsonResponse
    {
        if (!$request->user()?->hasPermission('GED_DOWNLOAD')) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        if (!Storage::disk('local')->exists($document->file_path)) {
            return response()->json(['message' => 'Fichier introuvable'], 404);
        }

        AuditLog::log(
            'DOCUMENT_DOWNLOAD',
            Document::class,
            $document->id,
            ['title' => $document->title],
            $request->user()->id
        );

        return Storage::disk('local')->download(
            $document->file_path,
            $document->original_name,
            ['Content-Type' => $document->mime_type]
        );
    }

    public function preview(Request $request, Document $document): StreamedResponse|JsonResponse
    {
        if (!$request->user()?->hasPermission('GED_READ')) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        if (!Storage::disk('local')->exists($document->file_path)) {
            return response()->json(['message' => 'Fichier introuvable'], 404);
        }

        AuditLog::log(
            'DOCUMENT_PREVIEW',
            Document::class,
            $document->id,
            ['title' => $document->title],
            $request->user()->id
        );

        $stream = Storage::disk('local')->readStream($document->file_path);
        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type'        => $document->mime_type,
            'Content-Disposition' => 'inline; filename="' . $document->original_name . '"',
        ]);
    }
}
