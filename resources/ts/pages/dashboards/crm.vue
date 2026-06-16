<script setup lang="ts">
import { useTheme } from 'vuetify'
import { hexToRgb } from '@layouts/utils'
import { useDashboard, formatCurrencyXOF } from '@/composables/useDashboard'
import { useAbility } from '@/plugins/casl/composables/useAbility'

definePage({
  meta: { action: 'view', subject: 'Dashboard' },
})

const router = useRouter()
const vuetifyTheme = useTheme()
const { can } = useAbility()

const {
  data,
  isLoading,
  error,
  pendingFilters,
  fetch,
  applyFilters,
  resetFilters,
} = useDashboard()

onMounted(fetch)

// ── Exercices disponibles ────────────────────────────────────────
const currentYear = new Date().getFullYear()
const exercices = Array.from({ length: 6 }, (_, i) => currentYear - i)

// ── Statuts contrats ─────────────────────────────────────────────
const statusConfig: Record<string, { label: string; color: string }> = {
  approved:  { label: 'Validé',    color: 'success' },
  submitted: { label: 'Soumis',    color: 'warning' },
  draft:     { label: 'Brouillon', color: 'secondary' },
  rejected:  { label: 'Rejeté',    color: 'error' },
  archived:  { label: 'Archivé',   color: 'secondary' },
  active:    { label: 'Actif',     color: 'info' },
}

function getStatusConfig(status: string) {
  return statusConfig[status] ?? { label: status, color: 'default' }
}

// ── KPI cards config ─────────────────────────────────────────────
const kpiCards = computed(() => {
  const k = data.value?.kpis
  return [
    {
      title: 'Contrats validés',
      value: k?.contracts_approved ?? 0,
      icon: 'tabler-file-check',
      color: 'success',
      isCurrency: false,
    },
    {
      title: 'Contrats en attente',
      value: k?.contracts_pending ?? 0,
      icon: 'tabler-file-time',
      color: 'warning',
      isCurrency: false,
    },
    {
      title: 'Avis',
      value: k?.avis_total ?? 0,
      icon: 'tabler-speakerphone',
      color: 'info',
      isCurrency: false,
    },
    {
      title: 'Dépouillements',
      value: k?.depouillements_total ?? 0,
      icon: 'tabler-list-check',
      color: 'primary',
      isCurrency: false,
    },
    {
      title: 'PV',
      value: k?.pv_total ?? 0,
      icon: 'tabler-clipboard-text',
      color: 'secondary',
      isCurrency: false,
    },
    {
      title: 'Fournisseurs',
      value: k?.suppliers_total ?? 0,
      icon: 'tabler-building-store',
      color: 'error',
      isCurrency: false,
    },
    {
      title: 'Paiements effectués (période)',
      value: k?.payments_total_amount ?? 0,
      icon: 'tabler-cash',
      color: 'success',
      isCurrency: true,
    },
    {
      title: 'Reste à payer',
      value: k?.remaining_to_pay ?? 0,
      icon: 'tabler-cash-off',
      color: 'error',
      isCurrency: true,
    },
  ]
})

// ── Chart: Contrats par statut (Donut) ───────────────────────────
const donutChartOptions = computed(() => {
  const theme = vuetifyTheme.current.value
  const colors = theme.colors
  const vars = theme.variables
  const legendColor = `rgba(${hexToRgb(colors['on-background'])},${vars['high-emphasis-opacity']})`

  const items = data.value?.charts?.contracts_by_status ?? []
  return {
    labels: items.map(i => getStatusConfig(i.status).label),
    series: items.map(i => i.count),
    options: {
      chart: { type: 'donut' },
      colors: ['#28C76F', '#FF9F43', '#7367F0', '#EA5455', '#00CFE8'],
      legend: {
        position: 'bottom',
        labels: { colors: legendColor },
        fontFamily: 'Public Sans',
      },
      dataLabels: { enabled: true },
      plotOptions: {
        pie: {
          donut: {
            size: '65%',
            labels: {
              show: true,
              total: {
                show: true,
                label: 'Total',
                color: legendColor,
                fontFamily: 'Public Sans',
                formatter: (w: any) => w.globals.seriesTotals.reduce((a: number, b: number) => a + b, 0),
              },
            },
          },
        },
      },
      tooltip: { theme: vuetifyTheme.name.value },
    },
  }
})

// ── Chart: Montants engagés vs payés (Bar grouped) ───────────────
const barChartOptions = computed(() => {
  const theme = vuetifyTheme.current.value
  const colors = theme.colors
  const vars = theme.variables
  const labelColor = `rgba(${hexToRgb(colors['on-surface'])},${vars['disabled-opacity']})`
  const borderColor = `rgba(${hexToRgb(String(vars['border-color']))},${vars['border-opacity']})`

  const items = data.value?.charts?.monthly_amounts ?? []
  const months = items.map(i => {
    const [y, m] = i.month.split('-')
    return new Date(Number(y), Number(m) - 1).toLocaleDateString('fr-FR', { month: 'short', year: '2-digit' })
  })

  return {
    series: [
      { name: 'Engagé', data: items.map(i => i.engaged) },
      { name: 'Payé',   data: items.map(i => i.paid) },
    ],
    options: {
      chart: { type: 'bar', toolbar: { show: false } },
      colors: ['#7367F0', '#28C76F'],
      plotOptions: {
        bar: { columnWidth: '45%', borderRadius: 4, borderRadiusApplication: 'end' },
      },
      dataLabels: { enabled: false },
      legend: {
        position: 'top',
        labels: { colors: labelColor },
        fontFamily: 'Public Sans',
      },
      xaxis: {
        categories: months,
        axisBorder: { show: true, color: borderColor },
        axisTicks: { show: false },
        labels: { style: { colors: labelColor, fontFamily: 'Public Sans' } },
      },
      yaxis: {
        labels: {
          style: { colors: labelColor, fontFamily: 'Public Sans' },
          formatter: (v: number) => {
            if (v >= 1_000_000) return `${(v / 1_000_000).toFixed(1)}M`
            if (v >= 1_000) return `${(v / 1_000).toFixed(0)}K`
            return String(v)
          },
        },
      },
      grid: { borderColor },
      tooltip: {
        theme: vuetifyTheme.name.value,
        y: { formatter: (v: number) => formatCurrencyXOF(v) },
      },
    },
  }
})

// ── Chart: Top fournisseurs (Bar horizontal) ─────────────────────
const suppliersChartOptions = computed(() => {
  const theme = vuetifyTheme.current.value
  const colors = theme.colors
  const vars = theme.variables
  const labelColor = `rgba(${hexToRgb(colors['on-surface'])},${vars['disabled-opacity']})`
  const borderColor = `rgba(${hexToRgb(String(vars['border-color']))},${vars['border-opacity']})`

  const items = data.value?.charts?.top_suppliers ?? []
  return {
    series: [{ name: 'Montant', data: items.map(i => i.amount) }],
    options: {
      chart: { type: 'bar', toolbar: { show: false } },
      colors: ['#FF9F43'],
      plotOptions: {
        bar: { horizontal: true, borderRadius: 4, borderRadiusApplication: 'end', barHeight: '55%' },
      },
      dataLabels: { enabled: false },
      xaxis: {
        categories: items.map(i => i.name),
        labels: {
          style: { colors: labelColor, fontFamily: 'Public Sans' },
          formatter: (v: number) => {
            if (v >= 1_000_000) return `${(v / 1_000_000).toFixed(1)}M`
            if (v >= 1_000) return `${(v / 1_000).toFixed(0)}K`
            return String(v)
          },
        },
      },
      yaxis: {
        labels: { style: { colors: labelColor, fontFamily: 'Public Sans' } },
      },
      grid: { borderColor },
      tooltip: {
        theme: vuetifyTheme.name.value,
        y: { formatter: (v: number) => formatCurrencyXOF(v) },
      },
    },
  }
})

// ── Navigation ───────────────────────────────────────────────────
function goToContract(id: number) {
  router.push({ name: 'apps-contrats-id', params: { id } })
}

// ── Colonnes tables ──────────────────────────────────────────────
const latestContractsHeaders = [
  { title: 'N°', key: 'numero', sortable: false },
  { title: 'Fournisseur', key: 'fournisseur', sortable: false },
  { title: 'Montant', key: 'montant', sortable: false },
  { title: 'Statut', key: 'statut', sortable: false },
  { title: 'Date', key: 'created_at', sortable: false },
]

const delayHeaders = [
  { title: 'N°', key: 'numero', sortable: false },
  { title: 'Fournisseur', key: 'fournisseur', sortable: false },
  { title: 'Étape', key: 'etape', sortable: false },
  { title: 'Retard (j)', key: 'days_late', sortable: false },
]

function formatDate(dateStr: string) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('fr-FR')
}
</script>

<template>
  <!-- Accès refusé -->
  <div
    v-if="!can('view', 'Dashboard')"
    class="d-flex flex-column align-center justify-center"
    style="min-height: 60vh;"
  >
    <VIcon
      icon="tabler-lock"
      size="64"
      color="error"
      class="mb-4"
    />
    <h3 class="text-h5 mb-2">
      Accès refusé
    </h3>
    <p class="text-body-2 text-medium-emphasis">
      Vous n'avez pas la permission d'accéder au tableau de bord.
    </p>
  </div>

  <div v-else>
    <!-- ── Erreur globale ─────────────────────────────────────── -->
    <VAlert
      v-if="error"
      color="error"
      variant="tonal"
      class="mb-6"
      closable
      @click:close="error = null"
    >
      {{ error }}
    </VAlert>

    <!-- ══════════════════════════════════════════════════════════
         A) HEADER + FILTRES
    ══════════════════════════════════════════════════════════ -->
    <VCard class="mb-6">
      <VCardText>
        <VRow
          align="center"
          class="gap-y-3"
        >
          <VCol
            cols="12"
            sm="6"
            md="3"
          >
            <AppDateTimePicker
              v-model="pendingFilters.from"
              label="Du"
              placeholder="AAAA-MM-JJ"
              density="comfortable"
            />
          </VCol>

          <VCol
            cols="12"
            sm="6"
            md="3"
          >
            <AppDateTimePicker
              v-model="pendingFilters.to"
              label="Au"
              placeholder="AAAA-MM-JJ"
              density="comfortable"
            />
          </VCol>

          <VCol
            cols="12"
            sm="6"
            md="2"
          >
            <AppSelect
              v-model="pendingFilters.exercice"
              :items="exercices"
              label="Exercice"
              density="comfortable"
            />
          </VCol>

          <VCol
            cols="12"
            sm="6"
            md="4"
            class="d-flex gap-3 flex-wrap"
          >
            <VBtn
              color="primary"
              :loading="isLoading"
              prepend-icon="tabler-filter"
              @click="applyFilters"
            >
              Appliquer
            </VBtn>
            <VBtn
              variant="tonal"
              color="secondary"
              prepend-icon="tabler-refresh"
              @click="resetFilters"
            >
              Réinitialiser
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- ══════════════════════════════════════════════════════════
         B) KPI CARDS
    ══════════════════════════════════════════════════════════ -->
    <VRow class="match-height mb-2">
      <VCol
        v-for="kpi in kpiCards"
        :key="kpi.title"
        cols="12"
        sm="6"
        md="4"
        lg="3"
      >
        <VCard>
          <VCardText>
            <div class="d-flex align-center justify-space-between">
              <div>
                <p class="text-sm text-medium-emphasis mb-1">
                  {{ kpi.title }}
                </p>
                <template v-if="isLoading">
                  <VSkeletonLoader
                    type="text"
                    width="100"
                  />
                </template>
                <h4
                  v-else
                  class="text-h4 font-weight-bold"
                >
                  {{ kpi.isCurrency ? formatCurrencyXOF(kpi.value as number) : kpi.value.toLocaleString('fr-FR') }}
                </h4>
              </div>
              <VAvatar
                :color="kpi.color"
                variant="tonal"
                rounded
                size="48"
              >
                <VIcon
                  :icon="kpi.icon"
                  size="28"
                />
              </VAvatar>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- ══════════════════════════════════════════════════════════
         QUICK ACTIONS
    ══════════════════════════════════════════════════════════ -->
    <!-- <VRow
      v-if="can('create', 'Avis') || can('create', 'Fournisseur') || can('create', 'Contrat')"
      class="mb-2"
    >
      <VCol cols="12">
        <VCard>
          <VCardText>
            <p class="text-sm font-weight-semibold text-uppercase text-medium-emphasis mb-3">
              Raccourcis
            </p>
            <div class="d-flex flex-wrap gap-3">
              <VBtn
                v-if="can('create', 'Avis')"
                color="info"
                variant="tonal"
                prepend-icon="tabler-speakerphone"
                :to="{ name: 'apps-passation-avis' }"
              >
                Nouvel Avis
              </VBtn>
              <VBtn
                v-if="can('create', 'Fournisseur')"
                color="warning"
                variant="tonal"
                prepend-icon="tabler-building-store"
                :to="{ name: 'apps-referentiels-fournisseurs' }"
              >
                Nouveau Fournisseur
              </VBtn>
              <VBtn
                v-if="can('create', 'Contrat')"
                color="success"
                variant="tonal"
                prepend-icon="tabler-file-plus"
                :to="{ name: 'apps-contrats' }"
              >
                Nouveau Contrat
              </VBtn>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow> -->

    <!-- ══════════════════════════════════════════════════════════
         C) GRAPHIQUES
    ══════════════════════════════════════════════════════════ -->
    <VRow class="match-height mb-2">
      <!-- Donut: contrats par statut -->
      <VCol
        cols="12"
        md="4"
      >
        <VCard height="100%">
          <VCardItem>
            <VCardTitle>Contrats par statut</VCardTitle>
          </VCardItem>
          <VCardText>
            <template v-if="isLoading">
              <div class="d-flex justify-center align-center py-10">
                <VProgressCircular
                  indeterminate
                  color="primary"
                />
              </div>
            </template>
            <template v-else-if="(data?.charts?.contracts_by_status?.length ?? 0) > 0">
              <VueApexCharts
                type="donut"
                height="280"
                :options="donutChartOptions.options"
                :series="donutChartOptions.series"
              />
            </template>
            <div
              v-else
              class="text-center text-medium-emphasis py-10"
            >
              Aucune donnée
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Bar: montants engagés vs payés -->
      <VCol
        cols="12"
        md="8"
      >
        <VCard height="100%">
          <VCardItem>
            <VCardTitle>Montants engagés vs payés</VCardTitle>
            <VCardSubtitle>Engagés et payés par mois (XOF) — 12 derniers mois</VCardSubtitle>
          </VCardItem>
          <VCardText>
            <template v-if="isLoading">
              <div class="d-flex justify-center align-center py-10">
                <VProgressCircular
                  indeterminate
                  color="primary"
                />
              </div>
            </template>
            <template v-else-if="(data?.charts?.monthly_amounts?.length ?? 0) > 0">
              <VueApexCharts
                type="bar"
                height="280"
                :options="barChartOptions.options"
                :series="barChartOptions.series"
              />
            </template>
            <div
              v-else
              class="text-center text-medium-emphasis py-10"
            >
              Aucune donnée
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Bar horizontal: top fournisseurs -->
    <VRow class="mb-2">
      <VCol cols="12">
        <VCard>
          <VCardItem>
            <VCardTitle>Top fournisseurs</VCardTitle>
            <VCardSubtitle>Par montant contractualisé (XOF)</VCardSubtitle>
          </VCardItem>
          <VCardText>
            <template v-if="isLoading">
              <div class="d-flex justify-center align-center py-8">
                <VProgressCircular
                  indeterminate
                  color="primary"
                />
              </div>
            </template>
            <template v-else-if="(data?.charts?.top_suppliers?.length ?? 0) > 0">
              <VueApexCharts
                type="bar"
                height="260"
                :options="suppliersChartOptions.options"
                :series="suppliersChartOptions.series"
              />
            </template>
            <div
              v-else
              class="text-center text-medium-emphasis py-8"
            >
              Aucune donnée
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- ══════════════════════════════════════════════════════════
         D) TABLES
    ══════════════════════════════════════════════════════════ -->
    <VRow class="match-height">
      <!-- Derniers contrats -->
      <VCol
        cols="12"
        :md="(data?.tables?.contracts_in_delay?.length ?? 0) > 0 ? 7 : 12"
      >
        <VCard height="100%">
          <VCardItem>
            <VCardTitle>Derniers contrats</VCardTitle>
          </VCardItem>
          <VCardText class="pa-0">
            <template v-if="isLoading">
              <VSkeletonLoader
                type="table-row@5"
                class="pa-2"
              />
            </template>
            <VDataTable
              v-else
              :headers="latestContractsHeaders"
              :items="data?.tables?.latest_contracts ?? []"
              :items-per-page="10"
              hide-default-footer
              hover
              class="text-no-wrap"
              @click:row="(_: any, { item }: any) => goToContract(item.id)"
            >
              <template #item.montant="{ item }">
                {{ formatCurrencyXOF(item.montant) }}
              </template>
              <template #item.statut="{ item }">
                <VChip
                  :color="getStatusConfig(item.statut).color"
                  size="small"
                  label
                >
                  {{ getStatusConfig(item.statut).label }}
                </VChip>
              </template>
              <template #item.created_at="{ item }">
                {{ formatDate(item.created_at) }}
              </template>
              <template #no-data>
                <div class="text-center text-medium-emphasis py-6">
                  Aucun contrat
                </div>
              </template>
            </VDataTable>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Contrats en retard -->
      <VCol
        v-if="(data?.tables?.contracts_in_delay?.length ?? 0) > 0"
        cols="12"
        md="5"
      >
        <VCard height="100%">
          <VCardItem>
            <VCardTitle>Contrats en retard</VCardTitle>
            <template #append>
              <VChip
                color="error"
                size="small"
                label
              >
                {{ data?.tables?.contracts_in_delay?.length ?? 0 }}
              </VChip>
            </template>
          </VCardItem>
          <VCardText class="pa-0">
            <VDataTable
              :headers="delayHeaders"
              :items="data?.tables?.contracts_in_delay ?? []"
              :items-per-page="10"
              hide-default-footer
              hover
              class="text-no-wrap"
              @click:row="(_: any, { item }: any) => goToContract(item.id)"
            >
              <template #item.days_late="{ item }">
                <VChip
                  color="error"
                  size="small"
                  label
                >
                  {{ item.days_late }}j
                </VChip>
              </template>
              <template #no-data>
                <div class="text-center text-medium-emphasis py-6">
                  Aucun retard
                </div>
              </template>
            </VDataTable>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
