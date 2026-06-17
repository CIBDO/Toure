<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DepouillementRequest;
use App\Models\AuditLog;
use App\Models\Depouillement;
use App\Models\Fournisseur;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DepouillementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Depouillement::with(['avis', 'compteBudget', 'createdBy']);

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

        return response()->json($depouillement->load(['avis', 'compteBudget']), 201);
    }

    public function show(Depouillement $depouillement): JsonResponse
    {
        return response()->json($depouillement->load(['avis.items', 'avis.fournisseurs', 'compteBudget', 'pvs', 'createdBy']));
    }

    public function update(DepouillementRequest $request, Depouillement $depouillement): JsonResponse
    {
        $old = $depouillement->toArray();
        $data = $request->validated();

        if (
            isset($data['date_depouillement']) && $data['date_depouillement'] !== $depouillement->date_depouillement?->format('Y-m-d')
            || array_key_exists('heure_depouillement', $data) && $data['heure_depouillement'] !== $depouillement->heure_depouillement
        ) {
            $data['notification_reunion_envoyee'] = false;
        }

        $depouillement->update($data);

        AuditLog::logAction('update', 'depouillements', $depouillement->id, $old, $depouillement->fresh()->toArray());

        return response()->json($depouillement->fresh()->load(['avis', 'compteBudget']));
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

        if (!empty($depouillement->resultats)) {
            $this->storeBordereauPdf($depouillement);
        }

        return response()->json($depouillement->fresh());
    }

    public function generateBordereau(Depouillement $depouillement): \Symfony\Component\HttpFoundation\Response|JsonResponse
    {
        if (empty($depouillement->resultats)) {
            return response()->json(['message' => 'Aucun pli enregistré. Le bordereau ne peut pas être généré.'], 422);
        }

        $data = $this->prepareBordereauData($depouillement);
        $filename = 'bordereau_envoi_' . $depouillement->reference . '_' . now()->format('Ymd') . '.pdf';
        $path = 'depouillements/bordereaux/' . $filename;
        $output = $this->buildBordereauPdf($data)->output();

        if ($depouillement->fichier_bordereau) {
            Storage::disk('local')->delete($depouillement->fichier_bordereau);
        }

        Storage::disk('local')->put($path, $output);
        $depouillement->update(['fichier_bordereau' => $path]);

        AuditLog::logAction(
            'generate',
            'depouillements',
            $depouillement->id,
            null,
            ['fichier_bordereau' => $path]
        );

        return response($output, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function prepareBordereauData(Depouillement $depouillement): array
    {
        $depouillement->load(['avis.items', 'compteBudget', 'createdBy']);

        $fournisseurIds = collect($depouillement->resultats ?? [])
            ->pluck('fournisseur_id')
            ->filter()
            ->unique();

        $fournisseurs = $fournisseurIds->isNotEmpty()
            ? Fournisseur::whereIn('id', $fournisseurIds)->get()->keyBy('id')
            : collect();

        $resultats = collect($depouillement->resultats ?? [])->map(function (array $r) use ($fournisseurs) {
            if (!empty($r['fournisseur_id']) && $fournisseurs->has($r['fournisseur_id'])) {
                $r['fournisseur_nom'] = $r['fournisseur_nom'] ?? $fournisseurs[$r['fournisseur_id']]->raison_sociale;
            }

            return $r;
        });

        $attributaire = $resultats->first(
            fn (array $r) => !empty($r['attributaire']) || !empty($r['retenu'])
        );

        return compact('depouillement', 'resultats', 'attributaire');
    }

    private function storeBordereauPdf(Depouillement $depouillement): string
    {
        $data = $this->prepareBordereauData($depouillement);
        $filename = 'bordereau_envoi_' . $depouillement->reference . '_' . now()->format('Ymd') . '.pdf';
        $path = 'depouillements/bordereaux/' . $filename;
        $output = $this->buildBordereauPdf($data)->output();

        if ($depouillement->fichier_bordereau) {
            Storage::disk('local')->delete($depouillement->fichier_bordereau);
        }

        Storage::disk('local')->put($path, $output);
        $depouillement->update(['fichier_bordereau' => $path]);

        AuditLog::logAction(
            'generate',
            'depouillements',
            $depouillement->id,
            null,
            ['fichier_bordereau' => $path]
        );

        return $filename;
    }

    private function buildBordereauPdf(array $data)
    {
        return Pdf::loadView('pdf.bordereau-envoi', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'dejavu sans');
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
