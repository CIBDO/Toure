<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PieceJointe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PieceJointeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'entite_type' => ['required', 'string'],
            'entite_id'   => ['required', 'integer'],
        ]);

        $pieces = PieceJointe::where('entite_type', $request->entite_type)
            ->where('entite_id', $request->entite_id)
            ->with('createdBy')
            ->latest()
            ->get();

        return response()->json($pieces);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'entite_type' => ['required', 'string'],
            'entite_id'   => ['required', 'integer'],
            'fichier'     => ['required', 'file', 'max:20480'], // 20MB max
            'categorie'   => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        $file = $request->file('fichier');
        $nomOriginal = $file->getClientOriginalName();
        $nomStockage = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $chemin = $file->storeAs('pieces_jointes/' . date('Y/m'), $nomStockage, 'local');

        $piece = PieceJointe::create([
            'entite_type'  => $request->entite_type,
            'entite_id'    => $request->entite_id,
            'nom_original' => $nomOriginal,
            'nom_stockage' => $nomStockage,
            'chemin'       => $chemin,
            'type_mime'    => $file->getMimeType(),
            'taille'       => $file->getSize(),
            'categorie'    => $request->categorie,
            'description'  => $request->description,
            'created_by'   => auth()->id(),
        ]);

        AuditLog::logAction('create', 'pieces_jointes', $piece->id, null, ['nom' => $nomOriginal]);

        return response()->json($piece->load('createdBy'), 201);
    }

    public function download(PieceJointe $pieceJointe): \Symfony\Component\HttpFoundation\Response
    {
        if (!Storage::disk('local')->exists($pieceJointe->chemin)) {
            abort(404, 'Fichier introuvable');
        }

        return Storage::disk('local')->download($pieceJointe->chemin, $pieceJointe->nom_original);
    }

    public function destroy(PieceJointe $pieceJointe): JsonResponse
    {
        Storage::disk('local')->delete($pieceJointe->chemin);
        AuditLog::logAction('delete', 'pieces_jointes', $pieceJointe->id, ['nom' => $pieceJointe->nom_original], null);
        $pieceJointe->delete();

        return response()->json(['message' => 'Pièce jointe supprimée avec succès']);
    }
}
