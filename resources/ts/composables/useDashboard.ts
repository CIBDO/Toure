/**
 * Composable Dashboard - CANAM Contract Manager
 * Gère le fetch, l'état, le loading, les erreurs et les filtres
 */

import { dashboardService } from '@/services/dashboardService'
import type { DashboardFilters, DashboardSummary } from '@/services/dashboardService'

const STORAGE_KEY = 'canam_dashboard_filters'

const currentYear = new Date().getFullYear()

function getDefaultFilters(): DashboardFilters {
  return {
    from: `${currentYear}-01-01`,
    to: `${currentYear}-12-31`,
    exercice: currentYear,
  }
}

function loadFiltersFromStorage(): DashboardFilters {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (raw) return { ...getDefaultFilters(), ...JSON.parse(raw) }
  }
  catch { /* ignore */ }
  return getDefaultFilters()
}

function saveFiltersToStorage(filters: DashboardFilters) {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(filters))
  }
  catch { /* ignore */ }
}

export function formatCurrencyXOF(amount: number | null | undefined): string {
  if (amount == null) return '0 XOF'
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}

export function useDashboard() {
  const data = ref<DashboardSummary | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const filters = ref<DashboardFilters>(loadFiltersFromStorage())
  const pendingFilters = ref<DashboardFilters>({ ...filters.value })

  const fetch = async () => {
    isLoading.value = true
    error.value = null
    try {
      data.value = await dashboardService.getSummary(filters.value)
    }
    catch (err: any) {
      const msg = err?.response?._data?.message
        || err?.data?.message
        || err?.message
        || 'Erreur lors du chargement du tableau de bord'
      error.value = msg
    }
    finally {
      isLoading.value = false
    }
  }

  const applyFilters = () => {
    filters.value = { ...pendingFilters.value }
    saveFiltersToStorage(filters.value)
    fetch()
  }

  const resetFilters = () => {
    const defaults = getDefaultFilters()
    filters.value = { ...defaults }
    pendingFilters.value = { ...defaults }
    saveFiltersToStorage(filters.value)
    fetch()
  }

  return {
    data,
    isLoading,
    error,
    filters,
    pendingFilters,
    fetch,
    applyFilters,
    resetFilters,
  }
}
