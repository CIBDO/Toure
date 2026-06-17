<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BanqueController;
use App\Http\Controllers\Api\ExpressionBesoinController;
use App\Http\Controllers\Api\DomaineActiviteController;
use App\Http\Controllers\Api\CompteBudgetController;
use App\Http\Controllers\Api\FournisseurController;
use App\Http\Controllers\Api\AvisController;
use App\Http\Controllers\Api\DepouillementController;
use App\Http\Controllers\Api\PvController;
use App\Http\Controllers\Api\ContratController;
use App\Http\Controllers\Api\PieceJointeController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EngagementController;
use App\Http\Controllers\Api\PaiementController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\AvenantController;
use App\Http\Controllers\Api\OrdreServiceController;
use App\Http\Controllers\Api\ReceptionController;

// Route de test
Route::get('/test', function () {
    return response()->json([
        'message' => 'CANAM Contract Manager API',
        'timestamp' => now()->toDateTimeString(),
        'version' => app()->version(),
    ]);
});

// Routes d'authentification (publiques)
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Routes protégées
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // Dashboard
    Route::get('/dashboard/stats',   [DashboardController::class, 'stats']);
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

    // Utilisateurs
    Route::apiResource('users', UserController::class);
    Route::post('users/{id}/roles', [UserController::class, 'assignRole']);
    Route::delete('users/{id}/roles/{roleId}', [UserController::class, 'revokeRole']);
    Route::post('users/{id}/activate', [UserController::class, 'activate']);
    Route::post('users/{id}/deactivate', [UserController::class, 'deactivate']);
    Route::post('users/{id}/suspend', [UserController::class, 'suspend']);

    // Rôles & Permissions
    Route::apiResource('roles', RoleController::class);
    Route::post('roles/{id}/permissions', [RoleController::class, 'assignPermission']);
    Route::delete('roles/{id}/permissions/{permissionId}', [RoleController::class, 'revokePermission']);
    Route::apiResource('permissions', PermissionController::class);

    // Référentiels
    Route::apiResource('banques', BanqueController::class);
    Route::apiResource('domaines', DomaineActiviteController::class);
    Route::apiResource('expressions-besoin', ExpressionBesoinController::class);
    Route::apiResource('comptes-budget', CompteBudgetController::class);

    // Fournisseurs
    Route::apiResource('fournisseurs', FournisseurController::class);

    // Passation — Avis
    Route::apiResource('avis', AvisController::class);
    Route::post('avis/{avi}/submit',  [AvisController::class, 'submit']);
    Route::post('avis/{avi}/approve', [AvisController::class, 'approve']);
    Route::post('avis/{avi}/reject',  [AvisController::class, 'reject']);
    Route::post('avis/{avi}/publish', [AvisController::class, 'publish']);
    Route::post('avis/{avi}/close',   [AvisController::class, 'close']);

    // Passation — Dépouillements
    Route::apiResource('depouillements', DepouillementController::class);
    Route::post('depouillements/{depouillement}/submit',  [DepouillementController::class, 'submit']);
    Route::post('depouillements/{depouillement}/approve', [DepouillementController::class, 'approve']);
    Route::post('depouillements/{depouillement}/reject',  [DepouillementController::class, 'reject']);
    Route::get('depouillements/{depouillement}/bordereau', [DepouillementController::class, 'generateBordereau']);

    // Passation — PVs
    Route::apiResource('pvs', PvController::class);
    Route::post('pvs/{pv}/submit',          [PvController::class, 'submit']);
    Route::post('pvs/{pv}/approve',         [PvController::class, 'approve']);
    Route::post('pvs/{pv}/reject',          [PvController::class, 'reject']);
    Route::get('pvs/{pv}/pdf',              [PvController::class, 'generatePdf']);
    Route::post('pvs/{pv}/upload-signe',    [PvController::class, 'uploadSigne']);
    Route::get('pvs/{pv}/download-signe',   [PvController::class, 'downloadSigne']);

    // Contrats
    Route::apiResource('contrats', ContratController::class);
    Route::post('contrats/{contrat}/submit',  [ContratController::class, 'submit']);
    Route::post('contrats/{contrat}/approve', [ContratController::class, 'approve']);
    Route::post('contrats/{contrat}/reject',  [ContratController::class, 'reject']);
    Route::post('contrats/{contrat}/archive', [ContratController::class, 'archive']);
    Route::put('contrats/{contrat}/etapes/{etape}',          [ContratController::class, 'updateEtape']);
    Route::post('contrats/{contrat}/etapes/{etape}',         [ContratController::class, 'updateEtape']); // multipart/form-data
    Route::get('contrats/{contrat}/etapes/{etape}/download', [ContratController::class, 'downloadEtapePiece']);

    // Avenants (Phase 2)
    Route::get('avenants', [AvenantController::class, 'index']);
    Route::get('contrats/{contrat}/avenants', [AvenantController::class, 'indexByContrat']);
    Route::post('contrats/{contrat}/avenants', [AvenantController::class, 'store']);
    Route::get('avenants/{avenant}', [AvenantController::class, 'show']);
    Route::put('avenants/{avenant}', [AvenantController::class, 'update']);
    Route::delete('avenants/{avenant}', [AvenantController::class, 'destroy']);
    Route::post('avenants/{avenant}/submit', [AvenantController::class, 'submit']);
    Route::post('avenants/{avenant}/approve', [AvenantController::class, 'approve']);
    Route::post('avenants/{avenant}/reject', [AvenantController::class, 'reject']);

    // Ordres de Service (Phase 2)
    Route::get('ordre-services', [OrdreServiceController::class, 'index']);
    Route::get('contrats/{contrat}/ordre-services', [OrdreServiceController::class, 'indexByContrat']);
    Route::post('contrats/{contrat}/ordre-services', [OrdreServiceController::class, 'store']);
    Route::get('ordre-services/{ordre_service}', [OrdreServiceController::class, 'show']);
    Route::put('ordre-services/{ordre_service}', [OrdreServiceController::class, 'update']);
    Route::delete('ordre-services/{ordre_service}', [OrdreServiceController::class, 'destroy']);
    Route::post('ordre-services/{ordre_service}/submit', [OrdreServiceController::class, 'submit']);
    Route::post('ordre-services/{ordre_service}/approve', [OrdreServiceController::class, 'approve']);
    Route::post('ordre-services/{ordre_service}/reject', [OrdreServiceController::class, 'reject']);
    Route::post('ordre-services/{ordre_service}/execute', [OrdreServiceController::class, 'execute']);

    // Réceptions (Phase 2) — PV de réception
    Route::get('receptions', [ReceptionController::class, 'index']);
    Route::get('contrats/{contrat}/receptions', [ReceptionController::class, 'indexByContrat']);
    Route::post('contrats/{contrat}/receptions', [ReceptionController::class, 'store']);
    Route::get('receptions/{reception}', [ReceptionController::class, 'show']);
    Route::put('receptions/{reception}', [ReceptionController::class, 'update']);
    Route::delete('receptions/{reception}', [ReceptionController::class, 'destroy']);
    Route::post('receptions/{reception}/submit', [ReceptionController::class, 'submit']);
    Route::post('receptions/{reception}/approve', [ReceptionController::class, 'approve']);
    Route::post('receptions/{reception}/reject', [ReceptionController::class, 'reject']);

    // Finances — Engagements
    Route::get('/contrats/{contrat}/engagements',     [EngagementController::class, 'indexByContrat']);
    Route::get('/contrats/{contrat}/finance-summary', [EngagementController::class, 'financeSummary']);
    Route::apiResource('engagements', EngagementController::class);
    Route::post('engagements/{engagement}/submit',  [EngagementController::class, 'submit']);
    Route::post('engagements/{engagement}/approve', [EngagementController::class, 'approve']);
    Route::post('engagements/{engagement}/reject',  [EngagementController::class, 'reject']);

    // Finances — Paiements
    Route::get('/engagements/{engagement}/paiements', [PaiementController::class, 'indexByEngagement']);
    Route::apiResource('paiements', PaiementController::class);
    Route::post('paiements/{paiement}/submit',  [PaiementController::class, 'submit']);
    Route::post('paiements/{paiement}/approve', [PaiementController::class, 'approve']);
    Route::post('paiements/{paiement}/reject',  [PaiementController::class, 'reject']);

    // GED - Pièces jointes
    Route::get('/pieces-jointes', [PieceJointeController::class, 'index']);
    Route::post('/pieces-jointes', [PieceJointeController::class, 'store']);
    Route::get('/pieces-jointes/{pieceJointe}/download', [PieceJointeController::class, 'download']);
    Route::delete('/pieces-jointes/{pieceJointe}', [PieceJointeController::class, 'destroy']);

    // GED - Documents (module GED)
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::get('/documents/{document}', [DocumentController::class, 'show']);
    Route::put('/documents/{document}', [DocumentController::class, 'update']);
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy']);
    Route::get('/documents/{document}/download', [DocumentController::class, 'download']);
    Route::get('/documents/{document}/preview', [DocumentController::class, 'preview']);

    // Rapports
    Route::get('/reports/contracts', [ReportController::class, 'contracts']);
    Route::get('/reports/contracts/export', [ReportController::class, 'exportContracts']);
    Route::get('/reports/financial', [ReportController::class, 'financial']);
    Route::get('/reports/financial/export', [ReportController::class, 'exportFinancial']);
    Route::get('/reports/engagements', [ReportController::class, 'engagements']);
    Route::get('/reports/payments', [ReportController::class, 'payments']);
    Route::get('/reports/suppliers', [ReportController::class, 'suppliers']);
});
