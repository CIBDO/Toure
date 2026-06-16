<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContratRequest;
use App\Models\AuditLog;
use App\Models\Contrat;
use App\Models\ContratEtape;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContratController extends Controller
{
    private const ETAPES = ['elaboration', 'engagement', 'oem', 'mandat', 'paie'];

    public function index(Request $request): JsonResponse
    {
        $query = Contrat::with(['fournisseur', 'compteBudget', 'agent', 'pv', 'avis']);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($query) use ($q) {
                $query->where('reference', 'like', "%{$q}%")
                    ->orWhere('objet', 'like', "%{$q}%");
            });
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('exercice')) {
            $query->where('exercice', $request->exercice);
        }

        if ($request->filled('fournisseur_id')) {
            $query->where('fournisseur_id', $request->fournisseur_id);
        }

        if ($request->filled('compte_budget_id')) {
            $query->where('compte_budget_id', $request->compte_budget_id);
        }

        if ($request->filled('date_from')) {
            $query->where('date_signature', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date_signature', '<=', $request->date_to);
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

    public function store(ContratRequest $request): JsonResponse
    {
        $contrat = Contrat::create(array_merge(
            $request->validated(),
            [
                'created_by' => auth()->id(),
                'montant_actuel' => $request->montant_initial,
            ]
        ));

        // Créer les étapes de suivi automatiquement
        foreach (self::ETAPES as $etape) {
            ContratEtape::create([
                'contrat_id' => $contrat->id,
                'type_etape' => $etape,
                'statut'     => 'pending',
            ]);
        }

        AuditLog::logAction('create', 'contrats', $contrat->id, null, $contrat->toArray());

        return response()->json($contrat->load(['fournisseur', 'compteBudget', 'etapes']), 201);
    }

    public function show(Contrat $contrat): JsonResponse
    {
        return response()->json($contrat->load([
            'fournisseur.domaineActivite',
            'fournisseur.banques.banque',
            'compteBudget',
            'agent',
            'pv.avis',
            'avis',
            'etapes',
            'piecesJointes',
            'createdBy',
        ]));
    }

    public function update(ContratRequest $request, Contrat $contrat): JsonResponse
    {
        $old = $contrat->toArray();
        $contrat->update($request->validated());

        AuditLog::logAction('update', 'contrats', $contrat->id, $old, $contrat->fresh()->toArray());

        return response()->json($contrat->fresh()->load(['fournisseur', 'compteBudget', 'etapes']));
    }

    public function destroy(Contrat $contrat): JsonResponse
    {
        AuditLog::logAction('delete', 'contrats', $contrat->id, $contrat->toArray(), null);
        $contrat->delete();

        return response()->json(['message' => 'Contrat supprimé avec succès']);
    }

    public function updateEtape(Request $request, Contrat $contrat, ContratEtape $etape): JsonResponse
    {
        $request->validate([
            'statut'         => ['required', 'in:pending,in_progress,completed,blocked'],
            'date_prevue'    => ['nullable', 'date'],
            'date_limite'    => ['nullable', 'date'],
            'date_effective' => ['nullable', 'date'],
            'commentaire'    => ['nullable', 'string'],
            'piece_jointe'   => ['nullable', 'file', 'max:10240'],
        ]);

        $data = $request->only(['statut', 'date_prevue', 'date_limite', 'date_effective', 'commentaire']);
        $data['updated_by'] = auth()->id();

        if ($request->hasFile('piece_jointe')) {
            $file = $request->file('piece_jointe');
            $path = $file->storeAs(
                "contrats/{$contrat->id}/etapes",
                "etape_{$etape->type_etape}_" . now()->format('Ymd_His') . '.' . $file->extension(),
                'local'
            );
            if ($etape->piece_jointe) {
                Storage::disk('local')->delete($etape->piece_jointe);
            }
            $data['piece_jointe'] = $path;
        }

        $etape->update($data);

        AuditLog::logAction('update', 'contrat_etapes', $etape->id, [], $etape->fresh()->toArray());

        return response()->json($etape->fresh());
    }

    public function submit(Contrat $contrat): JsonResponse
    {
        $old = $contrat->statut;
        $contrat->update(['statut' => 'submitted']);
        AuditLog::logAction('validate', 'contrats', $contrat->id, ['statut' => $old], ['statut' => 'submitted']);

        return response()->json($contrat->fresh());
    }

    public function approve(Contrat $contrat): JsonResponse
    {
        $old = $contrat->statut;
        $contrat->update(['statut' => 'approved']);
        AuditLog::logAction('validate', 'contrats', $contrat->id, ['statut' => $old], ['statut' => 'approved']);

        return response()->json($contrat->fresh());
    }

    public function reject(Request $request, Contrat $contrat): JsonResponse
    {
        $old = $contrat->statut;
        $contrat->update([
            'statut'      => 'rejected',
            'motif_rejet' => $request->input('motif_rejet'),
        ]);
        AuditLog::logAction('validate', 'contrats', $contrat->id, ['statut' => $old], ['statut' => 'rejected', 'motif' => $request->input('motif_rejet')]);

        return response()->json($contrat->fresh());
    }

    public function archive(Contrat $contrat): JsonResponse
    {
        $old = $contrat->statut;
        $contrat->update(['statut' => 'archived']);
        AuditLog::logAction('validate', 'contrats', $contrat->id, ['statut' => $old], ['statut' => 'archived']);

        return response()->json($contrat->fresh());
    }

    public function downloadEtapePiece(Contrat $contrat, ContratEtape $etape): \Symfony\Component\HttpFoundation\Response
    {
        if (!$etape->piece_jointe || !Storage::disk('local')->exists($etape->piece_jointe)) {
            abort(404, 'Fichier introuvable');
        }

        return Storage::disk('local')->download($etape->piece_jointe);
    }
}
