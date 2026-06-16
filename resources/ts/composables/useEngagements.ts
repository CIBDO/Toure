import { engagementService } from '@/services/engagementService'
import type { Engagement, EngagementFilters } from '@/services/engagementService'

export function useEngagements() {
  const items = ref<Engagement[]>([])
  const total = ref(0)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const fetch = async (filters: EngagementFilters = {}) => {
    isLoading.value = true
    error.value = null
    try {
      const res = await engagementService.list(filters)
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

  const create = async (payload: Partial<Engagement>) => {
    return engagementService.create(payload)
  }

  const update = async (id: number, payload: Partial<Engagement>) => {
    return engagementService.update(id, payload)
  }

  const remove = async (id: number) => {
    return engagementService.remove(id)
  }

  const submit = async (id: number) => {
    return engagementService.submit(id)
  }

  const approve = async (id: number) => {
    return engagementService.approve(id)
  }

  const reject = async (id: number, commentaire?: string) => {
    return engagementService.reject(id, commentaire)
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
