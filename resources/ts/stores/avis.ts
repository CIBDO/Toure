import { defineStore } from 'pinia'
import { useApi } from '@/composables/useApi'

export interface AvisItem {
  id?: number
  ordre: number
  expression_besoin_id?: number
  designation: string
  description_detaillee?: string
  quantite: number
  unite?: string
  lieu?: string
}

export interface Avis {
  id: number
  uuid: string
  reference: string
  objet: string
  mode_passation: string
  article_pour?: string
  article_relatif?: string
  exercice: string
  duree?: number
  delai?: number
  date_limite_depot?: string
  date_ouverture_plis?: string
  date_publication?: string
  statut: 'draft' | 'submitted' | 'approved' | 'rejected' | 'published' | 'closed' | 'cancelled'
  observations?: string
  motif_rejet?: string
  items?: AvisItem[]
  fournisseurs?: any[]
  created_at?: string
}

export const useAvisStore = defineStore('avis', () => {
  const avisList = ref<Avis[]>([])
  const total = ref(0)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const currentAvis = ref<Avis | null>(null)

  const fetchAvis = async (params: Record<string, any> = {}) => {
    isLoading.value = true
    error.value = null
    try {
      const query = new URLSearchParams(params).toString()
      const { data } = await useApi(`/avis?${query}`).json()
      avisList.value = data.value?.data ?? []
      total.value = data.value?.total ?? 0
    }
    catch (e: any) {
      error.value = e.message
    }
    finally {
      isLoading.value = false
    }
  }

  const fetchAvisById = async (id: number) => {
    const { data } = await useApi(`/avis/${id}`).json()
    currentAvis.value = data.value
    return data.value
  }

  const createAvis = async (payload: Partial<Avis>) => {
    const { data, error: err } = await useApi('/avis').post(payload).json()
    if (err.value) throw err.value
    return data.value
  }

  const updateAvis = async (id: number, payload: Partial<Avis>) => {
    const { data, error: err } = await useApi(`/avis/${id}`).put(payload).json()
    if (err.value) throw err.value
    return data.value
  }

  const deleteAvis = async (id: number) => {
    const { error: err } = await useApi(`/avis/${id}`).delete().json()
    if (err.value) throw err.value
  }

  const submitAvis = async (id: number, body: any = {}) => {
    const { data, error: err } = await useApi(`/avis/${id}/submit`).post(body).json()
    if (err.value) throw err.value
    return data.value
  }

  const approveAvis = async (id: number, body: any = {}) => {
    const { data, error: err } = await useApi(`/avis/${id}/approve`).post(body).json()
    if (err.value) throw err.value
    return data.value
  }

  const rejectAvis = async (id: number, body: any = {}) => {
    const { data, error: err } = await useApi(`/avis/${id}/reject`).post(body).json()
    if (err.value) throw err.value
    return data.value
  }

  const publishAvis = async (id: number, body: any = {}) => {
    const { data, error: err } = await useApi(`/avis/${id}/publish`).post(body).json()
    if (err.value) throw err.value
    return data.value
  }

  const closeAvis = async (id: number, body: any = {}) => {
    const { data, error: err } = await useApi(`/avis/${id}/close`).post(body).json()
    if (err.value) throw err.value
    return data.value
  }

  return {
    avisList, total, isLoading, error, currentAvis,
    fetchAvis, fetchAvisById, createAvis, updateAvis, deleteAvis,
    submitAvis, approveAvis, rejectAvis, publishAvis, closeAvis,
  }
})
