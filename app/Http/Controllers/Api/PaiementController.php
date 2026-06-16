<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaiementRequest;
use App\Models\AuditLog;
use App\Models\Engagement;
use App\Models\Paiement;
use App\Services\ContractFinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaiementController extends Controller
{
    public function __construct(private ContractFinanceService $financeService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Paiement::with([
            'engagement:id,numero,contrat_id,montant_engage',
            'engagement.contrat:id,reference,objet',
            'banque:id,libelle',
            'createdBy:id,prenom,nom',
        ]);

        if ($request->filled('q')) {
            $query->where('reference', 'like', "%{$request->q}%");
        }

        if ($request->filled('engagement_id')) {
            $query->where('engagement_id', $request->engagement_id);
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('mode_paiement')) {
            $query->where('mode_paiement', $request->mode_paiement);
        }

        if ($request->filled('exercice')) {
            $query->whereHas('engagement', fn($q) => $q->where('exercice', $request->exercice));
        }

        if ($request->filled('contrat_id')) {
            $query->whereHas('engagement', fn($q) => $q->where('contrat_id', $request->contrat_id));
        }

        if ($request->filled('date_from')) {
            $query->where('date_paiement', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date_paiement', '<=', $request->date_to);
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

    public function indexByEngagement(Request $request, Engagement $engagement): JsonResponse
    {
        $query = $engagement->paiements()
            ->with(['banque:id,libelle', 'createdBy:id,prenom,nom'])
            ->orderByDesc('created_at');

        return response()->json([
            'data'  => $query->get(),
            'total' => $query->count(),
        ]);
    }

    public function store(PaiementRequest $request): JsonResponse
    {
        $paiement = Paiement::create(array_merge(
            $request->validated(),
            ['created_by' => auth()->id()]
        ));

        AuditLog::logAction('create', 'paiements', $paiement->id, null, $paiement->toArray());

        return response()->json(
            $paiement->load(['engagement:id,numero,contrat_id', 'banque:id,libelle']),
            201
        );
    }

    public function show(Paiement $paiement): JsonResponse
    {
        return response()->json($paiement->load([
            'engagement.contrat:id,reference,objet,montant_initial',
            'banque:id,libelle',
            'createdBy:id,prenom,nom',
            'approvedBy:id,prenom,nom',
        ]));
    }

    public function update(PaiementRequest $request, Paiement $paiement): JsonResponse
    {
        $old = $paiement->toArray();
        $paiement->update($request->validated());

        AuditLog::logAction('update', 'paiements', $paiement->id, $old, $paiement->fresh()->toArray());

        return response()->json($paiement->fresh()->load(['engagement:id,numero', 'banque:id,libelle']));
    }

    public function destroy(Paiement $paiement): JsonResponse
    {
        AuditLog::logAction('delete', 'paiements', $paiement->id, $paiement->toArray(), null);
        $paiement->delete();

        return response()->json(['message' => 'Paiement supprimé avec succès']);
    }

    public function submit(Paiement $paiement): JsonResponse
    {
        $old = $paiement->statut;
        $paiement->update(['statut' => 'submitted']);
        AuditLog::logAction('validate', 'paiements', $paiement->id, ['statut' => $old], ['statut' => 'submitted']);

        return response()->json($paiement->fresh());
    }

    public function approve(Request $request, Paiement $paiement): JsonResponse
    {
        $engagement = $paiement->engagement;
        if (!$this->financeService->checkPaymentLimit($engagement, $paiement->montant, $paiement->id)) {
            return response()->json(['message' => 'Le montant dépasse le solde disponible de l\'engagement.'], 422);
        }

        $old = $paiement->statut;
        $paiement->update([
            'statut'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        AuditLog::logAction('validate', 'paiements', $paiement->id, ['statut' => $old], ['statut' => 'approved']);

        return response()->json($paiement->fresh());
    }

    public function reject(Request $request, Paiement $paiement): JsonResponse
    {
        $old = $paiement->statut;
        $paiement->update([
            'statut'                 => 'rejected',
            'commentaire_validation' => $request->input('commentaire_validation'),
        ]);
        AuditLog::logAction('validate', 'paiements', $paiement->id, ['statut' => $old], [
            'statut'     => 'rejected',
            'commentaire'=> $request->input('commentaire_validation'),
        ]);

        return response()->json($paiement->fresh());
    }
}
