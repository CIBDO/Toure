export interface Paiement {
  id: number
  uuid: string
  engagement_id: number
  reference: string
  date_paiement: string
  montant: number
  mode_paiement: 'virement' | 'cheque' | 'espece' | 'autre'
  banque_id?: number
  observation?: string
  statut: 'draft' | 'submitted' | 'approved' | 'rejected'
  commentaire_validation?: string
  created_by?: number
  approved_by?: number
  approved_at?: string
  engagement?: { id: number; numero: string; contrat_id: number; montant_engage: number; contrat?: any }
  banque?: { id: number; libelle: string }
  created_at?: string
}

export interface PaiementFilters {
  q?: string
  engagement_id?: number | string
  contrat_id?: number | string
  statut?: string
  mode_paiement?: string
  exercice?: string
  date_from?: string
  date_to?: string
  itemsPerPage?: number
  sortBy?: string
  sortDesc?: boolean
}

export const paiementService = {
  async list(filters: PaiementFilters = {}): Promise<{ data: Paiement[]; total: number }> {
    const params = new URLSearchParams()
    Object.entries(filters).forEach(([k, v]) => {
      if (v !== undefined && v !== null && v !== '') params.append(k, String(v))
    })
    return $api(`/paiements?${params.toString()}`, { method: 'GET' }) as Promise<{ data: Paiement[]; total: number }>
  },

  async listByEngagement(engagementId: number): Promise<{ data: Paiement[]; total: number }> {
    return $api(`/engagements/${engagementId}/paiements`, { method: 'GET' }) as Promise<{ data: Paiement[]; total: number }>
  },

  async get(id: number): Promise<Paiement> {
    return $api(`/paiements/${id}`, { method: 'GET' }) as Promise<Paiement>
  },

  async create(payload: Partial<Paiement>): Promise<Paiement> {
    return $api('/paiements', { method: 'POST', body: payload }) as Promise<Paiement>
  },

  async update(id: number, payload: Partial<Paiement>): Promise<Paiement> {
    return $api(`/paiements/${id}`, { method: 'PUT', body: payload }) as Promise<Paiement>
  },

  async remove(id: number): Promise<void> {
    return $api(`/paiements/${id}`, { method: 'DELETE' }) as Promise<void>
  },

  async submit(id: number): Promise<Paiement> {
    return $api(`/paiements/${id}/submit`, { method: 'POST' }) as Promise<Paiement>
  },

  async approve(id: number): Promise<Paiement> {
    return $api(`/paiements/${id}/approve`, { method: 'POST' }) as Promise<Paiement>
  },

  async reject(id: number, commentaire?: string): Promise<Paiement> {
    return $api(`/paiements/${id}/reject`, {
      method: 'POST',
      body: { commentaire_validation: commentaire },
    }) as Promise<Paiement>
  },
}
