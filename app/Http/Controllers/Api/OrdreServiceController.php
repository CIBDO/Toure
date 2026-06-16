<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectOrdreServiceRequest;
use App\Http\Requests\StoreOrdreServiceRequest;
use App\Http\Requests\UpdateOrdreServiceRequest;
use App\Models\AuditLog;
use App\Models\Contrat;
use App\Models\OrdreService;
use App\Services\OrdreServiceService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrdreServiceController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected OrdreServiceService $ordreServiceService
    ) {}

    /**
     * Liste des ordres de service d'un contrat.
     */
    public function indexByContrat(Request $request, Contrat $contrat): JsonResponse
    {
        $this->authorize('viewAny', OrdreService::class);

        $query = $contrat->ordreServices()->with(['createdBy', 'issuedBy', 'approvedBy']);

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('type_os')) {
            $query->where('type_os', $request->type_os);
        }
        if ($request->filled('date_emission_from')) {
            $query->where('date_emission', '>=', $request->date_emission_from);
        }
        if ($request->filled('date_emission_to')) {
            $query->where('date_emission', '<=', $request->date_emission_to);
        }

        $sortBy = $request->get('sortBy', 'date_emission');
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
     * Créer un ordre de service pour un contrat.
     */
    public function store(StoreOrdreServiceRequest $request, Contrat $contrat): JsonResponse
    {
        $data = $request->validated();
        $data['contrat_id'] = $contrat->id;
        $data['numero'] = $data['numero'] ?? $this->ordreServiceService->generateNumero($contrat);
        $data['statut'] = OrdreService::STATUT_DRAFT;
        $data['issued_by'] = auth()->id();
        $data['created_by'] = auth()->id();

        $os = OrdreService::create($data);

        AuditLog::logAction('create', 'ordre_services', $os->id, null, $os->toArray());

        return response()->json($os->load(['contrat', 'createdBy', 'issuedBy']), 201);
    }

    /**
     * Détail d'un ordre de service.
     */
    public function show(OrdreService $ordreService): JsonResponse
    {
        $this->authorize('view', $ordreService);

        return response()->json($ordreService->load([
            'contrat', 'createdBy', 'issuedBy', 'approvedBy', 'documents',
        ]));
    }

    /**
     * Mettre à jour un ordre de service (brouillon uniquement).
     */
    public function update(UpdateOrdreServiceRequest $request, OrdreService $ordreService): JsonResponse
    {
        $old = $ordreService->toArray();
        $ordreService->update($request->validated());

        AuditLog::logAction('update', 'ordre_services', $ordreService->id, $old, $ordreService->fresh()->toArray());

        return response()->json($ordreService->fresh()->load(['contrat', 'createdBy', 'issuedBy']));
    }

    /**
     * Soft delete (brouillon uniquement).
     */
    public function destroy(OrdreService $ordreService): JsonResponse
    {
        $this->authorize('delete', $ordreService);

        AuditLog::logAction('delete', 'ordre_services', $ordreService->id, $ordreService->toArray(), null);
        $ordreService->delete();

        return response()->json(['message' => 'Ordre de service supprimé avec succès']);
    }

    /**
     * Soumettre pour validation.
     */
    public function submit(OrdreService $ordreService): JsonResponse
    {
        $this->authorize('submit', $ordreService);

        $old = $ordreService->statut;
        $ordreService->update(['statut' => OrdreService::STATUT_SUBMITTED]);

        AuditLog::logAction('submit', 'ordre_services', $ordreService->id, ['statut' => $old], ['statut' => OrdreService::STATUT_SUBMITTED]);

        return response()->json($ordreService->fresh()->load(['contrat', 'createdBy', 'issuedBy']));
    }

    /**
     * Approuver l'OS et appliquer l'impact délai sur le contrat.
     */
    public function approve(OrdreService $ordreService): JsonResponse
    {
        $this->authorize('approve', $ordreService);

        try {
            $this->ordreServiceService->approveOs($ordreService);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $os = $ordreService->fresh();
        AuditLog::logAction('approve', 'ordre_services', $os->id, [], $os->toArray());

        return response()->json($os->load(['contrat', 'createdBy', 'issuedBy', 'approvedBy']));
    }

    /**
     * Rejeter l'OS.
     */
    public function reject(RejectOrdreServiceRequest $request, OrdreService $ordreService): JsonResponse
    {
        $this->authorize('reject', $ordreService);

        $old = $ordreService->statut;
        $ordreService->update([
            'statut' => OrdreService::STATUT_REJECTED,
            'commentaire_validation' => $request->validated('commentaire_validation'),
        ]);

        AuditLog::logAction('reject', 'ordre_services', $ordreService->id, ['statut' => $old], [
            'statut' => OrdreService::STATUT_REJECTED,
            'commentaire_validation' => $ordreService->commentaire_validation,
        ]);

        return response()->json($ordreService->fresh()->load(['contrat', 'createdBy', 'issuedBy']));
    }

    /**
     * Marquer comme exécuté.
     */
    public function execute(OrdreService $ordreService): JsonResponse
    {
        $this->authorize('execute', $ordreService);

        try {
            $this->ordreServiceService->executeOs($ordreService);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $os = $ordreService->fresh();
        AuditLog::logAction('execute', 'ordre_services', $os->id, [], $os->toArray());

        return response()->json($os->load(['contrat', 'createdBy', 'issuedBy', 'approvedBy']));
    }

    /**
     * Liste globale des ordres de service (filtres : contrat_id, type_os, statut, dates).
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', OrdreService::class);

        $query = OrdreService::with(['contrat', 'createdBy', 'issuedBy', 'approvedBy']);

        if ($request->filled('contrat_id')) {
            $query->where('contrat_id', $request->contrat_id);
        }
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('type_os')) {
            $query->where('type_os', $request->type_os);
        }
        if ($request->filled('date_emission_from')) {
            $query->where('date_emission', '>=', $request->date_emission_from);
        }
        if ($request->filled('date_emission_to')) {
            $query->where('date_emission', '<=', $request->date_emission_to);
        }

        $sortBy = $request->get('sortBy', 'date_emission');
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
