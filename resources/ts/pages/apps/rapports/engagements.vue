<script setup lang="ts">
import { useReportEngagements } from '@/composables/useReports'
import { formatCurrencyXOF } from '@/composables/useDashboard'
import { useApi } from '@/composables/useApi'

definePage({ meta: { title: 'Rapport Engagements', action: 'view', subject: 'Report' } })

const { data, isLoading, error, filters, fetch, applyFilters, resetFilters } = useReportEngagements()
const pending = ref({ ...filters.value })

const { data: comptesData } = await useApi<any>('/comptes-budget?itemsPerPage=-1').json()
const comptesList = computed(() =>
  comptesData.value?.data?.map((c: any) => ({ title: `${c.code} — ${c.libelle}`, value: c.id })) ?? [],
)

const currentYear = new Date().getFullYear()
const exercices = Array.from({ length: 6 }, (_, i) => currentYear - i)
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
    exercice: pending.value.exercice,
    compte_budget_id: pending.value.compte_budget_id,
    statut: pending.value.statut,
    page: 1,
  })
}

const reset = () => {
  pending.value = {
    date_from: `${currentYear}-01-01`,
    date_to: `${currentYear}-12-31`,
    exercice: String(currentYear),
    compte_budget_id: undefined,
    statut: undefined,
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
          <VIcon icon="tabler-receipt" size="24" />
          Rapport Engagements
        </VCardTitle>
        <VCardSubtitle>Liste des engagements budgétaires avec montants engagés et payés</VCardSubtitle>
      </VCardItem>
      <VCardText>
        <VRow class="mb-4">
          <VCol cols="12" md="2">
            <VTextField v-model="pending.date_from" label="Date engagement du" type="date" density="compact" />
          </VCol>
          <VCol cols="12" md="2">
            <VTextField v-model="pending.date_to" label="Date engagement au" type="date" density="compact" />
          </VCol>
          <VCol cols="12" md="2">
            <VSelect v-model="pending.exercice" :items="exercices" label="Exercice" density="compact" clearable />
          </VCol>
          <VCol cols="12" md="2">
            <VSelect v-model="pending.compte_budget_id" :items="comptesList" label="Compte budget" density="compact" clearable />
          </VCol>
          <VCol cols="12" md="2">
            <VSelect v-model="pending.statut" :items="statutOptions" label="Statut" density="compact" clearable />
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
          Engagements ({{ total }})
        </div>
        <VTable v-if="!isLoading" class="report-table">
          <thead>
            <tr>
              <th>N° engagement</th>
              <th>Contrat</th>
              <th class="text-end">Montant engagé</th>
              <th class="text-end">Montant payé</th>
              <th class="text-end">Reste engagement</th>
              <th>Statut</th>
              <th>Date engagement</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="e in tableData" :key="e.id">
              <td>{{ e.numero }}</td>
              <td>{{ e.contrat?.reference ?? '-' }}</td>
              <td class="text-end">{{ formatCurrencyXOF(e.montant_engage) }}</td>
              <td class="text-end">{{ formatCurrencyXOF(e.montant_paye) }}</td>
              <td class="text-end">{{ formatCurrencyXOF(e.reste_engagement) }}</td>
              <td>
                <VChip :color="statutColor(e.statut)" size="x-small" variant="tonal">{{ e.statut }}</VChip>
              </td>
              <td>{{ e.date_engagement ? new Date(e.date_engagement).toLocaleDateString('fr-FR') : '-' }}</td>
            </tr>
            <tr v-if="tableData.length === 0">
              <td colspan="7" class="text-center text-medium-emphasis py-4">
                Aucun engagement pour les critères sélectionnés.
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
