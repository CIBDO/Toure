/**
 * Composable useReports - CANAM Contract Manager
 * Gère le chargement des rapports, filtres, loading et erreurs.
 */

import { reportService } from '@/services/reportService'
import type {
  ReportContractsFilters,
  ReportContractsResponse,
  ReportFinancialFilters,
  ReportFinancialResponse,
  ReportEngagementsFilters,
  ReportEngagementsResponse,
  ReportPaymentsFilters,
  ReportPaymentsResponse,
  ReportSuppliersFilters,
  ReportSuppliersResponse,
} from '@/services/reportService'

const currentYear = new Date().getFullYear()

function defaultDateRange() {
  return {
    date_from: `${currentYear}-01-01`,
    date_to: `${currentYear}-12-31`,
    exercice: currentYear,
  }
}

export function useReportContracts() {
  const data = ref<ReportContractsResponse | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const filters = ref<ReportContractsFilters>({ ...defaultDateRange(), per_page: 15, page: 1 })

  const fetch = async () => {
    isLoading.value = true
    error.value = null
    try {
      data.value = await reportService.contracts(filters.value)
    } catch (err: any) {
      const msg = err?.data?.message || err?.message || 'Erreur lors du chargement du rapport contrats'
      error.value = msg
    } finally {
      isLoading.value = false
    }
  }

  const applyFilters = (f: Partial<ReportContractsFilters>) => {
    filters.value = { ...filters.value, ...f }
    fetch()
  }

  const resetFilters = () => {
    filters.value = { ...defaultDateRange(), per_page: 15, page: 1 }
    fetch()
  }

  return { data, isLoading, error, filters, fetch, applyFilters, resetFilters }
}

export function useReportFinancial() {
  const data = ref<ReportFinancialResponse | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const filters = ref<ReportFinancialFilters>({ ...defaultDateRange(), per_page: 15, page: 1 })

  const fetch = async () => {
    isLoading.value = true
    error.value = null
    try {
      data.value = await reportService.financial(filters.value)
    } catch (err: any) {
      const msg = err?.data?.message || err?.message || 'Erreur lors du chargement du rapport financier'
      error.value = msg
    } finally {
      isLoading.value = false
    }
  }

  const applyFilters = (f: Partial<ReportFinancialFilters>) => {
    filters.value = { ...filters.value, ...f }
    fetch()
  }

  const resetFilters = () => {
    filters.value = { ...defaultDateRange(), per_page: 15, page: 1 }
    fetch()
  }

  return { data, isLoading, error, filters, fetch, applyFilters, resetFilters }
}

export function useReportEngagements() {
  const data = ref<ReportEngagementsResponse | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const filters = ref<ReportEngagementsFilters>({ ...defaultDateRange(), per_page: 15, page: 1 })

  const fetch = async () => {
    isLoading.value = true
    error.value = null
    try {
      data.value = await reportService.engagements(filters.value)
    } catch (err: any) {
      const msg = err?.data?.message || err?.message || 'Erreur lors du chargement du rapport engagements'
      error.value = msg
    } finally {
      isLoading.value = false
    }
  }

  const applyFilters = (f: Partial<ReportEngagementsFilters>) => {
    filters.value = { ...filters.value, ...f }
    fetch()
  }

  const resetFilters = () => {
    filters.value = { ...defaultDateRange(), per_page: 15, page: 1 }
    fetch()
  }

  return { data, isLoading, error, filters, fetch, applyFilters, resetFilters }
}

export function useReportPayments() {
  const data = ref<ReportPaymentsResponse | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const filters = ref<ReportPaymentsFilters>({ ...defaultDateRange(), per_page: 15, page: 1 })

  const fetch = async () => {
    isLoading.value = true
    error.value = null
    try {
      data.value = await reportService.payments(filters.value)
    } catch (err: any) {
      const msg = err?.data?.message || err?.message || 'Erreur lors du chargement du rapport paiements'
      error.value = msg
    } finally {
      isLoading.value = false
    }
  }

  const applyFilters = (f: Partial<ReportPaymentsFilters>) => {
    filters.value = { ...filters.value, ...f }
    fetch()
  }

  const resetFilters = () => {
    filters.value = { ...defaultDateRange(), per_page: 15, page: 1 }
    fetch()
  }

  return { data, isLoading, error, filters, fetch, applyFilters, resetFilters }
}

export function useReportSuppliers() {
  const data = ref<ReportSuppliersResponse | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const filters = ref<ReportSuppliersFilters>(defaultDateRange())

  const fetch = async () => {
    isLoading.value = true
    error.value = null
    try {
      data.value = await reportService.suppliers(filters.value)
    } catch (err: any) {
      const msg = err?.data?.message || err?.message || 'Erreur lors du chargement du rapport fournisseurs'
      error.value = msg
    } finally {
      isLoading.value = false
    }
  }

  const applyFilters = (f: Partial<ReportSuppliersFilters>) => {
    filters.value = { ...filters.value, ...f }
    fetch()
  }

  const resetFilters = () => {
    filters.value = defaultDateRange()
    fetch()
  }

  return { data, isLoading, error, filters, fetch, applyFilters, resetFilters }
}
