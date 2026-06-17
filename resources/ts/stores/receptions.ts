import { defineStore } from 'pinia'
import { useApi } from '@/composables/useApi'

export type TypeReception = 'provisoire' | 'partielle' | 'definitive'
export type StatutConformite = 'conforme' | 'non_conforme' | 'conforme_avec_reserves'
export type StatutReception = 'draft' | 'submitted' | 'approved' | 'rejected'

export interface ReceptionItem {
  id?: number
  reception_id?: number
  contrat_item_id?: number
  label?: string
  quantite_prevue?: number
  quantite_recue?: number
  conforme?: boolean
  observation?: string
}

export interface Reception {
  id: number
  uuid: string
  contrat_id: number
  numero: string
  type_reception: TypeReception
  date_reception: string
  lieu_reception?: string
  responsable_reception?: string
  constatations?: string
  reserves?: string
  statut_conformite: StatutConformite
  quantite_receptionnee?: number
  taux_execution?: number
  statut: StatutReception
  commentaire_validation?: string
  approved_by?: number
  approved_at?: string
  created_by?: number
  contrat?: any
  created_by_user?: any
  approved_by_user?: any
  reception_items?: ReceptionItem[]
  documents?: any[]
  created_at?: string
  updated_at?: string
}

export const useReceptionsStore = defineStore('receptions', () => {
  const receptions = ref<Reception[]>([])
  const total = ref(0)
  const isLoading = ref(false)
  const currentReception = ref<Reception | null>(null)

  const fetchReceptions = async (params: Record<string, any> = {}) => {
    isLoading.value = true
    try {
      const query = new URLSearchParams(params).toString()
      const { data } = await useApi(`/receptions?${query}`).json()
      receptions.value = data.value?.data ?? []
      total.value = data.value?.total ?? 0
    }
    finally {
      isLoading.value = false
    }
  }

  const fetchReceptionsByContrat = async (contratId: number, params: Record<string, any> = {}) => {
    isLoading.value = true
    try {
      const query = new URLSearchParams(params).toString()
      const { data } = await useApi(`/contrats/${contratId}/receptions?${query}`).json()
      receptions.value = data.value?.data ?? []
      total.value = data.value?.total ?? 0
    }
    finally {
      isLoading.value = false
    }
  }

  const fetchReception = async (id: number) => {
    const { data } = await useApi(`/receptions/${id}`).json()
    currentReception.value = data.value
    return data.value
  }

  const createReception = async (contratId: number, payload: Partial<Reception> & { reception_items?: ReceptionItem[] }) => {
    const { data, error } = await useApi(`/contrats/${contratId}/receptions`).post(payload).json()
    if (error.value) throw error.value
    return data.value
  }

  const updateReception = async (id: number, payload: Partial<Reception> & { reception_items?: ReceptionItem[] }) => {
    const { data, error } = await useApi(`/receptions/${id}`).put(payload).json()
    if (error.value) throw error.value
    return data.value
  }

  const deleteReception = async (id: number) => {
    const { error } = await useApi(`/receptions/${id}`).delete().json()
    if (error.value) throw error.value
  }

  const submitReception = async (id: number) => {
    const { data, error } = await useApi(`/receptions/${id}/submit`).post({}).json()
    if (error.value) throw error.value
    return data.value
  }

  const approveReception = async (id: number) => {
    const { data, error } = await useApi(`/receptions/${id}/approve`).post({}).json()
    if (error.value) throw error.value
    return data.value
  }

  const rejectReception = async (id: number, commentaire: string) => {
    const { data, error } = await useApi(`/receptions/${id}/reject`).post({ commentaire_validation: commentaire }).json()
    if (error.value) throw error.value
    return data.value
  }

  return {
    receptions,
    total,
    isLoading,
    currentReception,
    fetchReceptions,
    fetchReceptionsByContrat,
    fetchReception,
    createReception,
    updateReception,
    deleteReception,
    submitReception,
    approveReception,
    rejectReception,
  }
})
