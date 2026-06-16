<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BanqueRequest;
use App\Models\AuditLog;
use App\Models\Banque;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BanqueController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Banque::query();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($query) use ($q) {
                $query->where('code', 'like', "%{$q}%")
                    ->orWhere('libelle', 'like', "%{$q}%")
                    ->orWhere('sigle', 'like', "%{$q}%");
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

    public function store(BanqueRequest $request): JsonResponse
    {
        $banque = Banque::create(array_merge(
            $request->validated(),
            ['created_by' => auth()->id()]
        ));

        AuditLog::logAction('create', 'banques', $banque->id, null, $banque->toArray());

        return response()->json($banque, 201);
    }

    public function show(Banque $banque): JsonResponse
    {
        return response()->json($banque->load('createdBy'));
    }

    public function update(BanqueRequest $request, Banque $banque): JsonResponse
    {
        $old = $banque->toArray();
        $banque->update($request->validated());

        AuditLog::logAction('update', 'banques', $banque->id, $old, $banque->fresh()->toArray());

        return response()->json($banque->fresh());
    }

    public function destroy(Banque $banque): JsonResponse
    {
        AuditLog::logAction('delete', 'banques', $banque->id, $banque->toArray(), null);
        $banque->delete();

        return response()->json(['message' => 'Banque supprimée avec succès']);
    }
}
