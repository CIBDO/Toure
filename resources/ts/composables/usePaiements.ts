import { paiementService } from '@/services/paiementService'
import type { Paiement, PaiementFilters } from '@/services/paiementService'

export function usePaiements() {
  const items = ref<Paiement[]>([])
  const total = ref(0)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const fetch = async (filters: PaiementFilters = {}) => {
    isLoading.value = true
    error.value = null
    try {
      const res = await paiementService.list(filters)
      items.value = res.data ?? []
      total.value = res.total ?? 0
    }
    catch (err: any) {
      error.value = err?.response?._data?.message || err?.message || 'Erreur de chargement'
    }
    finally {
      isLoading.value = false
    }
  }

  const create = async (payload: Partial<Paiement>) => {
    return paiementService.create(payload)
  }

  const update = async (id: number, payload: Partial<Paiement>) => {
    return paiementService.update(id, payload)
  }

  const remove = async (id: number) => {
    return paiementService.remove(id)
  }

  const submit = async (id: number) => {
    return paiementService.submit(id)
  }

  const approve = async (id: number) => {
    return paiementService.approve(id)
  }

  const reject = async (id: number, commentaire?: string) => {
    return paiementService.reject(id, commentaire)
  }

  return {
    items,
    total,
    isLoading,
    error,
    fetch,
    create,
    update,
    remove,
    submit,
    approve,
    reject,
  }
}
