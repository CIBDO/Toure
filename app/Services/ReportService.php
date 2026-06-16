<?php

namespace App\Services;

use App\Models\Contrat;
use App\Models\Engagement;
use App\Models\Paiement;
use App\Models\Fournisseur;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ReportService
{
    /**
     * Filtres communs sur les contrats pour les rapports.
     */
    public function contratQuery(Request $request): Builder
    {
        $q = Contrat::query()->with(['fournisseur:id,raison_sociale', 'compteBudget:id,code,libelle']);

        if ($request->filled('date_from')) {
            $q->where('date_signature', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->where('date_signature', '<=', $request->date_to);
        }
        if ($request->filled('exercice')) {
            $q->where('exercice', $request->exercice);
        }
        if ($request->filled('fournisseur_id')) {
            $q->where('fournisseur_id', $request->fournisseur_id);
        }
        if ($request->filled('statut')) {
            $q->where('statut', $request->statut);
        }
        if ($request->filled('mode_passation')) {
            $q->where('mode_passation', $request->mode_passation);
        }
        if ($request->filled('compte_budget_id')) {
            $q->where('compte_budget_id', $request->compte_budget_id);
        }

        return $q;
    }

    /**
     * Rapport Synthèse Contrats : KPIs + répartition par statut + liste paginée.
     */
    public function reportContracts(Request $request): array
    {
        $query = $this->contratQuery($request);

        $totalContrats = (clone $query)->count();
        $montantTotal = (float) (clone $query)->sum('montant_initial');
        $totalEngage = (float) (clone $query)->sum('montant_initial');
        $rawPaid = (clone $query)->selectRaw('SUM(COALESCE(montant_initial, 0) - COALESCE(montant_actuel, montant_initial)) as paid')->value('paid');
        $totalPaye = $rawPaid !== null && $rawPaid > 0 ? (float) $rawPaid : 0.0;
        $resteAPayer = max(0, $montantTotal - $totalPaye);

        $repartitionStatut = (clone $query)
            ->selectRaw('statut, COUNT(*) as count, SUM(montant_initial) as montant')
            ->groupBy('statut')
            ->get()
            ->map(fn ($r) => [
                'statut' => $r->statut,
                'count' => (int) $r->count,
                'montant' => (float) $r->montant,
            ])
            ->values();

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $perPage = min((int) $request->get('per_page', 15), 100);
        $page = max(1, (int) $request->get('page', 1));

        $data = (clone $query)
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'indicators' => [
                'nombre_total_contrats' => $totalContrats,
                'montant_total_contrats' => $montantTotal,
                'total_engage' => $totalEngage,
                'total_paye' => $totalPaye,
                'reste_a_payer' => $resteAPayer,
            ],
            'repartition_par_statut' => $repartitionStatut,
            'data' => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'per_page' => $data->perPage(),
            'total' => $data->total(),
        ];
    }

    /**
     * Rapport Situation Financière.
     */
    public function reportFinancial(Request $request): array
    {
        $query = Contrat::query()->with(['fournisseur:id,raison_sociale']);

        if ($request->filled('exercice')) {
            $query->where('exercice', $request->exercice);
        }
        if ($request->filled('date_from')) {
            $query->where('date_signature', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date_signature', '<=', $request->date_to);
        }
        if ($request->filled('fournisseur_id')) {
            $query->where('fournisseur_id', $request->fournisseur_id);
        }
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        $totalEngage = (float) (clone $query)->sum('montant_initial');
        $rawPaid = (clone $query)->selectRaw('SUM(COALESCE(montant_initial, 0) - COALESCE(montant_actuel, montant_initial)) as p')->value('p');
        $totalPaye = $rawPaid !== null && $rawPaid > 0 ? (float) $rawPaid : 0.0;
        $resteAPayer = max(0, $totalEngage - $totalPaye);

        $contratsPayesTotal = (clone $query)->whereRaw('COALESCE(montant_actuel, montant_initial) <= 0')->count();
        $contratsPartiels = (clone $query)->whereRaw('COALESCE(montant_actuel, montant_initial) > 0 AND montant_initial > COALESCE(montant_actuel, 0)')->count();
        $contratsNonPayes = (clone $query)->whereRaw('COALESCE(montant_actuel, montant_initial) = montant_initial')->count();

        $perPage = min((int) $request->get('per_page', 15), 100);
        $data = (clone $query)->orderByDesc('created_at')->paginate($perPage);

        return [
            'indicators' => [
                'total_engage' => $totalEngage,
                'total_paye' => $totalPaye,
                'reste_a_payer' => $resteAPayer,
                'contrats_payes_total' => $contratsPayesTotal,
                'contrats_partiellement_payes' => $contratsPartiels,
                'contrats_non_payes' => $contratsNonPayes,
            ],
            'data' => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'per_page' => $data->perPage(),
            'total' => $data->total(),
        ];
    }

    /**
     * Rapport Engagements.
     */
    public function reportEngagements(Request $request): array
    {
        $query = Engagement::query()
            ->with(['contrat:id,reference,numero,objet', 'compteBudget:id,code,libelle']);

        if ($request->filled('date_from')) {
            $query->where('date_engagement', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date_engagement', '<=', $request->date_to);
        }
        if ($request->filled('exercice')) {
            $query->where('exercice', $request->exercice);
        }
        if ($request->filled('compte_budget_id')) {
            $query->where('compte_budget_id', $request->compte_budget_id);
        }
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        $data = (clone $query)->orderByDesc('date_engagement')->paginate($perPage);

        $items = collect($data->items())->map(function ($e) {
            $paye = (float) Paiement::where('engagement_id', $e->id)->where('statut', 'approved')->sum('montant');
            $engage = (float) $e->montant_engage;
            $reste = max(0, $engage - $paye);
            return [
                'id' => $e->id,
                'numero' => $e->numero,
                'contrat' => $e->contrat ? ['id' => $e->contrat->id, 'reference' => $e->contrat->reference ?? $e->contrat->numero, 'objet' => $e->contrat->objet ?? null] : null,
                'montant_engage' => $engage,
                'montant_paye' => $paye,
                'reste_engagement' => $reste,
                'statut' => $e->statut,
                'date_engagement' => $e->date_engagement?->format('Y-m-d'),
                'exercice' => $e->exercice,
            ];
        });

        return [
            'data' => $items->values()->all(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'per_page' => $data->perPage(),
            'total' => $data->total(),
        ];
    }

    /**
     * Rapport Paiements.
     */
    public function reportPayments(Request $request): array
    {
        $query = Paiement::query()
            ->with(['engagement.contrat:id,reference,numero,fournisseur_id', 'engagement:id,numero,contrat_id']);

        if ($request->filled('date_from')) {
            $query->where('date_paiement', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date_paiement', '<=', $request->date_to);
        }
        if ($request->filled('mode_paiement')) {
            $query->where('mode_paiement', $request->mode_paiement);
        }
        if ($request->filled('exercice')) {
            $query->whereHas('engagement', fn ($q) => $q->where('exercice', $request->exercice));
        }
        if ($request->filled('fournisseur_id')) {
            $query->whereHas('engagement.contrat', fn ($q) => $q->where('fournisseur_id', $request->fournisseur_id));
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        $data = (clone $query)->orderByDesc('date_paiement')->paginate($perPage);

        $items = collect($data->items())->map(fn ($p) => [
            'id' => $p->id,
            'reference' => $p->reference,
            'contrat' => $p->engagement?->contrat ? ['id' => $p->engagement->contrat->id, 'reference' => $p->engagement->contrat->reference ?? $p->engagement->contrat->numero] : null,
            'engagement' => $p->engagement ? ['id' => $p->engagement->id, 'numero' => $p->engagement->numero] : null,
            'montant' => (float) $p->montant,
            'date_paiement' => $p->date_paiement?->format('Y-m-d'),
            'mode_paiement' => $p->mode_paiement,
            'statut' => $p->statut,
        ]);

        return [
            'data' => $items->values()->all(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'per_page' => $data->perPage(),
            'total' => $data->total(),
        ];
    }

    /**
     * Rapport Performance Fournisseurs : Top par montant, délai moyen paiement.
     */
    public function reportSuppliers(Request $request): array
    {
        $query = Contrat::query()->whereNotNull('fournisseur_id');

        if ($request->filled('exercice')) {
            $query->where('exercice', $request->exercice);
        }
        if ($request->filled('date_from')) {
            $query->where('date_signature', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date_signature', '<=', $request->date_to);
        }

        $bySupplier = (clone $query)
            ->selectRaw('fournisseur_id, COUNT(*) as total_contrats, SUM(montant_initial) as montant_attribue')
            ->groupBy('fournisseur_id')
            ->orderByDesc('montant_attribue')
            ->limit(50)
            ->get();

        $fournisseurIds = $bySupplier->pluck('fournisseur_id')->unique()->filter()->values()->all();
        $fournisseurs = Fournisseur::whereIn('id', $fournisseurIds)->get()->keyBy('id');

        $totalPayeByEngagement = Paiement::where('statut', 'approved')
            ->selectRaw('engagement_id, SUM(montant) as total')
            ->groupBy('engagement_id')
            ->pluck('total', 'engagement_id');

        $engagementByContrat = Engagement::whereIn('contrat_id', (clone $query)->select('id'))->get()->groupBy('contrat_id');

        $rows = [];
        foreach ($bySupplier as $row) {
            $f = $fournisseurs->get($row->fournisseur_id);
            $contratIds = (clone $query)->where('fournisseur_id', $row->fournisseur_id)->pluck('id');
            $paye = 0;
            $delais = [];
            foreach ($contratIds as $cid) {
                $engagements = $engagementByContrat->get($cid, collect());
                foreach ($engagements as $eng) {
                    $paye += (float) ($totalPayeByEngagement->get($eng->id, 0));
                    $firstPayment = Paiement::where('engagement_id', $eng->id)->where('statut', 'approved')->orderBy('date_paiement')->first();
                    if ($firstPayment && $eng->date_engagement) {
                        $delais[] = (new \DateTime($firstPayment->date_paiement))->getTimestamp() - (new \DateTime($eng->date_engagement))->getTimestamp();
                    }
                }
            }
            $delaiMoyenJours = count($delais) > 0 ? (int) round(array_sum($delais) / count($delais) / 86400) : null;
            $rows[] = [
                'fournisseur_id' => $row->fournisseur_id,
                'fournisseur' => $f ? ['id' => $f->id, 'raison_sociale' => $f->raison_sociale] : null,
                'total_contrats' => (int) $row->total_contrats,
                'montant_attribue' => (float) $row->montant_attribue,
                'montant_paye' => $paye,
                'delai_moyen_paiement_jours' => $delaiMoyenJours,
            ];
        }

        usort($rows, fn ($a, $b) => $b['montant_attribue'] <=> $a['montant_attribue']);
        $top10 = array_slice($rows, 0, 10);

        return [
            'indicators' => [
                'total_fournisseurs' => count($rows),
            ],
            'data' => $rows,
            'top_10' => $top10,
        ];
    }
}
