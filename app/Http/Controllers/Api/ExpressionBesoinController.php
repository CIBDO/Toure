<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpressionBesoinRequest;
use App\Models\AuditLog;
use App\Models\ExpressionBesoin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpressionBesoinController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ExpressionBesoin::with('domaineActivite');

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

        if ($request->filled('domaine_activite_id')) {
            $query->where('domaine_activite_id', $request->domaine_activite_id);
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

    public function store(ExpressionBesoinRequest $request): JsonResponse
    {
        $expression = ExpressionBesoin::create(array_merge(
            $request->validated(),
            ['created_by' => auth()->id()]
        ));

        AuditLog::logAction('create', 'expressions_besoin', $expression->id, null, $expression->toArray());

        return response()->json($expression->load('domaineActivite'), 201);
    }

    public function show(ExpressionBesoin $expressions_besoin): JsonResponse
    {
        return response()->json($expressions_besoin->load('domaineActivite'));
    }

    public function update(ExpressionBesoinRequest $request, ExpressionBesoin $expressions_besoin): JsonResponse
    {
        $old = $expressions_besoin->toArray();
        $expressions_besoin->update($request->validated());

        AuditLog::logAction('update', 'expressions_besoin', $expressions_besoin->id, $old, $expressions_besoin->fresh()->toArray());

        return response()->json($expressions_besoin->fresh()->load('domaineActivite'));
    }

    public function destroy(ExpressionBesoin $expressions_besoin): JsonResponse
    {
        if ($expressions_besoin->avisItems()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer une expression utilisée dans un avis de passation.',
            ], 422);
        }

        AuditLog::logAction('delete', 'expressions_besoin', $expressions_besoin->id, $expressions_besoin->toArray(), null);
        $expressions_besoin->delete();

        return response()->json(['message' => 'Expression de besoin supprimée avec succès']);
    }
}
