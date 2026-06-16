<script setup lang="ts">
import { useAvenantsStore } from '@/stores/avenants'

definePage({ meta: { title: 'Détail Avenant' } })

const route = useRoute()
const router = useRouter()
const store = useAvenantsStore()
const snackbar = ref({ show: false, text: '', color: 'success' })

const avenantId = computed(() => Number(route.params.id))
const avenant = computed(() => store.currentAvenant)

const typeLabels: Record<string, string> = {
  montant: 'Modification du montant',
  delai: 'Modification du délai',
  objet: "Modification de l'objet",
  mixte: 'Mixte',
}

const statutColor = (s: string) =>
  ({ draft: 'default', submitted: 'info', approved: 'success', rejected: 'error' }[s] || 'default')

const statutLabel = (s: string) =>
  ({ draft: 'Brouillon', submitted: 'Soumis', approved: 'Approuvé', rejected: 'Rejeté' }[s] || s)

const formatMontant = (v: number) =>
  v != null ? new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v) : '-'

const formatDate = (d: string) => (d ? new Date(d).toLocaleDateString('fr-FR') : '-')

onMounted(() => store.fetchAvenant(avenantId.value))
</script>

<template>
  <div v-if="avenant">
    <VRow class="mb-4">
      <VCol cols="12">
        <VCard>
          <VCardText class="pa-4">
            <div class="d-flex align-center gap-3 flex-wrap">
              <VBtn icon variant="text" @click="router.back()">
                <VIcon icon="tabler-arrow-left" />
              </VBtn>
              <div class="flex-grow-1">
                <div class="d-flex align-center gap-2 flex-wrap">
                  <h1 class="text-h5 font-weight-bold">
                    Avenant {{ avenant.numero }}
                  </h1>
                  <VChip :color="statutColor(avenant.statut)" size="small">
                    {{ statutLabel(avenant.statut) }}
                  </VChip>
                  <span class="text-body-2 text-medium-emphasis">
                    {{ typeLabels[avenant.type_avenant] }}
                  </span>
                </div>
                <p v-if="avenant.contrat" class="text-body-2 text-medium-emphasis mb-0 mt-1">
                  Contrat : {{ avenant.contrat.reference }} — {{ (avenant.contrat.objet || '').substring(0, 80) }}{{ (avenant.contrat.objet || '').length > 80 ? '…' : '' }}
                </p>
              </div>
              <VBtn
                variant="tonal"
                color="secondary"
                @click="router.push(`/apps/contrats/${avenant.contrat_id}`)"
              >
                Voir le contrat
              </VBtn>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VRow>
      <VCol cols="12" md="6">
        <VCard>
          <VCardTitle class="pa-4 text-subtitle-1">
            Données de l'avenant
          </VCardTitle>
          <VDivider />
          <VCardText>
            <VTable density="compact">
              <tbody>
                <tr>
                  <td class="text-medium-emphasis text-caption" style="width: 45%">Numéro</td>
                  <td class="font-weight-medium">{{ avenant.numero }}</td>
                </tr>
                <tr>
                  <td class="text-medium-emphasis text-caption">Type</td>
                  <td>{{ typeLabels[avenant.type_avenant] }}</td>
                </tr>
                <tr>
                  <td class="text-medium-emphasis text-caption">Date de signature</td>
                  <td>{{ formatDate(avenant.date_signature) }}</td>
                </tr>
                <tr v-if="avenant.montant_variation != null">
                  <td class="text-medium-emphasis text-caption">Variation montant</td>
                  <td :class="avenant.montant_variation >= 0 ? 'text-success' : 'text-error'">
                    {{ formatMontant(avenant.montant_variation) }}
                  </td>
                </tr>
                <tr>
                  <td class="text-medium-emphasis text-caption">Ancien montant</td>
                  <td>{{ formatMontant(avenant.ancien_montant) }}</td>
                </tr>
                <tr>
                  <td class="text-medium-emphasis text-caption">Nouveau montant</td>
                  <td class="font-weight-bold text-primary">{{ formatMontant(avenant.nouveau_montant) }}</td>
                </tr>
                <tr v-if="avenant.prolongation_jours != null">
                  <td class="text-medium-emphasis text-caption">Prolongation</td>
                  <td>{{ avenant.prolongation_jours }} jours</td>
                </tr>
                <tr v-if="avenant.ancienne_date_fin || avenant.nouvelle_date_fin">
                  <td class="text-medium-emphasis text-caption">Ancienne date fin</td>
                  <td>{{ formatDate(avenant.ancienne_date_fin!) }}</td>
                </tr>
                <tr v-if="avenant.nouvelle_date_fin">
                  <td class="text-medium-emphasis text-caption">Nouvelle date fin</td>
                  <td class="font-weight-medium">{{ formatDate(avenant.nouvelle_date_fin) }}</td>
                </tr>
                <tr>
                  <td class="text-medium-emphasis text-caption">Créé le</td>
                  <td>{{ avenant.created_at ? formatDate(avenant.created_at.slice(0, 10)) : '-' }}</td>
                </tr>
                <tr v-if="avenant.createdBy">
                  <td class="text-medium-emphasis text-caption">Créé par</td>
                  <td>{{ avenant.createdBy.prenom }} {{ avenant.createdBy.nom }}</td>
                </tr>
                <tr v-if="avenant.approved_at">
                  <td class="text-medium-emphasis text-caption">Approuvé le</td>
                  <td>{{ formatDate(avenant.approved_at.slice(0, 10)) }}</td>
                </tr>
                <tr v-if="avenant.approvedBy">
                  <td class="text-medium-emphasis text-caption">Approuvé par</td>
                  <td>{{ avenant.approvedBy.prenom }} {{ avenant.approvedBy.nom }}</td>
                </tr>
              </tbody>
            </VTable>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="6">
        <VCard>
          <VCardTitle class="pa-4 text-subtitle-1">
            Justification & objet
          </VCardTitle>
          <VDivider />
          <VCardText>
            <p class="text-caption text-medium-emphasis mb-1">
              Justification
            </p>
            <p class="text-body-2 mb-4">
              {{ avenant.justification }}
            </p>
            <template v-if="avenant.nouvelle_description_objet">
              <p class="text-caption text-medium-emphasis mb-1">
                Nouvelle description de l'objet
              </p>
              <p class="text-body-2 mb-4">
                {{ avenant.nouvelle_description_objet }}
              </p>
            </template>
            <template v-if="avenant.commentaire_validation">
              <p class="text-caption text-medium-emphasis mb-1">
                Commentaire de validation
              </p>
              <p class="text-body-2" :class="avenant.statut === 'rejected' ? 'text-error' : ''">
                {{ avenant.commentaire_validation }}
              </p>
            </template>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>

  <div v-else class="d-flex justify-center align-center" style="min-height: 300px">
    <VProgressCircular indeterminate color="primary" />
  </div>

  <VSnackbar v-model="snackbar.show" :color="snackbar.color" timeout="4000" location="top right">
    {{ snackbar.text }}
  </VSnackbar>
</template>
