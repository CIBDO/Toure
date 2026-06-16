import { defineStore } from 'pinia'
import { useApi } from '@/composables/useApi'

export interface DomaineActivite {
  id: number
  uuid: string
  code: string
  libelle: string
  description?: string
  actif: boolean
}

export const useDomainesStore = defineStore('domaines', () => {
  const domaines = ref<DomaineActivite[]>([])
  const total = ref(0)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const fetchDomaines = async (params: Record<string, any> = {}) => {
    isLoading.value = true
    error.value = null
    try {
      const query = new URLSearchParams(params).toString()
      const { data } = await useApi(`/domaines?${query}`).json()
      domaines.value = data.value?.data ?? []
      total.value = data.value?.total ?? 0
    }
    catch (e: any) {
      error.value = e.message
    }
    finally {
      isLoading.value = false
    }
  }

  const createDomaine = async (payload: Partial<DomaineActivite>) => {
    const { data, error: err } = await useApi('/domaines').post(payload).json()
    if (err.value) throw err.value
    return data.value
  }

  const updateDomaine = async (id: number, payload: Partial<DomaineActivite>) => {
    const { data, error: err } = await useApi(`/domaines/${id}`).put(payload).json()
    if (err.value) throw err.value
    return data.value
  }

  const deleteDomaine = async (id: number) => {
    const { error: err } = await useApi(`/domaines/${id}`).delete().json()
    if (err.value) throw err.value
  }

  return { domaines, total, isLoading, error, fetchDomaines, createDomaine, updateDomaine, deleteDomaine }
})
