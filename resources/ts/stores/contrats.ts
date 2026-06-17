import { defineStore } from 'pinia'
import { useApi } from '@/composables/useApi'

export interface ContratEtape {
  id: number
  contrat_id: number
  type_etape: 'elaboration' | 'engagement' | 'oem' | 'mandat' | 'paie'
  date_prevue?: string
  date_limite?: string
  date_effective?: string
  statut: 'pending' | 'in_progress' | 'completed' | 'blocked'
  commentaire?: string
  piece_jointe?: string
}

export interface Contrat {
  id: number
  uuid: string
  reference: string
  objet: string
  pv_id?: number
  avis_id?: number
  fournisseur_id: number
  compte_budget_id?: number
  agent_id?: number
  montant_initial: number
  montant_actuel?: number
  devise: string
  date_signature?: string
  date_debut?: string
  date_fin?: string
  date_previsionnelle_reception?: string
  duree_execution?: number
  mode_passation?: string
  exercice?: string
  statut: 'draft' | 'submitted' | 'approved' | 'rejected' | 'archived'
  observations?: string
  fournisseur?: any
  compte_budget?: any
  agent?: any
  etapes?: ContratEtape[]
  created_at?: string
}

export const useContratsStore = defineStore('contrats', () => {
  const contrats = ref<Contrat[]>([])
  const total = ref(0)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const currentContrat = ref<Contrat | null>(null)

  const fetchContrats = async (params: Record<string, any> = {}) => {
    isLoading.value = true
    error.value = null
    try {
      const query = new URLSearchParams(params).toString()
      const { data } = await useApi(`/contrats?${query}`).json()
      contrats.value = data.value?.data ?? []
      total.value = data.value?.total ?? 0
    }
    catch (e: any) {
      error.value = e.message
    }
    finally {
      isLoading.value = false
    }
  }

  const fetchContrat = async (id: number) => {
    const { data } = await useApi(`/contrats/${id}`).json()
    currentContrat.value = data.value
    return data.value
  }

  const createContrat = async (payload: Partial<Contrat>) => {
    const { data, error: err } = await useApi('/contrats').post(payload).json()
    if (err.value) throw err.value
    return data.value
  }

  const updateContrat = async (id: number, payload: Partial<Contrat>) => {
    const { data, error: err } = await useApi(`/contrats/${id}`).put(payload).json()
    if (err.value) throw err.value
    return data.value
  }

  const deleteContrat = async (id: number) => {
    const { error: err } = await useApi(`/contrats/${id}`).delete().json()
    if (err.value) throw err.value
  }

  const submitContrat = async (id: number) => {
    const { data, error: err } = await useApi(`/contrats/${id}/submit`).post({}).json()
    if (err.value) throw err.value
    return data.value
  }

  const approveContrat = async (id: number) => {
    const { data, error: err } = await useApi(`/contrats/${id}/approve`).post({}).json()
    if (err.value) throw err.value
    return data.value
  }

  const rejectContrat = async (id: number, body: any = {}) => {
    const { data, error: err } = await useApi(`/contrats/${id}/reject`).post(body).json()
    if (err.value) throw err.value
    return data.value
  }

  const archiveContrat = async (id: number) => {
    const { data, error: err } = await useApi(`/contrats/${id}/archive`).post({}).json()
    if (err.value) throw err.value
    return data.value
  }

  const updateEtape = async (contratId: number, etapeId: number, payload: Partial<ContratEtape> | FormData) => {
    // Use POST for multipart (file upload), PUT for JSON
    if (payload instanceof FormData) {
      const { data, error: err } = await useApi(`/contrats/${contratId}/etapes/${etapeId}`).post(payload).json()
      if (err.value) throw err.value
      return data.value
    }
    const { data, error: err } = await useApi(`/contrats/${contratId}/etapes/${etapeId}`).put(payload).json()
    if (err.value) throw err.value
    return data.value
  }

  return {
    contrats, total, isLoading, error, currentContrat,
    fetchContrats, fetchContrat, createContrat, updateContrat, deleteContrat,
    submitContrat, approveContrat, rejectContrat, archiveContrat, updateEtape,
  }
})
