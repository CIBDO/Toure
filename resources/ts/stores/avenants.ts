import { defineStore } from 'pinia'
import { useApi } from '@/composables/useApi'

export interface Avenant {
  id: number
  uuid: string
  contrat_id: number
  numero: string
  type_avenant: 'montant' | 'delai' | 'objet' | 'mixte'
  montant_variation?: number
  ancien_montant: number
  nouveau_montant: number
  ancienne_date_fin?: string
  nouvelle_date_fin?: string
  prolongation_jours?: number
  ancienne_description_objet?: string
  nouvelle_description_objet?: string
  justification: string
  date_signature: string
  statut: 'draft' | 'submitted' | 'approved' | 'rejected'
  commentaire_validation?: string
  created_by?: number
  approved_by?: number
  approved_at?: string
  contrat?: any
  created_by_user?: any
  approved_by_user?: any
  created_at?: string
  updated_at?: string
}

export const useAvenantsStore = defineStore('avenants', () => {
  const avenants = ref<Avenant[]>([])
  const total = ref(0)
  const isLoading = ref(false)
  const currentAvenant = ref<Avenant | null>(null)

  const fetchAvenants = async (params: Record<string, any> = {}) => {
    isLoading.value = true
    try {
      const query = new URLSearchParams(params).toString()
      const { data } = await useApi(`/avenants?${query}`).json()
      avenants.value = data.value?.data ?? []
      total.value = data.value?.total ?? 0
    }
    finally {
      isLoading.value = false
    }
  }

  const fetchAvenantsByContrat = async (contratId: number, params: Record<string, any> = {}) => {
    isLoading.value = true
    try {
      const query = new URLSearchParams(params).toString()
      const { data } = await useApi(`/contrats/${contratId}/avenants?${query}`).json()
      avenants.value = data.value?.data ?? []
      total.value = data.value?.total ?? 0
    }
    finally {
      isLoading.value = false
    }
  }

  const fetchAvenant = async (id: number) => {
    const { data } = await useApi(`/avenants/${id}`).json()
    currentAvenant.value = data.value
    return data.value
  }

  const createAvenant = async (contratId: number, payload: Partial<Avenant>) => {
    const { data, error } = await useApi(`/contrats/${contratId}/avenants`).post(payload).json()
    if (error.value) throw error.value
    return data.value
  }

  const updateAvenant = async (id: number, payload: Partial<Avenant>) => {
    const { data, error } = await useApi(`/avenants/${id}`).put(payload).json()
    if (error.value) throw error.value
    return data.value
  }

  const deleteAvenant = async (id: number) => {
    const { error } = await useApi(`/avenants/${id}`).delete().json()
    if (error.value) throw error.value
  }

  const submitAvenant = async (id: number) => {
    const { data, error } = await useApi(`/avenants/${id}/submit`).post({}).json()
    if (error.value) throw error.value
    return data.value
  }

  const approveAvenant = async (id: number) => {
    const { data, error } = await useApi(`/avenants/${id}/approve`).post({}).json()
    if (error.value) throw error.value
    return data.value
  }

  const rejectAvenant = async (id: number, commentaire: string) => {
    const { data, error } = await useApi(`/avenants/${id}/reject`).post({ commentaire_validation: commentaire }).json()
    if (error.value) throw error.value
    return data.value
  }

  return {
    avenants,
    total,
    isLoading,
    currentAvenant,
    fetchAvenants,
    fetchAvenantsByContrat,
    fetchAvenant,
    createAvenant,
    updateAvenant,
    deleteAvenant,
    submitAvenant,
    approveAvenant,
    rejectAvenant,
  }
})
