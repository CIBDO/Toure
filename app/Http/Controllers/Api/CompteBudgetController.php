<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompteBudgetRequest;
use App\Models\AuditLog;
use App\Models\CompteBudget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompteBudgetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CompteBudget::query();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($query) use ($q) {
                $query->where('code', 'like', "%{$q}%")
                    ->orWhere('libelle', 'like', "%{$q}%");
            });
        }

        if ($request->filled('exercice')) {
            $query->where('exercice', $request->exercice);
        }

        if ($request->filled('actif')) {
            $query->where('actif', filter_var($request->actif, FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy = $request->get('sortBy', 'code');
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

    public function store(CompteBudgetRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (!isset($data['montant_disponible'])) {
            $data['montant_disponible'] = $data['montant_alloue'] - ($data['montant_engage'] ?? 0);
        }

        $compte = CompteBudget::create(array_merge($data, ['created_by' => auth()->id()]));

        AuditLog::logAction('create', 'comptes_budget', $compte->id, null, $compte->toArray());

        return response()->json($compte, 201);
    }

    public function show(CompteBudget $compte_budget): JsonResponse
    {
        return response()->json($compte_budget);
    }

    public function update(CompteBudgetRequest $request, CompteBudget $compte_budget): JsonResponse
    {
        $old = $compte_budget->toArray();
        $data = $request->validated();
        if (!isset($data['montant_disponible'])) {
            $data['montant_disponible'] = ($data['montant_alloue'] ?? $compte_budget->montant_alloue)
                - ($data['montant_engage'] ?? $compte_budget->montant_engage);
        }
        $compte_budget->update($data);

        AuditLog::logAction('update', 'comptes_budget', $compte_budget->id, $old, $compte_budget->fresh()->toArray());

        return response()->json($compte_budget->fresh());
    }

    public function destroy(CompteBudget $compte_budget): JsonResponse
    {
        AuditLog::logAction('delete', 'comptes_budget', $compte_budget->id, $compte_budget->toArray(), null);
        $compte_budget->delete();

        return response()->json(['message' => 'Compte budget supprimé avec succès']);
    }
}
