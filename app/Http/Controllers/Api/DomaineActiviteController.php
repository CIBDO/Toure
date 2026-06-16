<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DomaineActiviteRequest;
use App\Models\AuditLog;
use App\Models\DomaineActivite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DomaineActiviteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DomaineActivite::query();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($query) use ($q) {
                $query->where('code', 'like', "%{$q}%")
                    ->orWhere('libelle', 'like', "%{$q}%");
            });
        }

        if ($request->filled('actif')) {
            $query->where('actif', filter_var($request->actif, FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy = $request->get('sortBy', 'libelle');
        $sortOrder = $request->get('sortDesc', false) ? 'desc' : 'asc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = (int) $request->get('itemsPerPage', 10);
        if ($perPage === -1) {
            return response()->json(['data' => $query->get(), 'total' => $query->count()]);
        }

        $paginated = $query->paginate($perPage);

        return response()->json([
            'data'  => $paginated->items(),
            'total' => $paginated->total(),
        ]);
    }

    public function store(DomaineActiviteRequest $request): JsonResponse
    {
        $domaine = DomaineActivite::create(array_merge(
            $request->validated(),
            ['created_by' => auth()->id()]
        ));

        AuditLog::logAction('create', 'domaines_activite', $domaine->id, null, $domaine->toArray());

        return response()->json($domaine, 201);
    }

    public function show(DomaineActivite $domaine): JsonResponse
    {
        return response()->json($domaine);
    }

    public function update(DomaineActiviteRequest $request, DomaineActivite $domaine): JsonResponse
    {
        $old = $domaine->toArray();
        $domaine->update($request->validated());

        AuditLog::logAction('update', 'domaines_activite', $domaine->id, $old, $domaine->fresh()->toArray());

        return response()->json($domaine->fresh());
    }

    public function destroy(DomaineActivite $domaine): JsonResponse
    {
        AuditLog::logAction('delete', 'domaines_activite', $domaine->id, $domaine->toArray(), null);
        $domaine->delete();

        return response()->json(['message' => 'Domaine d\'activité supprimé avec succès']);
    }
}
