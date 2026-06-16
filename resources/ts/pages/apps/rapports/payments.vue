<script setup lang="ts">
import { useReportPayments } from '@/composables/useReports'
import { formatCurrencyXOF } from '@/composables/useDashboard'
import { useApi } from '@/composables/useApi'

definePage({ meta: { title: 'Rapport Paiements', action: 'view', subject: 'Report' } })

const { data, isLoading, error, filters, fetch, applyFilters, resetFilters } = useReportPayments()
const pending = ref({ ...filters.value })

const { data: fournisseursData } = await useApi<any>('/fournisseurs?itemsPerPage=-1').json()
const fournisseursList = computed(() =>
  fournisseursData.value?.data?.map((f: any) => ({ title: f.raison_sociale, value: f.id })) ?? [],
)

const currentYear = new Date().getFullYear()
const exercices = Array.from({ length: 6 }, (_, i) => currentYear - i)
const modeOptions = [
  { title: 'Virement', value: 'virement' },
  { title: 'Chèque', value: 'cheque' },
  { title: 'Espèces', value: 'especes' },
  { title: 'Autre', value: 'autre' },
]
const statutOptions = [
  { title: 'Brouillon', value: 'draft' },
  { title: 'Soumis', value: 'submitted' },
  { title: 'Approuvé', value: 'approved' },
  { title: 'Rejeté', value: 'rejected' },
]

const statutColor = (s: string) => ({
  draft: 'secondary', submitted: 'info', approved: 'success', rejected: 'error',
}[s] || 'default')

const apply = () => {
  applyFilters({
    date_from: pending.value.date_from,
    date_to: pending.value.date_to,
    mode_paiement: pending.value.mode_paiement,
    fournisseur_id: pending.value.fournisseur_id,
    exercice: pending.value.exercice,
    page: 1,
  })
}

const reset = () => {
  pending.value = {
    date_from: `${currentYear}-01-01`,
    date_to: `${currentYear}-12-31`,
    mode_paiement: undefined,
    fournisseur_id: undefined,
    exercice: undefined,
    per_page: 15,
  }
  resetFilters()
}

const tableData = computed(() => data.value?.data ?? [])
const total = computed(() => data.value?.total ?? 0)
const currentPage = computed(() => data.value?.current_page ?? 1)
const lastPage = computed(() => data.value?.last_page ?? 1)

const onPageChange = (page: number) => {
  applyFilters({ page, per_page: filters.value.per_page })
}

onMounted(fetch)
</script>

<template>
  <div>
    <VCard>
      <VCardItem>
        <VCardTitle class="d-flex align-center gap-2">
          <VIcon icon="tabler-credit-card" size="24" />
          Rapport Paiements
        </VCardTitle>
        <VCardSubtitle>Liste des paiements avec référence, contrat, engagement et montant</VCardSubtitle>
      </VCardItem>
      <VCardText>
        <VRow class="mb-4">
          <VCol cols="12" md="2">
            <VTextField v-model="pending.date_from" label="Date paiement du" type="date" density="compact" />
          </VCol>
          <VCol cols="12" md="2">
            <VTextField v-model="pending.date_to" label="Date paiement au" type="date" density="compact" />
          </VCol>
          <VCol cols="12" md="2">
            <VSelect v-model="pending.exercice" :items="exercices" label="Exercice" density="compact" clearable />
          </VCol>
          <VCol cols="12" md="2">
            <VSelect v-model="pending.mode_paiement" :items="modeOptions" label="Mode paiement" density="compact" clearable />
          </VCol>
          <VCol cols="12" md="2">
            <VSelect v-model="pending.fournisseur_id" :items="fournisseursList" label="Fournisseur" density="compact" clearable />
          </VCol>
        </VRow>
        <VRow>
          <VCol class="d-flex gap-2 flex-wrap">
            <VBtn color="primary" @click="apply">
              Appliquer
            </VBtn>
            <VBtn variant="tonal" @click="reset">
              Réinitialiser
            </VBtn>
          </VCol>
        </VRow>

        <VAlert v-if="error" type="error" variant="tonal" class="mt-3" closable>
          {{ error }}
        </VAlert>

        <VProgressLinear v-if="isLoading" indeterminate color="primary" class="mb-3" />

        <div class="text-subtitle-1 mb-2">
          Paiements ({{ total }})
        </div>
        <VTable v-if="!isLoading" class="report-table">
          <thead>
            <tr>
              <th>Référence</th>
              <th>Contrat</th>
              <th>Engagement</th>
              <th class="text-end">Montant</th>
              <th>Date</th>
              <th>Mode</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in tableData" :key="p.id">
              <td>{{ p.reference }}</td>
              <td>{{ p.contrat?.reference ?? '-' }}</td>
              <td>{{ p.engagement?.numero ?? '-' }}</td>
              <td class="text-end">{{ formatCurrencyXOF(p.montant) }}</td>
              <td>{{ p.date_paiement ? new Date(p.date_paiement).toLocaleDateString('fr-FR') : '-' }}</td>
              <td>{{ p.mode_paiement }}</td>
              <td>
                <VChip :color="statutColor(p.statut)" size="x-small" variant="tonal">{{ p.statut }}</VChip>
              </td>
            </tr>
            <tr v-if="tableData.length === 0">
              <td colspan="7" class="text-center text-medium-emphasis py-4">
                Aucun paiement pour les critères sélectionnés.
              </td>
            </tr>
          </tbody>
        </VTable>
        <VProgressLinear v-else indeterminate color="primary" />

        <VRow v-if="lastPage > 1 && !isLoading" class="mt-3">
          <VCol class="d-flex justify-end">
            <VPagination :model-value="currentPage" :length="lastPage" total-visible="7" @update:model-value="onPageChange" />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
  </div>
</template>
