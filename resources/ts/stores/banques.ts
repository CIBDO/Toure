import { defineStore } from 'pinia'
import { useApi } from '@/composables/useApi'

export interface Banque {
  id: number
  uuid: string
  code: string
  libelle: string
  sigle?: string
  adresse?: string
  telephone?: string
  email?: string
  actif: boolean
  created_at?: string
}

export const useBanquesStore = defineStore('banques', () => {
  const banques = ref<Banque[]>([])
  const total = ref(0)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const fetchBanques = async (params: Record<string, any> = {}) => {
    isLoading.value = true
    error.value = null
    try {
      const query = new URLSearchParams(params).toString()
      const { data } = await useApi(`/banques?${query}`).json()
      banques.value = data.value?.data ?? []
      total.value = data.value?.total ?? 0
    }
    catch (e: any) {
      error.value = e.message
    }
    finally {
      isLoading.value = false
    }
  }

  const createBanque = async (payload: Partial<Banque>) => {
    const { data, error: err } = await useApi('/banques').post(payload).json()
    if (err.value) throw err.value
    return data.value
  }

  const updateBanque = async (id: number, payload: Partial<Banque>) => {
    const { data, error: err } = await useApi(`/banques/${id}`).put(payload).json()
    if (err.value) throw err.value
    return data.value
  }

  const deleteBanque = async (id: number) => {
    const { error: err } = await useApi(`/banques/${id}`).delete().json()
    if (err.value) throw err.value
  }

  return { banques, total, isLoading, error, fetchBanques, createBanque, updateBanque, deleteBanque }
})
