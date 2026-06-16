import { defineStore } from 'pinia'
import { useApi } from '@/composables/useApi'

export type TypeOS = 'demarrage' | 'suspension' | 'reprise' | 'arret' | 'modification' | 'autre'
export type ImpactDelai = 'none' | 'extend' | 'reduce'
export type StatutOS = 'draft' | 'submitted' | 'approved' | 'rejected' | 'executed' | 'archived'

export interface OrdreService {
  id: number
  uuid: string
  contrat_id: number
  numero: string
  type_os: TypeOS
  objet: string
  description?: string
  date_emission: string
  date_effet?: string
  impact_delai: ImpactDelai
  delai_jours?: number
  statut: StatutOS
  commentaire_validation?: string
  issued_by?: number
  approved_by?: number
  approved_at?: string
  executed_at?: string
  created_by?: number
  contrat?: any
  created_by_user?: any
  issued_by_user?: any
  approved_by_user?: any
  created_at?: string
  updated_at?: string
}

export const useOrdreServicesStore = defineStore('ordreServices', () => {
  const ordreServices = ref<OrdreService[]>([])
  const total = ref(0)
  const isLoading = ref(false)
  const currentOS = ref<OrdreService | null>(null)

  const fetchOrdreServices = async (params: Record<string, any> = {}) => {
    isLoading.value = true
    try {
      const query = new URLSearchParams(params).toString()
      const { data } = await useApi(`/ordre-services?${query}`).json()
      ordreServices.value = data.value?.data ?? []
      total.value = data.value?.total ?? 0
    }
    finally {
      isLoading.value = false
    }
  }

  const fetchOrdreServicesByContrat = async (contratId: number, params: Record<string, any> = {}) => {
    isLoading.value = true
    try {
      const query = new URLSearchParams(params).toString()
      const { data } = await useApi(`/contrats/${contratId}/ordre-services?${query}`).json()
      ordreServices.value = data.value?.data ?? []
      total.value = data.value?.total ?? 0
    }
    finally {
      isLoading.value = false
    }
  }

  const fetchOrdreService = async (id: number) => {
    const { data } = await useApi(`/ordre-services/${id}`).json()
    currentOS.value = data.value
    return data.value
  }

  const createOrdreService = async (contratId: number, payload: Partial<OrdreService>) => {
    const { data, error } = await useApi(`/contrats/${contratId}/ordre-services`).post(payload).json()
    if (error.value) throw error.value
    return data.value
  }

  const updateOrdreService = async (id: number, payload: Partial<OrdreService>) => {
    const { data, error } = await useApi(`/ordre-services/${id}`).put(payload).json()
    if (error.value) throw error.value
    return data.value
  }

  const deleteOrdreService = async (id: number) => {
    const { error } = await useApi(`/ordre-services/${id}`).delete().json()
    if (error.value) throw error.value
  }

  const submitOrdreService = async (id: number) => {
    const { data, error } = await useApi(`/ordre-services/${id}/submit`).post({}).json()
    if (error.value) throw error.value
    return data.value
  }

  const approveOrdreService = async (id: number) => {
    const { data, error } = await useApi(`/ordre-services/${id}/approve`).post({}).json()
    if (error.value) throw error.value
    return data.value
  }

  const rejectOrdreService = async (id: number, commentaire: string) => {
    const { data, error } = await useApi(`/ordre-services/${id}/reject`).post({ commentaire_validation: commentaire }).json()
    if (error.value) throw error.value
    return data.value
  }

  const executeOrdreService = async (id: number) => {
    const { data, error } = await useApi(`/ordre-services/${id}/execute`).post({}).json()
    if (error.value) throw error.value
    return data.value
  }

  return {
    ordreServices,
    total,
    isLoading,
    currentOS,
    fetchOrdreServices,
    fetchOrdreServicesByContrat,
    fetchOrdreService,
    createOrdreService,
    updateOrdreService,
    deleteOrdreService,
    submitOrdreService,
    approveOrdreService,
    rejectOrdreService,
    executeOrdreService,
  }
})
