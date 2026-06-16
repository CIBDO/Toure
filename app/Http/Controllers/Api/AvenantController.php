<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectAvenantRequest;
use App\Http\Requests\StoreAvenantRequest;
use App\Http\Requests\UpdateAvenantRequest;
use App\Models\AuditLog;
use App\Models\Avenant;
use App\Models\Contrat;
use App\Services\AvenantService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AvenantController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected AvenantService $avenantService
    ) {}

    /**
     * Liste des avenants d'un contrat.
     */
    public function indexByContrat(Request $request, Contrat $contrat): JsonResponse
    {
        $this->authorize('viewAny', Avenant::class);

        $query = $contrat->avenants()->with(['createdBy', 'approvedBy']);

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('date_signature_from')) {
            $query->where('date_signature', '>=', $request->date_signature_from);
        }
        if ($request->filled('date_signature_to')) {
            $query->where('date_signature', '<=', $request->date_signature_to);
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
            'data' => $paginated->items(),
            'total' => $paginated->total(),
        ]);
    }

    /**
     * Créer un avenant pour un contrat.
     */
    public function store(StoreAvenantRequest $request, Contrat $contrat): JsonResponse
    {
        $data = $request->validated();

        $computed = $this->avenantService->computeAvenantValues($contrat, $data);
        $data = array_merge($data, $computed);

        $data['contrat_id'] = $contrat->id;
        $data['numero'] = $this->avenantService->generateNumero($contrat);
        $data['created_by'] = auth()->id();
        $data['statut'] = Avenant::STATUT_DRAFT;

        $avenant = Avenant::create($data);

        AuditLog::logAction('create', 'avenants', $avenant->id, null, $avenant->toArray());

        return response()->json($avenant->load(['contrat', 'createdBy']), 201);
    }

    /**
     * Détail d'un avenant.
     */
    public function show(Avenant $avenant): JsonResponse
    {
        $this->authorize('view', $avenant);

        return response()->json($avenant->load(['contrat', 'createdBy', 'approvedBy']));
    }

    /**
     * Mettre à jour un avenant (brouillon uniquement).
     */
    public function update(UpdateAvenantRequest $request, Avenant $avenant): JsonResponse
    {
        $old = $avenant->toArray();

        $data = $request->validated();
        $computed = $this->avenantService->computeAvenantValues($avenant->contrat, array_merge($avenant->toArray(), $data));
        $data = array_merge($data, $computed);

        $avenant->update($data);

        AuditLog::logAction('update', 'avenants', $avenant->id, $old, $avenant->fresh()->toArray());

        return response()->json($avenant->fresh()->load(['contrat', 'createdBy']));
    }

    /**
     * Soft delete (brouillon uniquement).
     */
    public function destroy(Avenant $avenant): JsonResponse
    {
        $this->authorize('delete', $avenant);

        AuditLog::logAction('delete', 'avenants', $avenant->id, $avenant->toArray(), null);
        $avenant->delete();

        return response()->json(['message' => 'Avenant supprimé avec succès']);
    }

    /**
     * Soumettre pour validation.
     */
    public function submit(Avenant $avenant): JsonResponse
    {
        $this->authorize('submit', $avenant);

        $old = $avenant->statut;
        $avenant->update(['statut' => Avenant::STATUT_SUBMITTED]);

        AuditLog::logAction('validate', 'avenants', $avenant->id, ['statut' => $old], ['statut' => Avenant::STATUT_SUBMITTED]);

        return response()->json($avenant->fresh()->load(['contrat', 'createdBy']));
    }

    /**
     * Approuver l'avenant et appliquer les modifications au contrat.
     */
    public function approve(Avenant $avenant): JsonResponse
    {
        $this->authorize('approve', $avenant);

        try {
            $this->avenantService->validateBusinessRules($avenant);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        DB::transaction(function () use ($avenant) {
            $this->avenantService->applyAvenant($avenant);
            $avenant->update([
                'statut' => Avenant::STATUT_APPROVED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        });

        $old = ['statut' => $avenant->getOriginal('statut')];
        AuditLog::logAction('approve', 'avenants', $avenant->id, $old, $avenant->fresh()->toArray());

        $summary = $this->avenantService->recalcContractFinance($avenant->contrat);

        return response()->json([
            'avenant' => $avenant->fresh()->load(['contrat', 'createdBy', 'approvedBy']),
            'finance_summary' => $summary,
        ]);
    }

    /**
     * Rejeter l'avenant.
     */
    public function reject(RejectAvenantRequest $request, Avenant $avenant): JsonResponse
    {
        $this->authorize('reject', $avenant);

        $old = $avenant->statut;
        $avenant->update([
            'statut' => Avenant::STATUT_REJECTED,
            'commentaire_validation' => $request->validated('commentaire_validation'),
        ]);

        AuditLog::logAction('reject', 'avenants', $avenant->id, ['statut' => $old], [
            'statut' => Avenant::STATUT_REJECTED,
            'commentaire_validation' => $avenant->commentaire_validation,
        ]);

        return response()->json($avenant->fresh()->load(['contrat', 'createdBy']));
    }

    /**
     * Liste globale des avenants (filtres : contrat_id, statut, date_signature).
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Avenant::class);

        $query = Avenant::with(['contrat', 'createdBy', 'approvedBy']);

        if ($request->filled('contrat_id')) {
            $query->where('contrat_id', $request->contrat_id);
        }
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('date_signature_from')) {
            $query->where('date_signature', '>=', $request->date_signature_from);
        }
        if ($request->filled('date_signature_to')) {
            $query->where('date_signature', '<=', $request->date_signature_to);
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
            'data' => $paginated->items(),
            'total' => $paginated->total(),
        ]);
    }
}
