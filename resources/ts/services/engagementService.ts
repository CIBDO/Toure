export interface Engagement {
  id: number
  uuid: string
  contrat_id: number
  numero: string
  date_engagement: string
  exercice: string
  compte_budget_id?: number
  montant_engage: number
  statut: 'draft' | 'submitted' | 'approved' | 'rejected' | 'archived'
  commentaire_validation?: string
  created_by?: number
  approved_by?: number
  approved_at?: string
  contrat?: { id: number; reference: string; objet: string; montant_initial?: number }
  compte_budget?: { id: number; libelle: string }
  paiements?: any[]
  created_at?: string
}

export interface EngagementFilters {
  q?: string
  contrat_id?: number | string
  statut?: string
  exercice?: string
  date_from?: string
  date_to?: string
  itemsPerPage?: number
  sortBy?: string
  sortDesc?: boolean
}

export interface FinanceSummary {
  total_engaged: number
  total_paid: number
  remaining: number
  paid_status: 'non_paye' | 'partiel' | 'paye'
  montant_contrat: number
}

export const engagementService = {
  async list(filters: EngagementFilters = {}): Promise<{ data: Engagement[]; total: number }> {
    const params = new URLSearchParams()
    Object.entries(filters).forEach(([k, v]) => {
      if (v !== undefined && v !== null && v !== '') params.append(k, String(v))
    })
    return $api(`/engagements?${params.toString()}`, { method: 'GET' }) as Promise<{ data: Engagement[]; total: number }>
  },

  async listByContrat(contratId: number): Promise<{ data: Engagement[]; total: number }> {
    return $api(`/contrats/${contratId}/engagements`, { method: 'GET' }) as Promise<{ data: Engagement[]; total: number }>
  },

  async get(id: number): Promise<Engagement> {
    return $api(`/engagements/${id}`, { method: 'GET' }) as Promise<Engagement>
  },

  async create(payload: Partial<Engagement>): Promise<Engagement> {
    return $api('/engagements', { method: 'POST', body: payload }) as Promise<Engagement>
  },

  async update(id: number, payload: Partial<Engagement>): Promise<Engagement> {
    return $api(`/engagements/${id}`, { method: 'PUT', body: payload }) as Promise<Engagement>
  },

  async remove(id: number): Promise<void> {
    return $api(`/engagements/${id}`, { method: 'DELETE' }) as Promise<void>
  },

  async submit(id: number): Promise<Engagement> {
    return $api(`/engagements/${id}/submit`, { method: 'POST' }) as Promise<Engagement>
  },

  async approve(id: number): Promise<Engagement> {
    return $api(`/engagements/${id}/approve`, { method: 'POST' }) as Promise<Engagement>
  },

  async reject(id: number, commentaire?: string): Promise<Engagement> {
    return $api(`/engagements/${id}/reject`, {
      method: 'POST',
      body: { commentaire_validation: commentaire },
    }) as Promise<Engagement>
  },

  async financeSummary(contratId: number): Promise<FinanceSummary> {
    return $api(`/contrats/${contratId}/finance-summary`, { method: 'GET' }) as Promise<FinanceSummary>
  },
}
