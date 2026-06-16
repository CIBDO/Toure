<script setup lang="ts">
import { useReportFinancial } from '@/composables/useReports'
import { formatCurrencyXOF } from '@/composables/useDashboard'
import { reportService } from '@/services/reportService'
import { useApi } from '@/composables/useApi'

definePage({ meta: { title: 'Rapport Situation Financière', action: 'view', subject: 'Report' } })

const { data, isLoading, error, filters, fetch, applyFilters, resetFilters } = useReportFinancial()
const snackbar = ref({ show: false, text: '', color: 'success' })
const pending = ref({ ...filters.value })

const { data: fournisseursData } = await useApi<any>('/fournisseurs?itemsPerPage=-1').json()
const fournisseursList = computed(() =>
  fournisseursData.value?.data?.map((f: any) => ({ title: f.raison_sociale, value: f.id })) ?? [],
)

const currentYear = new Date().getFullYear()
const exercices = Array.from({ length: 6 }, (_, i) => currentYear - i)
const statutOptions = [
  { title: 'Brouillon', value: 'draft' },
  { title: 'Soumis', value: 'submitted' },
  { title: 'Approuvé', value: 'approved' },
  { title: 'Rejeté', value: 'rejected' },
  { title: 'Archivé', value: 'archived' },
]

const statutColor = (s: string) => ({
  draft: 'secondary', submitted: 'info', approved: 'success', rejected: 'error', archived: 'default',
}[s] || 'default')

const apply = () => {
  applyFilters({
    date_from: pending.value.date_from,
    date_to: pending.value.date_to,
    exercice: pending.value.exercice,
    fournisseur_id: pending.value.fournisseur_id,
    statut: pending.value.statut,
    page: 1,
  })
}

const reset = () => {
  pending.value = {
    date_from: `${currentYear}-01-01`,
    date_to: `${currentYear}-12-31`,
    exercice: currentYear,
    fournisseur_id: undefined,
    statut: undefined,
    per_page: 15,
  }
  resetFilters()
}

const exportExcel = async () => {
  try {
    await reportService.downloadExport('financial', 'excel', {
      date_from: pending.value.date_from,
      date_to: pending.value.date_to,
      exercice: pending.value.exercice,
      fournisseur_id: pending.value.fournisseur_id,
      statut: pending.value.statut,
    })
    snackbar.value = { show: true, text: 'Export Excel téléchargé', color: 'success' }
  } catch {
    snackbar.value = { show: true, text: 'Erreur lors de l\'export', color: 'error' }
  }
}

const exportPdf = async () => {
  try {
    await reportService.downloadExport('financial', 'pdf', {
      date_from: pending.value.date_from,
      date_to: pending.value.date_to,
      exercice: pending.value.exercice,
      fournisseur_id: pending.value.fournisseur_id,
      statut: pending.value.statut,
    })
    snackbar.value = { show: true, text: 'Export PDF téléchargé', color: 'success' }
  } catch {
    snackbar.value = { show: true, text: 'Erreur lors de l\'export', color: 'error' }
  }
}

const ind = computed(() => data.value?.indicators ?? null)
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
          <VIcon icon="tabler-report-money" size="24" />
          Rapport Situation Financière
        </VCardTitle>
        <VCardSubtitle>Engagements, paiements et reste à payer</VCardSubtitle>
      </VCardItem>
      <VCardText>
        <VRow class="mb-4">
          <VCol cols="12" md="2">
            <VTextField v-model="pending.date_from" label="Date du" type="date" density="compact" />
          </VCol>
          <VCol cols="12" md="2">
            <VTextField v-model="pending.date_to" label="Date au" type="date" density="compact" />
          </VCol>
          <VCol cols="12" md="2">
            <VSelect v-model="pending.exercice" :items="exercices" label="Exercice" density="compact" clearable />
          </VCol>
          <VCol cols="12" md="2">
            <VSelect v-model="pending.fournisseur_id" :items="fournisseursList" label="Fournisseur" density="compact" clearable />
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
            <VBtn variant="tonal" color="success" :loading="isLoading" @click="exportExcel">
              <VIcon start icon="tabler-file-spreadsheet" />
              Export Excel
            </VBtn>
            <VBtn variant="tonal" color="error" :loading="isLoading" @click="exportPdf">
              <VIcon start icon="tabler-file-type-pdf" />
              Export PDF
            </VBtn>
          </VCol>
        </VRow>

        <VAlert v-if="error" type="error" variant="tonal" class="mt-3" closable>
          {{ error }}
        </VAlert>

        <VProgressLinear v-if="isLoading" indeterminate color="primary" class="mb-3" />

        <VRow v-if="ind && !isLoading" class="mb-4">
          <VCol cols="12" sm="6" md="4">
            <VCard variant="tonal" color="primary">
              <VCardText>
                <div class="text-caption">Total engagé</div>
                <div class="text-h6">{{ formatCurrencyXOF(ind.total_engage) }}</div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="12" sm="6" md="4">
            <VCard variant="tonal" color="success">
              <VCardText>
                <div class="text-caption">Total payé</div>
                <div class="text-h6">{{ formatCurrencyXOF(ind.total_paye) }}</div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="12" sm="6" md="4">
            <VCard variant="tonal" color="warning">
              <VCardText>
                <div class="text-caption">Reste à payer</div>
                <div class="text-h6">{{ formatCurrencyXOF(ind.reste_a_payer) }}</div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="12" sm="6" md="4">
            <VCard variant="tonal" color="info">
              <VCardText>
                <div class="text-caption">Contrats payés total</div>
                <div class="text-h6">{{ ind.contrats_payes_total }}</div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="12" sm="6" md="4">
            <VCard variant="tonal" color="secondary">
              <VCardText>
                <div class="text-caption">Contrats partiellement payés</div>
                <div class="text-h6">{{ ind.contrats_partiellement_payes }}</div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="12" sm="6" md="4">
            <VCard variant="tonal" color="error">
              <VCardText>
                <div class="text-caption">Contrats non payés</div>
                <div class="text-h6">{{ ind.contrats_non_payes }}</div>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>

        <div class="text-subtitle-1 mb-2">
          Détail ({{ total }})
        </div>
        <VTable v-if="!isLoading" class="report-table">
          <thead>
            <tr>
              <th>Référence</th>
              <th>Fournisseur</th>
              <th class="text-end">Montant initial</th>
              <th class="text-end">Montant actuel</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in tableData" :key="c.id">
              <td>{{ c.reference ?? c.numero ?? '-' }}</td>
              <td>{{ c.fournisseur?.raison_sociale ?? '-' }}</td>
              <td class="text-end">{{ formatCurrencyXOF(c.montant_initial) }}</td>
              <td class="text-end">{{ formatCurrencyXOF(c.montant_actuel ?? c.montant_initial) }}</td>
              <td>
                <VChip :color="statutColor(c.statut)" size="x-small" variant="tonal">{{ c.statut }}</VChip>
              </td>
            </tr>
            <tr v-if="tableData.length === 0">
              <td colspan="5" class="text-center text-medium-emphasis py-4">
                Aucune donnée pour les critères sélectionnés.
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
  <VSnackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000" location="bottom">
    {{ snackbar.text }}
  </VSnackbar>
</template>
