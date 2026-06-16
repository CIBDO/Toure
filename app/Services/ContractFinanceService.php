<?php

namespace App\Services;

use App\Models\Contrat;
use App\Models\Engagement;
use App\Models\Paiement;

class ContractFinanceService
{
    public function getSummary(Contrat $contrat): array
    {
        $totalEngaged = $contrat->engagements()
            ->where('statut', 'approved')
            ->sum('montant_engage');

        $totalPaid = Paiement::whereHas(
            'engagement',
            fn($q) => $q->where('contrat_id', $contrat->id)
        )
            ->where('statut', 'approved')
            ->sum('montant');

        $remaining = max(0, (float) $contrat->montant_initial - (float) $totalPaid);

        $paidStatus = 'non_paye';
        if ((float) $totalPaid > 0) {
            $paidStatus = (float) $totalPaid >= (float) $contrat->montant_initial
                ? 'paye'
                : 'partiel';
        }

        return [
            'total_engaged'  => (int) $totalEngaged,
            'total_paid'     => (int) $totalPaid,
            'remaining'      => (int) $remaining,
            'paid_status'    => $paidStatus,
            'montant_contrat'=> (float) $contrat->montant_initial,
        ];
    }

    public function checkPaymentLimit(Engagement $engagement, int $newAmount, ?int $excludeId = null): bool
    {
        $alreadyPaid = $engagement->paiements()
            ->where('statut', 'approved')
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->sum('montant');

        return ($alreadyPaid + $newAmount) <= $engagement->montant_engage;
    }
}
