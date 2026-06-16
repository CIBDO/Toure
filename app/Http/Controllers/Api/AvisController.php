<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AvisRequest;
use App\Models\AuditLog;
use App\Models\Avis;
use App\Models\AvisItem;
use App\Notifications\AvisPublieNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class AvisController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Avis::with(['createdBy', 'fournisseurs', 'items']);

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

        if ($request->filled('mode_passation')) {
            $query->where('mode_passation', $request->mode_passation);
        }

        if ($request->filled('date_limite_from')) {
            $query->whereDate('date_limite_depot', '>=', $request->date_limite_from);
        }

        if ($request->filled('date_limite_to')) {
            $query->whereDate('date_limite_depot', '<=', $request->date_limite_to);
        }

        if ($request->filled('date_ouverture_from')) {
            $query->whereDate('date_ouverture_plis', '>=', $request->date_ouverture_from);
        }

        if ($request->filled('date_ouverture_to')) {
            $query->whereDate('date_ouverture_plis', '<=', $request->date_ouverture_to);
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

    public function store(AvisRequest $request): JsonResponse
    {
        $data = $request->validated();
        $fournisseurs = $data['fournisseurs'] ?? [];
        $items = $data['items'] ?? [];
        unset($data['fournisseurs'], $data['items']);
        $data = $this->applyPublicationDate($data);

        $avis = Avis::create(array_merge($data, ['created_by' => auth()->id()]));

        if (!empty($fournisseurs)) {
            $avis->fournisseurs()->sync($fournisseurs);
        }

        foreach ($items as $index => $item) {
            AvisItem::create(array_merge($item, ['avis_id' => $avis->id, 'ordre' => $index + 1]));
        }

        AuditLog::logAction('create', 'avis', $avis->id, null, $avis->toArray());

        return response()->json($avis->load(['fournisseurs', 'items']), 201);
    }

    public function show(Avis $avi): JsonResponse
    {
        return response()->json($avi->load(['fournisseurs', 'items', 'depouillements', 'pvs', 'createdBy', 'piecesJointes']));
    }

    public function update(AvisRequest $request, Avis $avi): JsonResponse
    {
        $old = $avi->toArray();
        $data = $request->validated();
        $fournisseurs = $data['fournisseurs'] ?? null;
        $items = $data['items'] ?? null;
        unset($data['fournisseurs'], $data['items']);
        $data = $this->applyPublicationDate($data);

        $avi->update($data);

        if ($fournisseurs !== null) {
            $avi->fournisseurs()->sync($fournisseurs);
        }

        if ($items !== null) {
            $avi->items()->delete();
            foreach ($items as $index => $item) {
                AvisItem::create(array_merge($item, ['avis_id' => $avi->id, 'ordre' => $index + 1]));
            }
        }

        AuditLog::logAction('update', 'avis', $avi->id, $old, $avi->fresh()->toArray());

        return response()->json($avi->fresh()->load(['fournisseurs', 'items']));
    }

    public function destroy(Avis $avi): JsonResponse
    {
        AuditLog::logAction('delete', 'avis', $avi->id, $avi->toArray(), null);
        $avi->delete();

        return response()->json(['message' => 'Avis supprimé avec succès']);
    }

    public function publish(Avis $avi): JsonResponse
    {
        $old = $avi->statut;
        $avi->update([
            'statut' => 'published',
            'date_publication' => $avi->date_publication ?? now()->toDateString(),
        ]);
        AuditLog::logAction('validate', 'avis', $avi->id, ['statut' => $old], ['statut' => 'published']);

        // Notifier les fournisseurs invités ayant un email
        $fournisseurs = $avi->fournisseurs()->whereNotNull('email')->where('email', '!=', '')->get();
        if ($fournisseurs->isNotEmpty()) {
            Notification::send($fournisseurs, new AvisPublieNotification($avi));
        }

        return response()->json(array_merge($avi->fresh()->toArray(), [
            'notifications_envoyees' => $fournisseurs->count(),
        ]));
    }

    public function submit(Avis $avi): JsonResponse
    {
        $old = $avi->statut;
        $avi->update(['statut' => 'submitted']);
        AuditLog::logAction('validate', 'avis', $avi->id, ['statut' => $old], ['statut' => 'submitted']);

        return response()->json($avi->fresh());
    }

    public function approve(Avis $avi): JsonResponse
    {
        $old = $avi->statut;
        $avi->update(['statut' => 'approved']);
        AuditLog::logAction('validate', 'avis', $avi->id, ['statut' => $old], ['statut' => 'approved']);

        return response()->json($avi->fresh());
    }

    public function reject(Request $request, Avis $avi): JsonResponse
    {
        $old = $avi->statut;
        $avi->update([
            'statut'       => 'rejected',
            'motif_rejet'  => $request->input('motif_rejet'),
        ]);
        AuditLog::logAction('validate', 'avis', $avi->id, ['statut' => $old], ['statut' => 'rejected', 'motif' => $request->input('motif_rejet')]);

        return response()->json($avi->fresh());
    }

    public function close(Avis $avi): JsonResponse
    {
        $old = $avi->statut;
        $avi->update(['statut' => 'closed']);
        AuditLog::logAction('validate', 'avis', $avi->id, ['statut' => $old], ['statut' => 'closed']);

        return response()->json($avi->fresh());
    }

    private function applyPublicationDate(array $data): array
    {
        if (($data['statut'] ?? null) === 'published' && empty($data['date_publication'])) {
            $data['date_publication'] = now()->toDateString();
        }

        return $data;
    }
}
