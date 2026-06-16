<?php

namespace App\Services;

use App\Models\Contrat;
use App\Models\OrdreService;
use Illuminate\Support\Facades\DB;

class OrdreServiceService
{
    /**
     * Génère le numéro OS au format OS-{année}-{contract_num}-{sequence}.
     * contract_num = numero ou reference du contrat, séquence = compteur par contrat.
     */
    public function generateNumero(Contrat $contrat): string
    {
        $year = now()->format('Y');
        $contractNum = preg_replace('/\s+/', '-', trim($contrat->numero ?? $contrat->reference ?? (string) $contrat->id));
        $maxSeq = $contrat->ordreServices()->withTrashed()->max(DB::raw('id'));
        $sequence = ($maxSeq ?? 0) + 1;
        $seqPadded = str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);

        return sprintf('OS-%s-%s-%s', $year, $contractNum, $seqPadded);
    }

    /**
     * Règles métier : ne pas approuver si contrat archivé ; optionnel : reprise après suspension.
     */
    public function validateBusinessRules(OrdreService $os): void
    {
        $contrat = $os->contrat;

        if ($contrat->statut === 'archived') {
            throw new \DomainException('Impossible d\'approuver un ordre de service sur un contrat archivé.');
        }

        if ($os->type_os === OrdreService::TYPE_REPRISE) {
            $hasActiveSuspension = $contrat->ordreServices()
                ->where('type_os', OrdreService::TYPE_SUSPENSION)
                ->whereIn('statut', [OrdreService::STATUT_APPROVED, OrdreService::STATUT_EXECUTED])
                ->whereNull('executed_at')
                ->exists();
            if (!$hasActiveSuspension) {
                // Avertissement métier possible : reprise sans suspension active
            }
        }
    }

    /**
     * Approuve l'OS et applique l'impact sur la date_fin du contrat (dans une transaction).
     */
    public function approveOs(OrdreService $os): void
    {
        $this->validateBusinessRules($os);

        DB::transaction(function () use ($os) {
            $contrat = $os->contrat;
            $oldDateFin = $contrat->date_fin?->format('Y-m-d');

            if ($os->impact_delai !== OrdreService::IMPACT_NONE && $os->delai_jours !== null && $os->delai_jours != 0) {
                $dateFin = $contrat->date_fin ?? now();
                if ($os->impact_delai === OrdreService::IMPACT_EXTEND) {
                    $contrat->date_fin = $dateFin->copy()->addDays($os->delai_jours);
                } else {
                    $contrat->date_fin = $dateFin->copy()->subDays(abs($os->delai_jours));
                }
                $contrat->save();
            }

            $os->update([
                'statut' => OrdreService::STATUT_APPROVED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        });
    }

    /**
     * Marque l'OS comme exécuté (date d'effet confirmée).
     */
    public function executeOs(OrdreService $os): void
    {
        if (!$os->isApproved()) {
            throw new \DomainException('Seul un ordre de service approuvé peut être marqué exécuté.');
        }

        $os->update([
            'statut' => OrdreService::STATUT_EXECUTED,
            'executed_at' => now(),
        ]);
    }

    /**
     * Calcule la nouvelle date_fin du contrat si l'OS (avec impact) était appliqué (pour simulation affichage).
     */
    public function simulateNewDateFin(Contrat $contrat, string $impactDelai, ?int $delaiJours): ?string
    {
        $dateFin = $contrat->date_fin;
        if (!$dateFin || $impactDelai === OrdreService::IMPACT_NONE || $delaiJours === null || $delaiJours == 0) {
            return $dateFin?->format('Y-m-d');
        }
        if ($impactDelai === OrdreService::IMPACT_EXTEND) {
            return $dateFin->copy()->addDays($delaiJours)->format('Y-m-d');
        }
        return $dateFin->copy()->subDays(abs($delaiJours))->format('Y-m-d');
    }
}
