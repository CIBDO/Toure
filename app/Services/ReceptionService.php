<?php

namespace App\Services;

use App\Models\Reception;
use App\Models\ReceptionItem;
use App\Models\Contrat;

class ReceptionService
{
    /**
     * Génère le numéro de réception pour un contrat (R1, R2, ...).
     */
    public function generateNumero(Contrat $contrat): string
    {
        $numeros = $contrat->receptions()->withTrashed()->pluck('numero');
        $max = 0;
        foreach ($numeros as $n) {
            if (preg_match('/^R(\d+)$/', $n, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return 'R' . ($max + 1);
    }

    /**
     * Calcule le taux d'exécution à partir des items (somme quantite_recue / somme quantite_prevue * 100).
     */
    public function calculateTauxExecution(Reception $reception): ?float
    {
        $items = $reception->receptionItems;
        if ($items->isEmpty()) {
            return null;
        }

        $totalPrevue = $items->sum('quantite_prevue');
        $totalRecue = $items->sum('quantite_recue');

        if ($totalPrevue <= 0) {
            return null;
        }

        return round((float) ($totalRecue / $totalPrevue * 100), 2);
    }

    /**
     * Vérifie qu'une réception définitive peut être approuvée :
     * - soit il existe au moins une réception provisoire approuvée
     * - soit l'utilisateur a la permission RECEPTION_OVERRIDE_DEFINITIVE
     */
    public function canApproveDefinitive(Reception $reception, bool $hasOverridePermission): bool
    {
        if ($reception->type_reception !== Reception::TYPE_DEFINITIVE) {
            return true;
        }

        if ($hasOverridePermission) {
            return true;
        }

        $hasProvisoireApproved = $reception->contrat->receptions()
            ->where('type_reception', Reception::TYPE_PROVISOIRE)
            ->where('statut', Reception::STATUT_APPROVED)
            ->exists();

        return $hasProvisoireApproved;
    }

    /**
     * Valide les règles métier avant approbation.
     */
    public function validateBeforeApprove(Reception $reception, bool $hasOverridePermission): void
    {
        $contrat = $reception->contrat;

        if ($contrat->statut === 'archived') {
            throw new \DomainException('Aucune réception possible sur un contrat archivé.');
        }

        if ($reception->type_reception === Reception::TYPE_DEFINITIVE) {
            if (!$this->canApproveDefinitive($reception, $hasOverridePermission)) {
                throw new \DomainException('Une réception définitive ne peut être approuvée qu\'après au moins une réception provisoire approuvée (ou avec permission dérogatoire).');
            }
        }
    }

    /**
     * Approuve la réception et met à jour le contrat (statut exécution, cloturable).
     * À appeler dans une transaction.
     */
    public function approveReception(Reception $reception): void
    {
        $reception->update([
            'statut' => Reception::STATUT_APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $contrat = $reception->contrat;

        if ($reception->type_reception === Reception::TYPE_PROVISOIRE) {
            $contrat->update([
                'status_execution' => 'reception_provisoire',
            ]);
        }

        if ($reception->type_reception === Reception::TYPE_DEFINITIVE) {
            $contrat->update([
                'status_execution' => 'reception_definitive',
                'cloturable' => true,
            ]);
        }

        if ($reception->type_reception === Reception::TYPE_PARTIELLE) {
            $contrat->update([
                'status_execution' => $contrat->status_execution ?: 'reception_partielle',
            ]);
        }
    }
}
