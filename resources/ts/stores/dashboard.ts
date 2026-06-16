import { defineStore } from 'pinia'
import { useApi } from '@/composables/useApi'

export interface DashboardStats {
  contrats: {
    total: number
    approuves: number
    non_valides: number
    archives: number
    montant_total: number
  }
  avis: { total: number; publies: number; clos: number }
  depouillements: { total: number }
  pvs: { total: number }
  fournisseurs: { total: number }
  comptes_budget: { total: number }
  utilisateurs: { total: number }
  charts: {
    contrats_par_mois: Array<{ mois: string; total: number }>
    contrats_par_statut: Array<{ statut: string; total: number }>
  }
}

export const useDashboardStore = defineStore('dashboard', () => {
  const stats = ref<DashboardStats | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const fetchStats = async (params: Record<string, any> = {}) => {
    isLoading.value = true
    error.value = null
    try {
      const query = new URLSearchParams(params).toString()
      const { data } = await useApi(`/dashboard/stats?${query}`).json()
      stats.value = data.value
    }
    catch (e: any) {
      error.value = e.message
    }
    finally {
      isLoading.value = false
    }
  }

  return { stats, isLoading, error, fetchStats }
})
