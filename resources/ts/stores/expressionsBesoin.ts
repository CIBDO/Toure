import { defineStore } from 'pinia'
import { useApi } from '@/composables/useApi'

export interface ExpressionBesoin {
  id: number
  uuid: string
  code: string
  libelle: string
  description?: string
  unite_defaut?: string
  domaine_activite_id?: number
  domaine_activite?: { id: number; code: string; libelle: string }
  actif: boolean
}

export const useExpressionsBesoinStore = defineStore('expressionsBesoin', () => {
  const expressions = ref<ExpressionBesoin[]>([])
  const total = ref(0)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const fetchExpressions = async (params: Record<string, any> = {}) => {
    isLoading.value = true
    error.value = null
    try {
      const query = new URLSearchParams(params).toString()
      const { data } = await useApi(`/expressions-besoin?${query}`).json()
      expressions.value = data.value?.data ?? []
      total.value = data.value?.total ?? 0
    }
    catch (e: any) {
      error.value = e.message
    }
    finally {
      isLoading.value = false
    }
  }

  const createExpression = async (payload: Partial<ExpressionBesoin>) => {
    const { data, error: err } = await useApi('/expressions-besoin').post(payload).json()
    if (err.value) throw err.value
    return data.value
  }

  const updateExpression = async (id: number, payload: Partial<ExpressionBesoin>) => {
    const { data, error: err } = await useApi(`/expressions-besoin/${id}`).put(payload).json()
    if (err.value) throw err.value
    return data.value
  }

  const deleteExpression = async (id: number) => {
    const { error: err } = await useApi(`/expressions-besoin/${id}`).delete().json()
    if (err.value) throw err.value
  }

  return {
    expressions, total, isLoading, error,
    fetchExpressions, createExpression, updateExpression, deleteExpression,
  }
})
