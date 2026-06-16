<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService) {}

    private function canView(): bool
    {
        $user = request()->user();
        return $user && ($user->hasPermission('RAPPORTS_READ') || $user->hasPermission('REPORT_VIEW') || $user->hasPermission('REPORT_CONTRACT') || $user->hasPermission('REPORT_FINANCIAL'));
    }

    private function canExport(): bool
    {
        $user = request()->user();
        return $user && ($user->hasPermission('RAPPORTS_EXPORT') || $user->hasPermission('REPORT_EXPORT'));
    }

    /**
     * GET /api/reports/contracts
     */
    public function contracts(Request $request): JsonResponse
    {
        if (!$this->canView()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        return response()->json($this->reportService->reportContracts($request));
    }

    /**
     * GET /api/reports/financial
     */
    public function financial(Request $request): JsonResponse
    {
        if (!$this->canView()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        return response()->json($this->reportService->reportFinancial($request));
    }

    /**
     * GET /api/reports/engagements
     */
    public function engagements(Request $request): JsonResponse
    {
        if (!$this->canView()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        return response()->json($this->reportService->reportEngagements($request));
    }

    /**
     * GET /api/reports/payments
     */
    public function payments(Request $request): JsonResponse
    {
        if (!$this->canView()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        return response()->json($this->reportService->reportPayments($request));
    }

    /**
     * GET /api/reports/suppliers
     */
    public function suppliers(Request $request): JsonResponse
    {
        if (!$this->canView()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        return response()->json($this->reportService->reportSuppliers($request));
    }

    /**
     * GET /api/reports/contracts/export?format=excel|pdf
     */
    public function exportContracts(Request $request): JsonResponse|StreamedResponse|SymfonyResponse
    {
        if (!$this->canExport()) {
            return response()->json(['message' => 'Export non autorisé'], 403);
        }
        $format = strtolower($request->get('format', 'excel'));
        $data = $this->reportService->reportContracts($request);

        AuditLog::log('REPORT_EXPORT', 'Report', null, [
            'report' => 'contracts',
            'format' => $format,
            'filters' => $request->only(['date_from', 'date_to', 'exercice', 'fournisseur_id', 'statut']),
        ]);

        if ($format === 'pdf') {
            return $this->exportContractsPdf($data);
        }
        return $this->exportContractsExcel($data);
    }

    /**
     * GET /api/reports/financial/export?format=excel|pdf
     */
    public function exportFinancial(Request $request): JsonResponse|StreamedResponse|SymfonyResponse
    {
        if (!$this->canExport()) {
            return response()->json(['message' => 'Export non autorisé'], 403);
        }
        $format = strtolower($request->get('format', 'excel'));
        $data = $this->reportService->reportFinancial($request);

        AuditLog::log('REPORT_EXPORT', 'Report', null, [
            'report' => 'financial',
            'format' => $format,
        ]);

        if ($format === 'pdf') {
            return $this->exportFinancialPdf($data);
        }
        return $this->exportFinancialExcel($data);
    }

    private function exportContractsExcel(array $data): StreamedResponse
    {
        $filename = 'rapport-contrats-' . now()->format('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return Response::stream(function () use ($data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel
            fputcsv($out, ['Rapport Synthèse Contrats - CANAM - Généré le ' . now()->format('d/m/Y à H:i')], ';');
            fputcsv($out, [], ';');
            fputcsv($out, ['Référence', 'Objet', 'Fournisseur', 'Montant initial', 'Statut', 'Exercice', 'Date signature'], ';');
            foreach ($data['data'] ?? [] as $row) {
                $ref = $row->reference ?? $row->numero ?? '';
                $objet = $row->objet ?? '';
                $fournisseur = $row->fournisseur->raison_sociale ?? '';
                $montant = $row->montant_initial ?? 0;
                $statut = $row->statut ?? '';
                $exercice = $row->exercice ?? '';
                $dateSig = $row->date_signature ? (is_string($row->date_signature) ? $row->date_signature : $row->date_signature->format('Y-m-d')) : '';
                fputcsv($out, [$ref, $objet, $fournisseur, $montant, $statut, $exercice, $dateSig], ';');
            }
            fclose($out);
        }, 200, $headers);
    }

    private function exportContractsPdf(array $data): StreamedResponse|SymfonyResponse
    {
        $html = view('pdf.reports.contracts', $data)->render();
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) { // optional: composer require barryvdh/laravel-dompdf
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'landscape');
            return $pdf->stream('rapport-contrats-' . now()->format('Y-m-d') . '.pdf');
        }
        return Response::stream(function () use ($html) {
            echo $html;
        }, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    private function exportFinancialExcel(array $data): StreamedResponse
    {
        $filename = 'rapport-situation-financiere-' . now()->format('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        return Response::stream(function () use ($data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel
            fputcsv($out, ['Rapport Situation Financière - CANAM - Généré le ' . now()->format('d/m/Y à H:i')], ';');
            fputcsv($out, [], ';');
            fputcsv($out, ['Référence', 'Fournisseur', 'Montant initial', 'Montant actuel', 'Statut'], ';');
            foreach ($data['data'] ?? [] as $row) {
                $ref = $row->reference ?? $row->numero ?? '';
                $fournisseur = $row->fournisseur->raison_sociale ?? '';
                $initial = $row->montant_initial ?? 0;
                $actuel = $row->montant_actuel ?? $row->montant_initial ?? 0;
                $statut = $row->statut ?? '';
                fputcsv($out, [$ref, $fournisseur, $initial, $actuel, $statut], ';');
            }
            fclose($out);
        }, 200, $headers);
    }

    private function exportFinancialPdf(array $data): StreamedResponse|SymfonyResponse
    {
        $html = view('pdf.reports.financial', $data)->render();
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) { // optional: composer require barryvdh/laravel-dompdf
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'landscape');
            return $pdf->stream('rapport-situation-financiere-' . now()->format('Y-m-d') . '.pdf');
        }
        return Response::stream(function () use ($html) {
            echo $html;
        }, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
