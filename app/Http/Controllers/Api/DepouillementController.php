<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DepouillementRequest;
use App\Models\AuditLog;
use App\Models\Depouillement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepouillementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Depouillement::with(['avis', 'createdBy']);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($query) use ($q) {
                $query->where('reference', 'like', "%{$q}%");
            });
        }

        if ($request->filled('avis_id')) {
            $query->where('avis_id', $request->avis_id);
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date_depouillement', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date_depouillement', '<=', $request->date_to);
        }

        $sortBy = $request->get('sortBy', 'created_at');
        $sortOrder = $request->get('sortDesc', true) ? 'desc' : 'asc';
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

    public function store(DepouillementRequest $request): JsonResponse
    {
        $depouillement = Depouillement::create(array_merge(
            $request->validated(),
            ['created_by' => auth()->id()]
        ));

        AuditLog::logAction('create', 'depouillements', $depouillement->id, null, $depouillement->toArray());

        return response()->json($depouillement->load(['avis']), 201);
    }

    public function show(Depouillement $depouillement): JsonResponse
    {
        return response()->json($depouillement->load(['avis.items', 'avis.fournisseurs', 'pvs', 'createdBy']));
    }

    public function update(DepouillementRequest $request, Depouillement $depouillement): JsonResponse
    {
        $old = $depouillement->toArray();
        $depouillement->update($request->validated());

        AuditLog::logAction('update', 'depouillements', $depouillement->id, $old, $depouillement->fresh()->toArray());

        return response()->json($depouillement->fresh()->load(['avis']));
    }

    public function destroy(Depouillement $depouillement): JsonResponse
    {
        AuditLog::logAction('delete', 'depouillements', $depouillement->id, $depouillement->toArray(), null);
        $depouillement->delete();

        return response()->json(['message' => 'Dépouillement supprimé avec succès']);
    }

    public function submit(Depouillement $depouillement): JsonResponse
    {
        $depouillement->update(['statut' => 'submitted']);
        AuditLog::logAction('validate', 'depouillements', $depouillement->id, ['statut' => 'draft'], ['statut' => 'submitted']);

        return response()->json($depouillement->fresh());
    }

    public function approve(Depouillement $depouillement): JsonResponse
    {
        $old = $depouillement->statut;
        $depouillement->update(['statut' => 'approved']);
        AuditLog::logAction('validate', 'depouillements', $depouillement->id, ['statut' => $old], ['statut' => 'approved']);

        return response()->json($depouillement->fresh());
    }

    public function reject(Request $request, Depouillement $depouillement): JsonResponse
    {
        $old = $depouillement->statut;
        $depouillement->update([
            'statut'      => 'rejected',
            'motif_rejet' => $request->input('motif_rejet'),
        ]);
        AuditLog::logAction('validate', 'depouillements', $depouillement->id, ['statut' => $old], ['statut' => 'rejected', 'motif' => $request->input('motif_rejet')]);

        return response()->json($depouillement->fresh());
    }
}
