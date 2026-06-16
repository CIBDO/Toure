<script setup lang="ts">
import { useReportSuppliers } from '@/composables/useReports'
import { formatCurrencyXOF } from '@/composables/useDashboard'

definePage({ meta: { title: 'Rapport Performance Fournisseurs', action: 'view', subject: 'Report' } })

const { data, isLoading, error, filters, fetch, applyFilters, resetFilters } = useReportSuppliers()
const pending = ref({ ...filters.value })

const currentYear = new Date().getFullYear()
const exercices = Array.from({ length: 6 }, (_, i) => currentYear - i)

const apply = () => {
  applyFilters({
    date_from: pending.value.date_from,
    date_to: pending.value.date_to,
    exercice: pending.value.exercice,
  })
}

const reset = () => {
  pending.value = {
    date_from: `${currentYear}-01-01`,
    date_to: `${currentYear}-12-31`,
    exercice: currentYear,
  }
  resetFilters()
}

const top10 = computed(() => data.value?.top_10 ?? [])
const tableData = computed(() => data.value?.data ?? [])
const totalFournisseurs = computed(() => data.value?.indicators?.total_fournisseurs ?? 0)

const chartSeries = computed(() => [{
  name: 'Montant attribué',
  data: top10.value.map((s: any) => s.montant_attribue ?? 0),
}])
const chartOptions = computed(() => ({
  chart: { type: 'bar' as const },
  plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
  xaxis: {
    categories: top10.value.map((s: any) => s.fournisseur?.raison_sociale ?? 'Inconnu').slice(0, 10),
    labels: { formatter: (v: number) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(v) },
  },
  dataLabels: { enabled: false },
  tooltip: { y: { formatter: (v: number) => formatCurrencyXOF(v) } },
}))

onMounted(fetch)
</script>

<template>
  <div>
    <VCard>
      <VCardItem>
        <VCardTitle class="d-flex align-center gap-2">
          <VIcon icon="tabler-building-store" size="24" />
          Rapport Performance Fournisseurs
        </VCardTitle>
        <VCardSubtitle>Top fournisseurs par montant attribué et délai moyen de paiement</VCardSubtitle>
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

        <VCard v-if="totalFournisseurs > 0 && !isLoading" variant="tonal" color="primary" class="mb-4">
          <VCardText>
            <span class="text-caption">Nombre de fournisseurs ayant au moins un contrat</span>
            <div class="text-h6">
              {{ totalFournisseurs }}
            </div>
          </VCardText>
        </VCard>

        <div v-if="top10.length && !isLoading" class="mb-4">
          <div class="text-subtitle-1 mb-2">
            Top 10 fournisseurs (bar chart)
          </div>
          <VueApexCharts
            type="bar"
            :options="chartOptions"
            :series="chartSeries"
            height="320"
          />
        </div>

        <div class="text-subtitle-1 mb-2">
          Liste des fournisseurs ({{ tableData.length }})
        </div>
        <VTable v-if="!isLoading" class="report-table">
          <thead>
            <tr>
              <th>Fournisseur</th>
              <th class="text-end">Nb contrats</th>
              <th class="text-end">Montant attribué</th>
              <th class="text-end">Montant payé</th>
              <th class="text-end">Délai moyen (jours)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in tableData" :key="s.fournisseur_id">
              <td>{{ s.fournisseur?.raison_sociale ?? '-' }}</td>
              <td class="text-end">{{ s.total_contrats }}</td>
              <td class="text-end">{{ formatCurrencyXOF(s.montant_attribue) }}</td>
              <td class="text-end">{{ formatCurrencyXOF(s.montant_paye) }}</td>
              <td class="text-end">{{ s.delai_moyen_paiement_jours ?? '-' }}</td>
            </tr>
            <tr v-if="tableData.length === 0">
              <td colspan="5" class="text-center text-medium-emphasis py-4">
                Aucun fournisseur pour les critères sélectionnés.
              </td>
            </tr>
          </tbody>
        </VTable>
        <VProgressLinear v-else indeterminate color="primary" />
      </VCardText>
    </VCard>
  </div>
</template>
