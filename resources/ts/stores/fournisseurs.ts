import { defineStore } from 'pinia'
import { useApi } from '@/composables/useApi'

export interface FournisseurBanque {
  id?: number
  banque_id: number
  numero_compte: string
  rib?: string
  swift?: string
  iban?: string
  intitule_compte?: string
  principal: boolean
  banque?: { id: number; code: string; libelle: string; sigle?: string }
}

export interface Fournisseur {
  id: number
  uuid: string
  code: string
  civilite?: string
  qualite_fonction?: string
  raison_sociale: string
  sigle?: string
  nif?: string
  rc?: string
  telephone?: string
  fax?: string
  email?: string
  adresse?: string
  ville?: string
  region?: string
  pays?: string
  representant?: string
  fonction_representant?: string
  domaine_activite_id?: number
  domaine_activite?: { id: number; code: string; libelle: string }
  modes_passation?: string[]
  duree_min?: number | null
  duree_max?: number | null
  statut: 'actif' | 'suspendu' | 'blackliste'
  observations?: string
  banques?: FournisseurBanque[]
}

export const useFournisseursStore = defineStore('fournisseurs', () => {
  const fournisseurs = ref<Fournisseur[]>([])
  const total = ref(0)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const currentFournisseur = ref<Fournisseur | null>(null)

  const fetchFournisseurs = async (params: Record<string, any> = {}) => {
    isLoading.value = true
    error.value = null
    try {
      const query = new URLSearchParams(params).toString()
      const { data } = await useApi(`/fournisseurs?${query}`).json()
      fournisseurs.value = data.value?.data ?? []
      total.value = data.value?.total ?? 0
    }
    catch (e: any) {
      error.value = e.message
    }
    finally {
      isLoading.value = false
    }
  }

  const fetchFournisseur = async (id: number) => {
    const { data } = await useApi(`/fournisseurs/${id}`).json()
    currentFournisseur.value = data.value
    return data.value
  }

  const createFournisseur = async (payload: Partial<Fournisseur>) => {
    const { data, error: err } = await useApi('/fournisseurs').post(payload).json()
    if (err.value) throw err.value
    return data.value
  }

  const updateFournisseur = async (id: number, payload: Partial<Fournisseur>) => {
    const { data, error: err } = await useApi(`/fournisseurs/${id}`).put(payload).json()
    if (err.value) throw err.value
    return data.value
  }

  const deleteFournisseur = async (id: number) => {
    const { error: err } = await useApi(`/fournisseurs/${id}`).delete().json()
    if (err.value) throw err.value
  }

  return {
    fournisseurs, total, isLoading, error, currentFournisseur,
    fetchFournisseurs, fetchFournisseur, createFournisseur, updateFournisseur, deleteFournisseur,
  }
})
