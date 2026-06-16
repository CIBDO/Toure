<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EngagementRequest;
use App\Models\AuditLog;
use App\Models\Contrat;
use App\Models\Engagement;
use App\Services\ContractFinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EngagementController extends Controller
{
    public function __construct(private ContractFinanceService $financeService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Engagement::with(['contrat:id,reference,objet', 'compteBudget:id,libelle', 'createdBy:id,prenom,nom']);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where('numero', 'like', "%{$q}%");
        }

        if ($request->filled('contrat_id')) {
            $query->where('contrat_id', $request->contrat_id);
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('exercice')) {
            $query->where('exercice', $request->exercice);
        }

        if ($request->filled('date_from')) {
            $query->where('date_engagement', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date_engagement', '<=', $request->date_to);
        }

        $sortBy    = $request->get('sortBy', 'created_at');
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

    public function indexByContrat(Request $request, Contrat $contrat): JsonResponse
    {
        $query = $contrat->engagements()
            ->with(['compteBudget:id,libelle', 'paiements', 'createdBy:id,prenom,nom'])
            ->orderByDesc('created_at');

        return response()->json([
            'data'  => $query->get(),
            'total' => $query->count(),
        ]);
    }

    public function store(EngagementRequest $request): JsonResponse
    {
        $engagement = Engagement::create(array_merge(
            $request->validated(),
            ['created_by' => auth()->id()]
        ));

        AuditLog::logAction('create', 'engagements', $engagement->id, null, $engagement->toArray());

        return response()->json(
            $engagement->load(['contrat:id,reference,objet', 'compteBudget:id,libelle']),
            201
        );
    }

    public function show(Engagement $engagement): JsonResponse
    {
        return response()->json($engagement->load([
            'contrat:id,reference,objet,montant_initial',
            'compteBudget:id,libelle',
            'paiements.banque:id,libelle',
            'createdBy:id,prenom,nom',
            'approvedBy:id,prenom,nom',
        ]));
    }

    public function update(EngagementRequest $request, Engagement $engagement): JsonResponse
    {
        $old = $engagement->toArray();
        $engagement->update($request->validated());

        AuditLog::logAction('update', 'engagements', $engagement->id, $old, $engagement->fresh()->toArray());

        return response()->json($engagement->fresh()->load(['contrat:id,reference,objet', 'compteBudget:id,libelle']));
    }

    public function destroy(Engagement $engagement): JsonResponse
    {
        AuditLog::logAction('delete', 'engagements', $engagement->id, $engagement->toArray(), null);
        $engagement->delete();

        return response()->json(['message' => 'Engagement supprimé avec succès']);
    }

    public function submit(Engagement $engagement): JsonResponse
    {
        $old = $engagement->statut;
        $engagement->update(['statut' => 'submitted']);
        AuditLog::logAction('validate', 'engagements', $engagement->id, ['statut' => $old], ['statut' => 'submitted']);

        return response()->json($engagement->fresh());
    }

    public function approve(Engagement $engagement): JsonResponse
    {
        $old = $engagement->statut;
        $engagement->update([
            'statut'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        AuditLog::logAction('validate', 'engagements', $engagement->id, ['statut' => $old], ['statut' => 'approved']);

        return response()->json($engagement->fresh());
    }

    public function reject(Request $request, Engagement $engagement): JsonResponse
    {
        $old = $engagement->statut;
        $engagement->update([
            'statut'                 => 'rejected',
            'commentaire_validation' => $request->input('commentaire_validation'),
        ]);
        AuditLog::logAction('validate', 'engagements', $engagement->id, ['statut' => $old], [
            'statut'     => 'rejected',
            'commentaire'=> $request->input('commentaire_validation'),
        ]);

        return response()->json($engagement->fresh());
    }

    public function financeSummary(Contrat $contrat): JsonResponse
    {
        return response()->json($this->financeService->getSummary($contrat));
    }
}
