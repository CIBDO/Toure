<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Avis;
use App\Models\CompteBudget;
use App\Models\Contrat;
use App\Models\ContratEtape;
use App\Models\Depouillement;
use App\Models\Engagement;
use App\Models\Fournisseur;
use App\Models\Paiement;
use App\Models\Pv;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * GET /api/dashboard/summary
     * Retourne les KPIs, graphiques et tables pour le tableau de bord CRM.
     */
    public function summary(Request $request): JsonResponse
    {
        $exercice = $request->get('exercice');
        $from     = $request->get('from');
        $to       = $request->get('to');

        $contratQ = Contrat::query();
        $avisQ    = Avis::query();

        if ($exercice) {
            $contratQ->where('exercice', $exercice);
            $avisQ->where('exercice', $exercice);
        }
        if ($from) {
            $contratQ->where('created_at', '>=', $from);
            $avisQ->where('created_at', '>=', $from);
        }
        if ($to) {
            $contratQ->where('created_at', '<=', $to . ' 23:59:59');
            $avisQ->where('created_at', '<=', $to . ' 23:59:59');
        }

        // ── KPIs ─────────────────────────────────────────────────
        $contractsApproved = (clone $contratQ)->where('statut', 'approved')->count();
        $contractsPending  = (clone $contratQ)->whereIn('statut', ['draft', 'submitted'])->count();
        $avisTotal         = (clone $avisQ)->count();
        $depTotal          = Depouillement::count();
        $pvTotal           = Pv::count();
        $suppliersTotal    = Fournisseur::count();
        $budgetTotal       = CompteBudget::count();
        $usersTotal        = User::where('statut', 'ACTIF')->count();

        // Paiements effectués dans la période (date_paiement entre from et to)
        $paiementsQuery = Paiement::where('statut', 'approved');
        if ($from) {
            $paiementsQuery->whereDate('date_paiement', '>=', $from);
        }
        if ($to) {
            $paiementsQuery->whereDate('date_paiement', '<=', $to);
        }
        $paymentsTotal = (float) $paiementsQuery->sum('montant');

        // Reste à payer réel : total engagé (engagements approuvés) - total payé (paiements approuvés)
        $totalEngage = (float) Engagement::where('statut', 'approved')->sum('montant_engage');
        $totalPaye   = (float) Paiement::where('statut', 'approved')->sum('montant');
        $remainingToPay = max(0, $totalEngage - $totalPaye);

        // ── Chart : contrats par statut (avec filtres) ─────────────
        $contractsByStatusQuery = (clone $contratQ)->selectRaw('statut as status, COUNT(*) as count')
            ->groupBy('statut');
        $contractsByStatus = $contractsByStatusQuery->get()
            ->map(fn($r) => ['status' => $r->status, 'count' => (int) $r->count])
            ->values();

        // ── Chart : montants engagés vs payés par mois (engagements et paiements réels)
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            $monthExprEng = "TO_CHAR(date_engagement, 'YYYY-MM')";
            $monthExprPai = "TO_CHAR(date_paiement, 'YYYY-MM')";
        } else {
            $monthExprEng = "DATE_FORMAT(date_engagement, '%Y-%m')";
            $monthExprPai = "DATE_FORMAT(date_paiement, '%Y-%m')";
        }
        $engagedByMonth = Engagement::where('statut', 'approved')
            ->where('date_engagement', '>=', now()->subMonths(12))
            ->selectRaw("{$monthExprEng} as month, COALESCE(SUM(montant_engage), 0) as total")
            ->groupByRaw($monthExprEng)
            ->orderByRaw($monthExprEng)
            ->pluck('total', 'month')
            ->toArray();
        $paidByMonth = Paiement::where('statut', 'approved')
            ->where('date_paiement', '>=', now()->subMonths(12))
            ->selectRaw("{$monthExprPai} as month, COALESCE(SUM(montant), 0) as total")
            ->groupByRaw($monthExprPai)
            ->orderByRaw($monthExprPai)
            ->pluck('total', 'month')
            ->toArray();
        $allMonths = collect(array_keys($engagedByMonth))->merge(array_keys($paidByMonth))->unique()->sort()->values();
        $monthlyRaw = $allMonths->map(fn($month) => [
            'month'   => $month,
            'engaged' => (float) ($engagedByMonth[$month] ?? 0),
            'paid'    => (float) ($paidByMonth[$month] ?? 0),
        ])->values();

        // ── Chart : top 10 fournisseurs par montant ───────────────
        $topSuppliers = Contrat::selectRaw('fournisseur_id, SUM(montant_initial) as amount')
            ->where('statut', 'approved')
            ->groupBy('fournisseur_id')
            ->orderByDesc('amount')
            ->limit(10)
            ->with('fournisseur:id,raison_sociale')
            ->get()
            ->map(fn($r) => [
                'name'   => $r->fournisseur?->raison_sociale ?? 'Inconnu',
                'amount' => (float) $r->amount,
            ])
            ->values();

        // ── Table : derniers contrats (avec filtres) ───────────────
        $latestContracts = (clone $contratQ)
            ->with('fournisseur:id,raison_sociale')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn($c) => [
                'id'         => $c->id,
                'numero'     => $c->reference ?? $c->numero ?? "C-{$c->id}",
                'fournisseur'=> $c->fournisseur?->raison_sociale ?? '-',
                'montant'    => (float) $c->montant_initial,
                'statut'     => $c->statut,
                'created_at' => $c->created_at?->toDateString(),
            ])
            ->values();

        // ── Table : contrats en retard ────────────────────────────
        $contractsInDelay = ContratEtape::whereNotNull('date_limite')
            ->whereDate('date_limite', '<', now())
            ->whereNotIn('statut', ['completed'])
            ->with('contrat:id,reference,numero,fournisseur_id', 'contrat.fournisseur:id,raison_sociale')
            ->get()
            ->map(fn($e) => [
                'id'          => $e->contrat?->id,
                'numero'      => $e->contrat?->reference ?? $e->contrat?->numero ?? "C-{$e->contrat_id}",
                'fournisseur' => $e->contrat?->fournisseur?->raison_sociale ?? '-',
                'etape'       => $e->type_etape,
                'days_late'   => (int) now()->diffInDays($e->date_limite),
            ])
            ->values();

        return response()->json([
            'kpis' => [
                'contracts_approved'    => $contractsApproved,
                'contracts_pending'     => $contractsPending,
                'avis_total'            => $avisTotal,
                'depouillements_total'  => $depTotal,
                'pv_total'              => $pvTotal,
                'suppliers_total'       => $suppliersTotal,
                'budget_accounts_total' => $budgetTotal,
                'users_total'           => $usersTotal,
                'payments_total_amount' => $paymentsTotal,
                'remaining_to_pay'      => $remainingToPay,
            ],
            'charts' => [
                'contracts_by_status' => $contractsByStatus,
                'monthly_amounts'     => $monthlyRaw,
                'top_suppliers'       => $topSuppliers,
            ],
            'tables' => [
                'latest_contracts'    => $latestContracts,
                'contracts_in_delay'  => $contractsInDelay,
            ],
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $exercice = $request->get('exercice');
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');

        $contratQuery = Contrat::query();
        $avisQuery    = Avis::query();
        $depQuery     = Depouillement::query();

        if ($exercice) {
            $contratQuery->where('exercice', $exercice);
            $avisQuery->where('exercice', $exercice);
        }

        if ($dateFrom) {
            $contratQuery->where('created_at', '>=', $dateFrom);
            $avisQuery->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $contratQuery->where('created_at', '<=', $dateTo);
            $avisQuery->where('created_at', '<=', $dateTo);
        }

        $totalContrats      = (clone $contratQuery)->count();
        $contratsApprouves  = (clone $contratQuery)->where('statut', 'approved')->count();
        $contratsNonValides = (clone $contratQuery)->whereIn('statut', ['draft', 'submitted'])->count();
        $contratsArchives   = (clone $contratQuery)->where('statut', 'archived')->count();

        $montantTotal = (clone $contratQuery)->where('statut', 'approved')->sum('montant_initial');

        $totalAvis       = (clone $avisQuery)->count();
        $avisPublies     = (clone $avisQuery)->where('statut', 'published')->count();
        $avisClos        = (clone $avisQuery)->where('statut', 'closed')->count();

        $totalDepouillements = $depQuery->count();
        $totalPvs            = Pv::count();
        $totalFournisseurs   = Fournisseur::count();
        $totalComptesBudget  = CompteBudget::count();
        $totalUtilisateurs   = User::where('statut', 'ACTIF')->count();

        // Contrats par mois (12 derniers mois) — compatible MySQL + PostgreSQL
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            $moisExpr = "TO_CHAR(created_at, 'YYYY-MM')";
        } else {
            $moisExpr = "DATE_FORMAT(created_at, '%Y-%m')";
        }
        $contratsParMois = Contrat::selectRaw("{$moisExpr} as mois, COUNT(*) as total")
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupByRaw($moisExpr)
            ->orderByRaw($moisExpr)
            ->get();

        // Contrats par statut
        $contratsParStatut = Contrat::selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->get();

        // Étapes de suivi : comptage par type_etape + statut
        $etapesParType = ContratEtape::selectRaw('type_etape, statut, COUNT(*) as total')
            ->groupBy('type_etape', 'statut')
            ->get()
            ->groupBy('type_etape')
            ->map(fn($group) => $group->keyBy('statut')->map(fn($e) => $e->total));

        // Étapes en retard : date_limite dépassée et non completed
        $etapesEnRetard = ContratEtape::whereNotNull('date_limite')
            ->whereDate('date_limite', '<', now())
            ->whereNotIn('statut', ['completed'])
            ->with('contrat:id,reference,objet,fournisseur_id')
            ->get()
            ->map(fn($e) => [
                'id'           => $e->id,
                'type_etape'   => $e->type_etape,
                'statut'       => $e->statut,
                'date_limite'  => $e->date_limite,
                'jours_retard' => now()->diffInDays($e->date_limite),
                'contrat'      => $e->contrat ? [
                    'id'        => $e->contrat->id,
                    'reference' => $e->contrat->reference,
                    'objet'     => $e->contrat->objet,
                ] : null,
            ]);

        // Contrats actifs par étape courante (étape in_progress)
        $contratsParEtapeCourante = ContratEtape::where('statut', 'in_progress')
            ->selectRaw('type_etape, COUNT(*) as total')
            ->groupBy('type_etape')
            ->pluck('total', 'type_etape');

        return response()->json([
            'contrats' => [
                'total'         => $totalContrats,
                'approuves'     => $contratsApprouves,
                'non_valides'   => $contratsNonValides,
                'archives'      => $contratsArchives,
                'montant_total' => $montantTotal,
            ],
            'avis' => [
                'total'   => $totalAvis,
                'publies' => $avisPublies,
                'clos'    => $avisClos,
            ],
            'depouillements' => ['total' => $totalDepouillements],
            'pvs'            => ['total' => $totalPvs],
            'fournisseurs'   => ['total' => $totalFournisseurs],
            'comptes_budget' => ['total' => $totalComptesBudget],
            'utilisateurs'   => ['total' => $totalUtilisateurs],
            'charts' => [
                'contrats_par_mois'          => $contratsParMois,
                'contrats_par_statut'        => $contratsParStatut,
                'etapes_par_type'            => $etapesParType,
                'contrats_par_etape_courante'=> $contratsParEtapeCourante,
                'etapes_en_retard'           => $etapesEnRetard,
            ],
        ]);
    }
}
