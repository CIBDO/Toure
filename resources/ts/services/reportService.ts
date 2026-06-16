/**
 * Service Rapports - CANAM Contract Manager
 * API: /api/reports/*
 */

export interface ReportContractsFilters {
  date_from?: string
  date_to?: string
  exercice?: number | string
  fournisseur_id?: number
  statut?: string
  mode_passation?: string
  compte_budget_id?: number
  page?: number
  per_page?: number
  sort_by?: string
  sort_order?: 'asc' | 'desc'
}

export interface ReportContractsIndicators {
  nombre_total_contrats: number
  montant_total_contrats: number
  total_engage: number
  total_paye: number
  reste_a_payer: number
}

export interface RepartitionStatut {
  statut: string
  count: number
  montant: number
}

export interface ReportContractsResponse {
  indicators: ReportContractsIndicators
  repartition_par_statut: RepartitionStatut[]
  data: any[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface ReportFinancialFilters {
  exercice?: number | string
  date_from?: string
  date_to?: string
  fournisseur_id?: number
  statut?: string
  page?: number
  per_page?: number
}

export interface ReportFinancialIndicators {
  total_engage: number
  total_paye: number
  reste_a_payer: number
  contrats_payes_total: number
  contrats_partiellement_payes: number
  contrats_non_payes: number
}

export interface ReportFinancialResponse {
  indicators: ReportFinancialIndicators
  data: any[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface ReportEngagementsFilters {
  date_from?: string
  date_to?: string
  exercice?: string
  compte_budget_id?: number
  statut?: string
  page?: number
  per_page?: number
}

export interface ReportEngagementRow {
  id: number
  numero: string
  contrat: { id: number; reference: string; objet?: string } | null
  montant_engage: number
  montant_paye: number
  reste_engagement: number
  statut: string
  date_engagement: string
  exercice: string
}

export interface ReportEngagementsResponse {
  data: ReportEngagementRow[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface ReportPaymentsFilters {
  date_from?: string
  date_to?: string
  mode_paiement?: string
  fournisseur_id?: number
  exercice?: string
  page?: number
  per_page?: number
}

export interface ReportPaymentRow {
  id: number
  reference: string
  contrat: { id: number; reference: string } | null
  engagement: { id: number; numero: string } | null
  montant: number
  date_paiement: string
  mode_paiement: string
  statut: string
}

export interface ReportPaymentsResponse {
  data: ReportPaymentRow[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface ReportSuppliersFilters {
  exercice?: number | string
  date_from?: string
  date_to?: string
}

export interface SupplierPerformanceRow {
  fournisseur_id: number
  fournisseur: { id: number; raison_sociale: string } | null
  total_contrats: number
  montant_attribue: number
  montant_paye: number
  delai_moyen_paiement_jours: number | null
}

export interface ReportSuppliersResponse {
  indicators: { total_fournisseurs: number }
  data: SupplierPerformanceRow[]
  top_10: SupplierPerformanceRow[]
}

function buildParams(filters: Record<string, unknown>): string {
  const params = new URLSearchParams()
  Object.entries(filters).forEach(([k, v]) => {
    if (v !== undefined && v !== null && v !== '') params.append(k, String(v))
  })
  return params.toString()
}

const base = '/reports'

export const reportService = {
  async contracts(filters: ReportContractsFilters = {}): Promise<ReportContractsResponse> {
    const q = buildParams(filters)
    return $api(`${base}/contracts?${q}`, { method: 'GET' }) as Promise<ReportContractsResponse>
  },

  async financial(filters: ReportFinancialFilters = {}): Promise<ReportFinancialResponse> {
    const q = buildParams(filters)
    return $api(`${base}/financial?${q}`, { method: 'GET' }) as Promise<ReportFinancialResponse>
  },

  async engagements(filters: ReportEngagementsFilters = {}): Promise<ReportEngagementsResponse> {
    const q = buildParams(filters)
    return $api(`${base}/engagements?${q}`, { method: 'GET' }) as Promise<ReportEngagementsResponse>
  },

  async payments(filters: ReportPaymentsFilters = {}): Promise<ReportPaymentsResponse> {
    const q = buildParams(filters)
    return $api(`${base}/payments?${q}`, { method: 'GET' }) as Promise<ReportPaymentsResponse>
  },

  async suppliers(filters: ReportSuppliersFilters = {}): Promise<ReportSuppliersResponse> {
    const q = buildParams(filters)
    return $api(`${base}/suppliers?${q}`, { method: 'GET' }) as Promise<ReportSuppliersResponse>
  },

  /**
   * Retourne l'URL d'export (Excel ou PDF) — à utiliser avec fetch + Authorization header.
   * Préférer downloadExport() pour télécharger avec le token.
   */
  getExportUrl(report: 'contracts' | 'financial', format: 'excel' | 'pdf', filters: Record<string, unknown> = {}): string {
    const baseUrl = (import.meta.env.VITE_API_BASE_URL && String(import.meta.env.VITE_API_BASE_URL)) || '/api'
    const params = new URLSearchParams({ format })
    Object.entries(filters).forEach(([k, v]) => {
      if (v !== undefined && v !== null && v !== '') params.append(k, String(v))
    })
    return `${baseUrl}/${base}/${report}/export?${params.toString()}`
  },

  /**
   * Déclenche le téléchargement d'un export (fetch avec Bearer token).
   * Utilise la même base URL que l'API (avec proxy Vite en dev si besoin).
   */
  async downloadExport(report: 'contracts' | 'financial', format: 'excel' | 'pdf', filters: Record<string, unknown> = {}): Promise<void> {
    const baseUrl = (import.meta.env.VITE_API_BASE_URL && String(import.meta.env.VITE_API_BASE_URL)) || '/api'
    const params = new URLSearchParams({ format })
    Object.entries(filters).forEach(([k, v]) => {
      if (v !== undefined && v !== null && v !== '') params.append(k, String(v))
    })
    const url = `${baseUrl.replace(/\/$/, '')}${base}/${report}/export?${params.toString()}`
    const token = useCookie('accessToken').value
    const headers: Record<string, string> = {
      Accept: format === 'pdf' ? 'application/pdf' : 'text/csv, application/octet-stream',
    }
    if (token) headers.Authorization = `Bearer ${token}`
    const response = await fetch(url, { headers, redirect: 'manual' })
    if (response.type === 'opaqueredirect' || response.status === 302 || response.status === 301) {
      throw new Error('Session expirée ou non autorisée. Reconnectez-vous puis réessayez.')
    }
    if (!response.ok) {
      const text = await response.text()
      let msg = 'Export impossible'
      if (response.status === 403) msg = 'Droits d\'export insuffisants'
      else if (response.status === 401) msg = 'Session expirée. Reconnectez-vous.'
      else if (text && text.trim().startsWith('<')) msg = 'Le serveur a renvoyé du HTML au lieu du fichier. Vérifiez que l\'API Laravel est bien atteinte (proxy ou VITE_API_BASE_URL).'
      throw new Error(msg)
    }
    const contentType = response.headers.get('Content-Type') || ''
    if (format === 'pdf' && !contentType.includes('pdf') && contentType.includes('text/html')) {
      throw new Error('Le serveur a renvoyé du HTML au lieu du PDF. Vérifiez que Laravel tourne (php artisan serve) et que le proxy /api pointe vers Laravel.')
    }
    if (format === 'excel' && contentType.includes('text/html')) {
      throw new Error('Le serveur a renvoyé du HTML au lieu du CSV. Vérifiez que Laravel tourne (php artisan serve) et que le proxy /api pointe vers Laravel.')
    }
    const blob = await response.blob()
    const ext = format === 'pdf' ? 'pdf' : 'csv'
    const name = `rapport-${report}-${new Date().toISOString().slice(0, 10)}.${ext}`
    const a = document.createElement('a')
    a.href = URL.createObjectURL(blob)
    a.download = name
    a.click()
    URL.revokeObjectURL(a.href)
  },
}
