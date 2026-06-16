<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PvRequest;
use App\Models\AuditLog;
use App\Models\Pv;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PvController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Pv::with(['avis', 'fournisseurAttributaire', 'depouillement', 'createdBy']);

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

        if ($request->filled('type_pv')) {
            $query->where('type_pv', $request->type_pv);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date_pv', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date_pv', '<=', $request->date_to);
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

    public function store(PvRequest $request): JsonResponse
    {
        $pv = Pv::create(array_merge(
            $request->validated(),
            ['created_by' => auth()->id()]
        ));

        AuditLog::logAction('create', 'pvs', $pv->id, null, $pv->toArray());

        return response()->json($pv->load(['avis', 'fournisseurAttributaire']), 201);
    }

    public function show(Pv $pv): JsonResponse
    {
        return response()->json($pv->load(['avis.items', 'fournisseurAttributaire', 'depouillement', 'contrats', 'piecesJointes', 'createdBy']));
    }

    public function update(PvRequest $request, Pv $pv): JsonResponse
    {
        $old = $pv->toArray();
        $pv->update($request->validated());

        AuditLog::logAction('update', 'pvs', $pv->id, $old, $pv->fresh()->toArray());

        return response()->json($pv->fresh()->load(['avis', 'fournisseurAttributaire']));
    }

    public function destroy(Pv $pv): JsonResponse
    {
        AuditLog::logAction('delete', 'pvs', $pv->id, $pv->toArray(), null);
        $pv->delete();

        return response()->json(['message' => 'PV supprimé avec succès']);
    }

    public function submit(Pv $pv): JsonResponse
    {
        $old = $pv->statut;
        $pv->update(['statut' => 'submitted']);
        AuditLog::logAction('validate', 'pvs', $pv->id, ['statut' => $old], ['statut' => 'submitted']);

        return response()->json($pv->fresh());
    }

    public function approve(Pv $pv): JsonResponse
    {
        $old = $pv->statut;
        $pv->update(['statut' => 'approved', 'date_signature' => now()]);
        AuditLog::logAction('validate', 'pvs', $pv->id, ['statut' => $old], ['statut' => 'approved']);

        return response()->json($pv->fresh());
    }

    public function reject(Request $request, Pv $pv): JsonResponse
    {
        $old = $pv->statut;
        $pv->update([
            'statut'      => 'rejected',
            'motif_rejet' => $request->input('motif_rejet'),
        ]);
        AuditLog::logAction('validate', 'pvs', $pv->id, ['statut' => $old], ['statut' => 'rejected', 'motif' => $request->input('motif_rejet')]);

        return response()->json($pv->fresh());
    }

    public function uploadSigne(Request $request, Pv $pv): JsonResponse
    {
        $request->validate([
            'fichier' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $file = $request->file('fichier');
        $filename = 'pv_signe_' . $pv->reference . '_' . now()->format('Ymd_His') . '.pdf';
        $path = $file->storeAs('pvs/signes', $filename, 'local');

        $old = $pv->fichier_pv_signe;
        if ($old) {
            Storage::disk('local')->delete($old);
        }

        $pv->update(['fichier_pv_signe' => $path]);
        AuditLog::logAction('update', 'pvs', $pv->id, ['fichier_pv_signe' => $old], ['fichier_pv_signe' => $path]);

        return response()->json([
            'message'         => 'PV signé uploadé avec succès',
            'fichier_pv_signe' => $path,
        ]);
    }

    public function downloadSigne(Pv $pv): \Symfony\Component\HttpFoundation\Response
    {
        if (!$pv->fichier_pv_signe || !Storage::disk('local')->exists($pv->fichier_pv_signe)) {
            abort(404, 'Fichier PV signé introuvable');
        }

        return Storage::disk('local')->download($pv->fichier_pv_signe);
    }

    public function generatePdf(Pv $pv): \Symfony\Component\HttpFoundation\Response
    {
        $pv->load(['avis.items', 'fournisseurAttributaire', 'depouillement']);

        $pdf = Pdf::loadView('pdf.pv', ['pv' => $pv]);
        $filename = 'pv_' . $pv->reference . '_' . now()->format('Ymd') . '.pdf';
        $path = 'pvs/' . $filename;

        Storage::disk('local')->put($path, $pdf->output());
        $pv->update(['fichier_pdf' => $path]);

        return $pdf->download($filename);
    }
}
