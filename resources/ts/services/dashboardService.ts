/**
 * Service Dashboard - CANAM Contract Manager
 * Consomme GET /api/dashboard/summary
 */

export interface DashboardKpis {
  contracts_approved: number
  contracts_pending: number
  avis_total: number
  depouillements_total: number
  pv_total: number
  suppliers_total: number
  budget_accounts_total: number
  users_total: number
  payments_total_amount: number
  remaining_to_pay: number
}

export interface ContractByStatus {
  status: string
  count: number
}

export interface MonthlyAmount {
  month: string
  engaged: number
  paid: number
}

export interface TopSupplier {
  name: string
  amount: number
}

export interface LatestContract {
  id: number
  numero: string
  fournisseur: string
  montant: number
  statut: string
  created_at: string
}

export interface ContractInDelay {
  id: number
  numero: string
  fournisseur: string
  etape: string
  days_late: number
}

export interface DashboardCharts {
  contracts_by_status: ContractByStatus[]
  monthly_amounts: MonthlyAmount[]
  top_suppliers: TopSupplier[]
}

export interface DashboardTables {
  latest_contracts: LatestContract[]
  contracts_in_delay: ContractInDelay[]
}

export interface DashboardSummary {
  kpis: DashboardKpis
  charts: DashboardCharts
  tables: DashboardTables
}

export interface DashboardFilters {
  from?: string
  to?: string
  exercice?: number | string
}

export const dashboardService = {
  async getSummary(filters: DashboardFilters = {}): Promise<DashboardSummary> {
    const params: Record<string, string> = {}
    if (filters.from) params.from = filters.from
    if (filters.to) params.to = filters.to
    if (filters.exercice) params.exercice = String(filters.exercice)

    return $api('/dashboard/summary', {
      method: 'GET',
      params,
    }) as Promise<DashboardSummary>
  },
}
