/**
 * Service GED - Gestion Électronique des Documents
 * API: /api/documents
 */

export const GED_ENTITY_OPTIONS = [
  { value: 'avis', title: 'Avis' },
  { value: 'pv', title: 'PV' },
  { value: 'contrats', title: 'Contrat' },
  { value: 'ordre_services', title: 'Ordre de service' },
  { value: 'engagements', title: 'Engagement' },
  { value: 'payments', title: 'Paiement' },
  { value: 'receptions', title: 'Réception' },
] as const

export const GED_CATEGORIES: Record<string, string> = {
  contrat_signe: 'Contrat signé',
  pv_signe: 'PV signé',
  pv_reception: 'PV de réception signé',
  bordereau: 'Bordereau',
  dao: 'DAO',
  facture: 'Facture',
  mandat: 'Mandat',
  preuve_paiement: 'Preuve de paiement',
  os_signe: 'OS signé',
  piece_justificative: 'Pièce justificative',
  autres: 'Autres',
}

export interface DocumentRecord {
  id: number
  uuid: string
  documentable_type: string
  documentable_id: number
  category: string
  title: string
  description?: string
  date_document?: string
  tags?: string[]
  file_path: string
  original_name: string
  mime_type: string
  size: number
  is_private?: boolean
  created_by?: number
  updated_by?: number
  created_at?: string
  updated_at?: string
  documentable?: { id: number; reference?: string; objet?: string; numero?: string }
  created_by_user?: { id: number; nom?: string; prenom?: string; name?: string }
}

export interface DocumentFilters {
  documentable_type?: string
  documentable_id?: number
  category?: string
  q?: string
  from?: string
  to?: string
  page?: number
  per_page?: number
}

export interface DocumentListResponse {
  data: DocumentRecord[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

const getBaseUrl = () => (import.meta.env.VITE_API_BASE_URL && String(import.meta.env.VITE_API_BASE_URL)) || '/api'

function getAuthHeaders(): Record<string, string> {
  const token = useCookie('accessToken').value
  const headers: Record<string, string> = { Accept: 'application/json' }
  if (token) headers.Authorization = `Bearer ${token}`
  return headers
}

export const documentService = {
  async list(filters: DocumentFilters = {}): Promise<DocumentListResponse> {
    const params = new URLSearchParams()
    Object.entries(filters).forEach(([k, v]) => {
      if (v !== undefined && v !== null && v !== '') params.append(k, String(v))
    })
    return $api(`/documents?${params.toString()}`, { method: 'GET' }) as Promise<DocumentListResponse>
  },

  async get(id: number): Promise<DocumentRecord> {
    return $api(`/documents/${id}`, { method: 'GET' }) as Promise<DocumentRecord>
  },

  async upload(form: FormData): Promise<DocumentRecord> {
    const token = useCookie('accessToken').value
    const baseUrl = getBaseUrl()
    const response = await fetch(`${baseUrl}/documents`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
      body: form,
    })
    if (!response.ok) {
      const err = await response.json().catch(() => ({ message: response.statusText }))
      throw new Error(err?.message || 'Erreur upload')
    }
    return response.json()
  },

  async update(id: number, payload: Partial<Pick<DocumentRecord, 'title' | 'category' | 'description' | 'date_document' | 'tags'>>): Promise<DocumentRecord> {
    return $api(`/documents/${id}`, { method: 'PUT', body: payload }) as Promise<DocumentRecord>
  },

  async remove(id: number): Promise<void> {
    await $api(`/documents/${id}`, { method: 'DELETE' })
  },

  /**
   * Télécharge le fichier et retourne le Blob (pour sauvegarde côté client)
   */
  async getDownloadBlob(id: number): Promise<Blob> {
    const baseUrl = getBaseUrl()
    const response = await fetch(`${baseUrl}/documents/${id}/download`, { headers: getAuthHeaders() })
    if (!response.ok) throw new Error('Téléchargement impossible')
    return response.blob()
  },

  /**
   * Retourne une URL blob pour prévisualisation (PDF/image).
   * Penser à révoquer l'URL après usage : URL.revokeObjectURL(url)
   */
  async getPreviewBlobUrl(id: number): Promise<string> {
    const baseUrl = getBaseUrl()
    const response = await fetch(`${baseUrl}/documents/${id}/preview`, { headers: getAuthHeaders() })
    if (!response.ok) throw new Error('Aperçu non disponible')
    const blob = await response.blob()
    return URL.createObjectURL(blob)
  },
}
