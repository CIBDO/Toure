<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectReceptionRequest;
use App\Http\Requests\StoreReceptionRequest;
use App\Http\Requests\UpdateReceptionRequest;
use App\Models\AuditLog;
use App\Models\Contrat;
use App\Models\Reception;
use App\Models\ReceptionItem;
use App\Services\ReceptionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceptionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ReceptionService $receptionService
    ) {}

    /**
     * Liste des réceptions d'un contrat.
     */
    public function indexByContrat(Request $request, Contrat $contrat): JsonResponse
    {
        $this->authorize('viewAny', Reception::class);

        $query = $contrat->receptions()->with(['createdBy', 'approvedBy', 'receptionItems']);

        if ($request->filled('type_reception')) {
            $query->where('type_reception', $request->type_reception);
        }
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('date_from')) {
            $query->where('date_reception', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date_reception', '<=', $request->date_to);
        }

        $sortBy = $request->get('sortBy', 'date_reception');
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
     * Créer une réception pour un contrat.
     */
    public function store(StoreReceptionRequest $request, Contrat $contrat): JsonResponse
    {
        $data = $request->validated();
        $items = $data['reception_items'] ?? [];
        unset($data['reception_items']);

        $data['contrat_id'] = $contrat->id;
        $data['numero'] = $this->receptionService->generateNumero($contrat);
        $data['created_by'] = auth()->id();
        $data['statut'] = Reception::STATUT_DRAFT;
        $data['statut_conformite'] = $data['statut_conformite'] ?? Reception::STATUT_CONFORMITE_CONFORME;

        $reception = Reception::create($data);

        foreach ($items as $row) {
            ReceptionItem::create([
                'reception_id' => $reception->id,
                'label' => $row['label'] ?? null,
                'quantite_prevue' => $row['quantite_prevue'] ?? null,
                'quantite_recue' => $row['quantite_recue'] ?? null,
                'conforme' => $row['conforme'] ?? true,
                'observation' => $row['observation'] ?? null,
            ]);
        }

        $taux = $this->receptionService->calculateTauxExecution($reception->load('receptionItems'));
        if ($taux !== null) {
            $reception->update(['taux_execution' => $taux]);
        }

        AuditLog::logAction('create', Reception::class, $reception->id, null, $reception->fresh()->toArray());

        return response()->json($reception->fresh()->load(['contrat', 'createdBy', 'receptionItems']), 201);
    }

    /**
     * Détail d'une réception.
     */
    public function show(Reception $reception): JsonResponse
    {
        $this->authorize('view', $reception);

        return response()->json($reception->load([
            'contrat', 'createdBy', 'approvedBy', 'receptionItems', 'documents',
        ]));
    }

    /**
     * Mettre à jour une réception (brouillon uniquement).
     */
    public function update(UpdateReceptionRequest $request, Reception $reception): JsonResponse
    {
        $old = $reception->toArray();

        $data = $request->validated();
        $items = $data['reception_items'] ?? null;
        unset($data['reception_items']);

        $reception->update($data);

        if ($items !== null) {
            $reception->receptionItems()->delete();
            foreach ($items as $row) {
                ReceptionItem::create([
                    'reception_id' => $reception->id,
                    'label' => $row['label'] ?? null,
                    'quantite_prevue' => $row['quantite_prevue'] ?? null,
                    'quantite_recue' => $row['quantite_recue'] ?? null,
                    'conforme' => $row['conforme'] ?? true,
                    'observation' => $row['observation'] ?? null,
                ]);
            }
        }

        $taux = $this->receptionService->calculateTauxExecution($reception->fresh()->load('receptionItems'));
        $reception->update(['taux_execution' => $taux]);

        AuditLog::logAction('update', Reception::class, $reception->id, $old, $reception->fresh()->toArray());

        return response()->json($reception->fresh()->load(['contrat', 'createdBy', 'receptionItems']));
    }

    /**
     * Soft delete (brouillon uniquement).
     */
    public function destroy(Reception $reception): JsonResponse
    {
        $this->authorize('delete', $reception);

        AuditLog::logAction('delete', Reception::class, $reception->id, $reception->toArray(), null);
        $reception->delete();

        return response()->json(['message' => 'Réception supprimée avec succès']);
    }

    /**
     * Soumettre pour validation.
     */
    public function submit(Reception $reception): JsonResponse
    {
        $this->authorize('submit', $reception);

        $old = $reception->statut;
        $reception->update(['statut' => Reception::STATUT_SUBMITTED]);

        AuditLog::logAction('reception_submit', Reception::class, $reception->id, ['statut' => $old], ['statut' => Reception::STATUT_SUBMITTED]);

        return response()->json($reception->fresh()->load(['contrat', 'createdBy', 'receptionItems']));
    }

    /**
     * Approuver et mettre à jour le contrat.
     */
    public function approve(Reception $reception): JsonResponse
    {
        $this->authorize('approve', $reception);

        $hasOverride = request()->user()?->hasPermission('RECEPTION_OVERRIDE_DEFINITIVE') ?? false;

        try {
            $this->receptionService->validateBeforeApprove($reception, $hasOverride);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        DB::transaction(function () use ($reception) {
            $this->receptionService->approveReception($reception);
        });

        $old = ['statut' => $reception->getOriginal('statut')];
        AuditLog::logAction('reception_approve', Reception::class, $reception->id, $old, $reception->fresh()->toArray());

        return response()->json($reception->fresh()->load(['contrat', 'createdBy', 'approvedBy', 'receptionItems']));
    }

    /**
     * Rejeter la réception.
     */
    public function reject(RejectReceptionRequest $request, Reception $reception): JsonResponse
    {
        $this->authorize('reject', $reception);

        $old = $reception->statut;
        $reception->update([
            'statut' => Reception::STATUT_REJECTED,
            'commentaire_validation' => $request->validated('commentaire_validation'),
        ]);

        AuditLog::logAction('reception_reject', Reception::class, $reception->id, ['statut' => $old], [
            'statut' => Reception::STATUT_REJECTED,
            'commentaire_validation' => $reception->commentaire_validation,
        ]);

        return response()->json($reception->fresh()->load(['contrat', 'createdBy']));
    }

    /**
     * Liste globale des réceptions (filtres).
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Reception::class);

        $query = Reception::with(['contrat', 'createdBy', 'approvedBy']);

        if ($request->filled('contrat_id')) {
            $query->where('contrat_id', $request->contrat_id);
        }
        if ($request->filled('type_reception')) {
            $query->where('type_reception', $request->type_reception);
        }
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('date_from')) {
            $query->where('date_reception', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date_reception', '<=', $request->date_to);
        }

        $sortBy = $request->get('sortBy', 'date_reception');
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
