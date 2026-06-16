<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FournisseurRequest;
use App\Models\AuditLog;
use App\Models\Fournisseur;
use App\Models\FournisseurBanque;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FournisseurController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Fournisseur::with(['domaineActivite', 'banques.banque']);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($query) use ($q) {
                $query->where('raison_sociale', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('nif', 'like', "%{$q}%")
                    ->orWhere('rc', 'like', "%{$q}%")
                    ->orWhere('sigle', 'like', "%{$q}%")
                    ->orWhere('ville', 'like', "%{$q}%")
                    ->orWhere('representant', 'like', "%{$q}%");
            });
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('domaine_activite_id')) {
            $query->where('domaine_activite_id', $request->domaine_activite_id);
        }

        if ($request->filled('mode_passation')) {
            $mode = $request->mode_passation;
            $query->where(function ($query) use ($mode) {
                $query->whereJsonContains('modes_passation', $mode)
                    ->orWhereNull('modes_passation')
                    ->orWhere('modes_passation', '[]');
            });
        }

        if ($request->filled('duree')) {
            $duree = (int) $request->duree;
            $query->where(function ($query) use ($duree) {
                $query->where(function ($query) use ($duree) {
                    $query->whereNull('duree_min')->orWhere('duree_min', '<=', $duree);
                })->where(function ($query) use ($duree) {
                    $query->whereNull('duree_max')->orWhere('duree_max', '>=', $duree);
                });
            });
        }

        $sortBy = $request->get('sortBy', 'raison_sociale');
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

    public function store(FournisseurRequest $request): JsonResponse
    {
        $data = $request->validated();
        $banques = $data['banques'] ?? [];
        unset($data['banques']);

        $fournisseur = Fournisseur::create(array_merge($data, ['created_by' => auth()->id()]));

        foreach ($banques as $banque) {
            FournisseurBanque::create(array_merge($banque, ['fournisseur_id' => $fournisseur->id]));
        }

        AuditLog::logAction('create', 'fournisseurs', $fournisseur->id, null, $fournisseur->toArray());

        return response()->json($fournisseur->load(['domaineActivite', 'banques.banque']), 201);
    }

    public function show(Fournisseur $fournisseur): JsonResponse
    {
        return response()->json($fournisseur->load(['domaineActivite', 'banques.banque', 'createdBy']));
    }

    public function update(FournisseurRequest $request, Fournisseur $fournisseur): JsonResponse
    {
        $old = $fournisseur->toArray();
        $data = $request->validated();
        $banques = $data['banques'] ?? null;
        unset($data['banques']);

        $fournisseur->update($data);

        if ($banques !== null) {
            $fournisseur->banques()->delete();
            foreach ($banques as $banque) {
                FournisseurBanque::create(array_merge($banque, ['fournisseur_id' => $fournisseur->id]));
            }
        }

        AuditLog::logAction('update', 'fournisseurs', $fournisseur->id, $old, $fournisseur->fresh()->toArray());

        return response()->json($fournisseur->fresh()->load(['domaineActivite', 'banques.banque']));
    }

    public function destroy(Fournisseur $fournisseur): JsonResponse
    {
        AuditLog::logAction('delete', 'fournisseurs', $fournisseur->id, $fournisseur->toArray(), null);
        $fournisseur->delete();

        return response()->json(['message' => 'Fournisseur supprimé avec succès']);
    }
}
