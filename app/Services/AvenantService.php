<?php

namespace App\Services;

use App\Models\Avenant;
use App\Models\Contrat;
use Illuminate\Support\Facades\DB;

class AvenantService
{
    public function __construct(
        protected ContractFinanceService $financeService
    ) {}

    /**
     * Génère le numéro séquentiel pour un nouvel avenant (A1, A2, A3...).
     */
    public function generateNumero(Contrat $contrat): string
    {
        $max = $contrat->avenants()->withTrashed()->max(DB::raw('CAST(SUBSTRING(numero, 2) AS INTEGER)'));
        $next = ($max ?? 0) + 1;

        return 'A' . $next;
    }

    /**
     * Calcule nouveau_montant et nouvelle_date_fin / nouvelle_description_objet selon type_avenant.
     * Ne persiste pas : à utiliser avant create/update.
     */
    public function computeAvenantValues(Contrat $contrat, array $data): array
    {
        $montantRef = (int) ($contrat->montant_actuel ?? $contrat->montant_initial);
        $dateFinRef = $contrat->date_fin;
        $objetRef = $contrat->objet;

        $type = $data['type_avenant'] ?? Avenant::TYPE_MONTANT;

        $nouveauMontant = $montantRef;
        $nouvelleDateFin = $dateFinRef?->format('Y-m-d');
        $nouvelleDescriptionObjet = $objetRef;

        if (in_array($type, [Avenant::TYPE_MONTANT, Avenant::TYPE_MIXTE], true)) {
            $variation = (int) ($data['montant_variation'] ?? 0);
            $nouveauMontant = $montantRef + $variation;
        }

        if (in_array($type, [Avenant::TYPE_DELAI, Avenant::TYPE_MIXTE], true)) {
            $prolongation = (int) ($data['prolongation_jours'] ?? 0);
            if ($dateFinRef && $prolongation !== 0) {
                $nouvelleDateFin = $dateFinRef->copy()->addDays($prolongation)->format('Y-m-d');
            }
        }

        if (in_array($type, [Avenant::TYPE_OBJET, Avenant::TYPE_MIXTE], true)) {
            $nouvelleDescriptionObjet = $data['nouvelle_description_objet'] ?? $objetRef;
        }

        return [
            'ancien_montant' => $montantRef,
            'nouveau_montant' => max(0, $nouveauMontant),
            'ancienne_date_fin' => $dateFinRef,
            'nouvelle_date_fin' => $nouvelleDateFin ? \Carbon\Carbon::parse($nouvelleDateFin) : null,
            'ancienne_description_objet' => $objetRef,
            'nouvelle_description_objet' => $nouvelleDescriptionObjet,
        ];
    }

    /**
     * Règles métier : aucun avenant ne peut être approuvé si contrat archivé ou avenant en attente.
     */
    public function validateBusinessRules(Avenant $avenant): void
    {
        $contrat = $avenant->contrat;

        if ($contrat->statut === 'archived') {
            throw new \DomainException('Impossible d\'approuver un avenant sur un contrat archivé.');
        }

        $pending = $contrat->avenants()
            ->where('statut', Avenant::STATUT_SUBMITTED)
            ->where('id', '!=', $avenant->id)
            ->exists();

        if ($pending) {
            throw new \DomainException('Un autre avenant est déjà en attente de validation pour ce contrat.');
        }
    }

    /**
     * Applique l'avenant au contrat : met à jour montant, date_fin, objet.
     */
    public function applyAvenant(Avenant $avenant): void
    {
        $contrat = $avenant->contrat;

        $updates = [];
        if (in_array($avenant->type_avenant, [Avenant::TYPE_MONTANT, Avenant::TYPE_MIXTE], true)) {
            $updates['montant_initial'] = $avenant->nouveau_montant;
            $updates['montant_actuel'] = $avenant->nouveau_montant;
        }
        if (in_array($avenant->type_avenant, [Avenant::TYPE_DELAI, Avenant::TYPE_MIXTE], true) && $avenant->nouvelle_date_fin) {
            $updates['date_fin'] = $avenant->nouvelle_date_fin;
        }
        if (in_array($avenant->type_avenant, [Avenant::TYPE_OBJET, Avenant::TYPE_MIXTE], true) && $avenant->nouvelle_description_objet !== null) {
            $updates['objet'] = $avenant->nouvelle_description_objet;
        }

        if ($updates !== []) {
            $contrat->update($updates);
        }
    }

    /**
     * Recalcule les agrégats financiers (reste à payer, alertes).
     * Si montant diminue et total engagé > nouveau montant → alerte.
     */
    public function recalcContractFinance(Contrat $contrat): array
    {
        return $this->financeService->getSummary($contrat);
    }
}
