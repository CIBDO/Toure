import { defineStore } from 'pinia'
import { useApi } from '@/composables/useApi'

export interface CompteBudget {
  id: number
  uuid: string
  code: string
  libelle: string
  exercice: string
  montant_alloue: number
  montant_engage: number
  montant_disponible: number
  description?: string
  actif: boolean
}

export const useComptesBudgetStore = defineStore('comptesBudget', () => {
  const comptes = ref<CompteBudget[]>([])
  const total = ref(0)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const fetchComptes = async (params: Record<string, any> = {}) => {
    isLoading.value = true
    error.value = null
    try {
      const query = new URLSearchParams(params).toString()
      const { data } = await useApi(`/comptes-budget?${query}`).json()
      comptes.value = data.value?.data ?? []
      total.value = data.value?.total ?? 0
    }
    catch (e: any) {
      error.value = e.message
    }
    finally {
      isLoading.value = false
    }
  }

  const createCompte = async (payload: Partial<CompteBudget>) => {
    const { data, error: err } = await useApi('/comptes-budget').post(payload).json()
    if (err.value) throw err.value
    return data.value
  }

  const updateCompte = async (id: number, payload: Partial<CompteBudget>) => {
    const { data, error: err } = await useApi(`/comptes-budget/${id}`).put(payload).json()
    if (err.value) throw err.value
    return data.value
  }

  const deleteCompte = async (id: number) => {
    const { error: err } = await useApi(`/comptes-budget/${id}`).delete().json()
    if (err.value) throw err.value
  }

  return { comptes, total, isLoading, error, fetchComptes, createCompte, updateCompte, deleteCompte }
})
