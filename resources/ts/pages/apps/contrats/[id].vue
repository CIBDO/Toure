<script setup lang="ts">
import { useContratsStore } from '@/stores/contrats'
import { useApi } from '@/composables/useApi'
import { engagementService } from '@/services/engagementService'
import { paiementService } from '@/services/paiementService'
import DocumentsPanel from '@/components/DocumentsPanel.vue'
import { useAvenantsStore } from '@/stores/avenants'
import { useOrdreServicesStore } from '@/stores/ordreServices'
import { useReceptionsStore } from '@/stores/receptions'
import AvenantForm from '@/components/contracts/AvenantForm.vue'
import OrdreServiceForm from '@/components/contracts/OrdreServiceForm.vue'
import ReceptionForm from '@/components/contracts/ReceptionForm.vue'
import type { Engagement } from '@/services/engagementService'
import type { Paiement } from '@/services/paiementService'
import { formatCurrencyXOF } from '@/composables/useDashboard'

definePage({ meta: { title: 'Détail Contrat' } })

const route = useRoute()
const router = useRouter()
const store = useContratsStore()
const snackbar = ref({ show: false, text: '', color: 'success' })
const activeTab = ref('info')

// Étape dialog
const etapeDialog = ref(false)
const selectedEtape = ref<any>(null)
const etapeForm = ref({
  statut: '', date_prevue: '', date_limite: '', date_effective: '', commentaire: '',
})
const etapeFile = ref<File | null>(null)
const isSavingEtape = ref(false)

// Reject
const rejectDialog = ref(false)
const motifRejet = ref('')

const contratId = computed(() => Number(route.params.id))
const contrat = computed(() => store.currentContrat)
const isLoadingContrat = ref(false)
const loadError = ref<string | null>(null)

const loadContrat = async () => {
  if (!contratId.value || Number.isNaN(contratId.value)) {
    loadError.value = 'Identifiant de contrat invalide'
    return
  }
  isLoadingContrat.value = true
  loadError.value = null
  try {
    const data = await store.fetchContrat(contratId.value)
    if (!data)
      loadError.value = 'Contrat introuvable'
  }
  catch (e: any) {
    loadError.value = e?.data?.message || 'Impossible de charger le contrat'
  }
  finally {
    isLoadingContrat.value = false
  }
}

onMounted(loadContrat)
watch(contratId, loadContrat)

const etapeLabels: Record<string, string> = {
  elaboration: 'Élaboration',
  engagement: 'Engagement budgétaire',
  oem: 'OEM (Ordre d\'Engagement de Mandatement)',
  mandat: 'Mandat de paiement',
  paie: 'Paiement',
}

const etapeIcons: Record<string, string> = {
  elaboration: 'tabler-pencil',
  engagement: 'tabler-lock',
  oem: 'tabler-file-invoice',
  mandat: 'tabler-receipt',
  paie: 'tabler-cash',
}

const etapeStatutColor = (s: string) => ({
  pending: 'default', in_progress: 'info', completed: 'success', blocked: 'error',
}[s] || 'default')

const etapeStatutLabel = (s: string) => ({
  pending: 'En attente', in_progress: 'En cours', completed: 'Terminé', blocked: 'Bloqué',
}[s] || s)

const statutColor = (s: string) => ({
  draft: 'default', submitted: 'info', approved: 'success', rejected: 'error', archived: 'secondary',
}[s] || 'default')

const statutLabel = (s: string) => ({
  draft: 'Brouillon', submitted: 'Soumis', approved: 'Approuvé', rejected: 'Rejeté', archived: 'Archivé',
}[s] || s)

const formatMontant = (v: number) => v ? new Intl.NumberFormat('fr-GN').format(v) + ' CFA' : '-'
const formatDate = (d: string) => d ? new Date(d).toLocaleDateString('fr-FR') : '-'

const isEnRetard = (etape: any) => {
  if (!etape.date_limite || etape.statut === 'completed') return false
  return new Date(etape.date_limite) < new Date()
}

const joursRetard = (etape: any) => {
  if (!etape.date_limite) return 0
  return Math.floor((Date.now() - new Date(etape.date_limite).getTime()) / 86400000)
}

// Progression globale
const progressionPct = computed(() => {
  const etapes = contrat.value?.etapes ?? []
  if (!etapes.length) return 0
  const done = etapes.filter((e: any) => e.statut === 'completed').length
  return Math.round((done / etapes.length) * 100)
})

// ─── Actions contrat ──────────────────────────────────────────────────────────

// ─── Étapes ───────────────────────────────────────────────────────────────────
const openEtapeEdit = (etape: any) => {
  selectedEtape.value = etape
  etapeForm.value = {
    statut: etape.statut,
    date_prevue: etape.date_prevue ?? '',
    date_limite: etape.date_limite ?? '',
    date_effective: etape.date_effective ?? '',
    commentaire: etape.commentaire ?? '',
  }
  etapeFile.value = null
  etapeDialog.value = true
}

const saveEtape = async () => {
  isSavingEtape.value = true
  try {
    let payload: any
    if (etapeFile.value) {
      // Multipart pour upload fichier
      const fd = new FormData()
      Object.entries(etapeForm.value).forEach(([k, v]) => { if (v) fd.append(k, v) })
      fd.append('piece_jointe', etapeFile.value)
      payload = fd
    }
    else {
      payload = { ...etapeForm.value }
    }
    await store.updateEtape(contratId.value, selectedEtape.value.id, payload)
    etapeDialog.value = false
    snackbar.value = { show: true, text: 'Étape mise à jour avec succès', color: 'success' }
    await store.fetchContrat(contratId.value)
  }
  catch {
    snackbar.value = { show: true, text: 'Erreur lors de la mise à jour', color: 'error' }
  }
  finally {
    isSavingEtape.value = false
  }
}

const downloadEtapePiece = (etape: any) => {
  window.open(`${import.meta.env.VITE_API_BASE_URL}/contrats/${contratId.value}/etapes/${etape.id}/download`, '_blank')
}

// ─── Actions contrat ──────────────────────────────────────────────────────────
const doContratAction = async (action: 'submitContrat' | 'approveContrat' | 'archiveContrat') => {
  try {
    await store[action](contratId.value)
    const labels = { submitContrat: 'soumis', approveContrat: 'approuvé', archiveContrat: 'archivé' }
    snackbar.value = { show: true, text: `Contrat ${labels[action]} avec succès`, color: 'success' }
    await store.fetchContrat(contratId.value)
  }
  catch {
    snackbar.value = { show: true, text: 'Erreur lors de l\'action', color: 'error' }
  }
}

const confirmReject = async () => {
  try {
    await store.rejectContrat(contratId.value, { motif_rejet: motifRejet.value })
    rejectDialog.value = false
    snackbar.value = { show: true, text: 'Contrat rejeté', color: 'warning' }
    await store.fetchContrat(contratId.value)
  }
  catch {
    snackbar.value = { show: true, text: 'Erreur lors du rejet', color: 'error' }
  }
}

// ─── Finance ──────────────────────────────────────────────────────────────────
const financeSummary = ref<any>(null)
const financeEngagements = ref<Engagement[]>([])
const isLoadingFinance = ref(false)
const expandedEngagement = ref<number | null>(null)
const engagementPaiements = ref<Record<number, Paiement[]>>({})

// Engagement form
const engagementDialog = ref(false)
const isEditingEngagement = ref(false)
const isSavingEngagement = ref(false)
const selectedEngagement = ref<Engagement | null>(null)
const engagementRejectDialog = ref(false)
const engagementCommentaire = ref('')
const engagementForm = ref({
  numero: '',
  date_engagement: '',
  exercice: new Date().getFullYear().toString(),
  compte_budget_id: null as number | null,
  montant_engage: null as number | null,
  commentaire_validation: '',
})

// Paiement form
const paiementDialog = ref(false)
const isSavingPaiement = ref(false)
const paiementForm = ref({
  engagement_id: null as number | null,
  reference: '',
  date_paiement: '',
  montant: null as number | null,
  mode_paiement: 'virement' as Paiement['mode_paiement'],
  banque_id: null as number | null,
  observation: '',
})

const comptesBudget = ref<any[]>([])
const banques = ref<any[]>([])

const loadFinance = async () => {
  isLoadingFinance.value = true
  try {
    const [summary, engs] = await Promise.all([
      engagementService.financeSummary(contratId.value),
      engagementService.listByContrat(contratId.value),
    ])
    financeSummary.value = summary
    financeEngagements.value = engs.data ?? []
  }
  catch { /* ignore */ }
  finally {
    isLoadingFinance.value = false
  }
}

const loadFinanceSelects = async () => {
  const [cb, bq] = await Promise.all([
    useApi('/comptes-budget?itemsPerPage=-1').json(),
    useApi('/banques?itemsPerPage=-1').json(),
  ])
  comptesBudget.value = cb.data.value?.data ?? []
  banques.value = bq.data.value?.data ?? []
}

const toggleEngagementPaiements = async (engId: number) => {
  if (expandedEngagement.value === engId) {
    expandedEngagement.value = null
    return
  }
  expandedEngagement.value = engId
  if (!engagementPaiements.value[engId]) {
    const res = await paiementService.listByEngagement(engId)
    engagementPaiements.value[engId] = res.data ?? []
  }
}

const openCreateEngagement = () => {
  isEditingEngagement.value = false
  selectedEngagement.value = null
  engagementForm.value = {
    numero: '',
    date_engagement: '',
    exercice: new Date().getFullYear().toString(),
    compte_budget_id: null,
    montant_engage: null,
    commentaire_validation: '',
  }
  engagementDialog.value = true
}

const openEditEngagement = (eng: Engagement) => {
  isEditingEngagement.value = true
  selectedEngagement.value = eng
  engagementForm.value = {
    numero: eng.numero,
    date_engagement: eng.date_engagement,
    exercice: eng.exercice,
    compte_budget_id: eng.compte_budget_id ?? null,
    montant_engage: eng.montant_engage,
    commentaire_validation: eng.commentaire_validation ?? '',
  }
  engagementDialog.value = true
}

const saveEngagement = async () => {
  isSavingEngagement.value = true
  try {
    if (isEditingEngagement.value && selectedEngagement.value) {
      await engagementService.update(selectedEngagement.value.id, engagementForm.value as any)
      snackbar.value = { show: true, text: 'Engagement modifié', color: 'success' }
    }
    else {
      await engagementService.create({ ...engagementForm.value, contrat_id: contratId.value } as any)
      snackbar.value = { show: true, text: 'Engagement créé', color: 'success' }
    }
    engagementDialog.value = false
    await loadFinance()
  }
  catch (e: any) {
    snackbar.value = { show: true, text: e?.response?._data?.message || 'Erreur', color: 'error' }
  }
  finally {
    isSavingEngagement.value = false
  }
}

const doEngagementAction = async (action: 'submit' | 'approve', eng: Engagement) => {
  try {
    await engagementService[action](eng.id)
    snackbar.value = { show: true, text: `Engagement ${action === 'submit' ? 'soumis' : 'approuvé'}`, color: 'success' }
    await loadFinance()
  }
  catch (e: any) {
    snackbar.value = { show: true, text: e?.response?._data?.message || 'Erreur', color: 'error' }
  }
}

const openRejectEngagement = (eng: Engagement) => {
  selectedEngagement.value = eng
  engagementCommentaire.value = ''
  engagementRejectDialog.value = true
}

const confirmRejectEngagement = async () => {
  if (!selectedEngagement.value) return
  try {
    await engagementService.reject(selectedEngagement.value.id, engagementCommentaire.value)
    engagementRejectDialog.value = false
    snackbar.value = { show: true, text: 'Engagement rejeté', color: 'warning' }
    await loadFinance()
  }
  catch (e: any) {
    snackbar.value = { show: true, text: e?.response?._data?.message || 'Erreur', color: 'error' }
  }
}

const openCreatePaiement = (eng: Engagement) => {
  paiementForm.value = {
    engagement_id: eng.id,
    reference: '',
    date_paiement: '',
    montant: null,
    mode_paiement: 'virement',
    banque_id: null,
    observation: '',
  }
  paiementDialog.value = true
}

const savePaiement = async () => {
  isSavingPaiement.value = true
  try {
    await paiementService.create(paiementForm.value as any)
    paiementDialog.value = false
    snackbar.value = { show: true, text: 'Paiement créé', color: 'success' }
    const engId = paiementForm.value.engagement_id!
    const res = await paiementService.listByEngagement(engId)
    engagementPaiements.value[engId] = res.data ?? []
    await loadFinance()
  }
  catch (e: any) {
    const msg = e?.response?._data?.errors?.montant?.[0]
      || e?.response?._data?.message
      || 'Erreur'
    snackbar.value = { show: true, text: msg, color: 'error' }
  }
  finally {
    isSavingPaiement.value = false
  }
}

const paidStatusConfig: Record<string, { label: string; color: string }> = {
  non_paye: { label: 'Non payé',  color: 'error' },
  partiel:  { label: 'Partiel',   color: 'warning' },
  paye:     { label: 'Payé',      color: 'success' },
}

const engStatutConfig: Record<string, { label: string; color: string }> = {
  draft:     { label: 'Brouillon', color: 'default' },
  submitted: { label: 'Soumis',    color: 'info' },
  approved:  { label: 'Approuvé',  color: 'success' },
  rejected:  { label: 'Rejeté',    color: 'error' },
  archived:  { label: 'Archivé',   color: 'secondary' },
}

const paiStatutConfig: Record<string, { label: string; color: string }> = {
  draft:     { label: 'Brouillon', color: 'default' },
  submitted: { label: 'Soumis',    color: 'info' },
  approved:  { label: 'Approuvé',  color: 'success' },
  rejected:  { label: 'Rejeté',    color: 'error' },
}

const modeLabels: Record<string, string> = {
  virement: 'Virement', cheque: 'Chèque', espece: 'Espèce', autre: 'Autre',
}

const modeOptions = [
  { title: 'Virement', value: 'virement' },
  { title: 'Chèque', value: 'cheque' },
  { title: 'Espèce', value: 'espece' },
  { title: 'Autre', value: 'autre' },
]

const etapeStatutOptions = [
  { title: 'En attente', value: 'pending' },
  { title: 'En cours', value: 'in_progress' },
  { title: 'Terminé', value: 'completed' },
  { title: 'Bloqué', value: 'blocked' },
]

// ─── Avenants ───────────────────────────────────────────────────────────────
const avenantsStore = useAvenantsStore()
const avenantDialog = ref(false)
const avenantSnackbar = ref({ show: false, text: '', color: 'success' })

const loadAvenants = () => avenantsStore.fetchAvenantsByContrat(contratId.value)

// ─── Ordres de service ──────────────────────────────────────────────────────
const ordreServicesStore = useOrdreServicesStore()
const osDialog = ref(false)
const osSnackbar = ref({ show: false, text: '', color: 'success' })

const loadOrdreServices = () => ordreServicesStore.fetchOrdreServicesByContrat(contratId.value)

// ─── Réceptions ─────────────────────────────────────────────────────────────
const receptionsStore = useReceptionsStore()
const receptionDialog = ref(false)
const receptionSnackbar = ref({ show: false, text: '', color: 'success' })

const loadReceptions = () => receptionsStore.fetchReceptionsByContrat(contratId.value)

const hasProvisoireApprovedReception = computed(() =>
  receptionsStore.receptions.some((r: any) => r.type_reception === 'provisoire' && r.statut === 'approved'),
)

watch(activeTab, async (tab) => {
  if (tab === 'finance' && !financeSummary.value)
    await loadFinance()
  if (tab === 'avenants')
    loadAvenants()
  if (tab === 'ordre-services')
    loadOrdreServices()
  if (tab === 'receptions')
    loadReceptions()
})

const avenantStatutColor = (s: string) =>
  ({ draft: 'default', submitted: 'info', approved: 'success', rejected: 'error' }[s] || 'default')

const avenantStatutLabel = (s: string) =>
  ({ draft: 'Brouillon', submitted: 'Soumis', approved: 'Approuvé', rejected: 'Rejeté' }[s] || s)

const avenantTypeLabel: Record<string, string> = {
  montant: 'Montant', delai: 'Délai', objet: 'Objet', mixte: 'Mixte',
}

const formatAvenantDate = (d: string) => (d ? new Date(d).toLocaleDateString('fr-FR') : '-')

const formatAvenantMontant = (v: number) =>
  v != null ? formatCurrencyXOF(v) : '-'

const onAvenantFormSubmit = async (payload: Record<string, any>) => {
  try {
    await avenantsStore.createAvenant(contratId.value, payload)
    avenantDialog.value = false
    avenantSnackbar.value = { show: true, text: 'Avenant créé avec succès', color: 'success' }
    loadAvenants()
    await store.fetchContrat(contratId.value)
  }
  catch (e: any) {
    avenantSnackbar.value = {
      show: true,
      text: e?.data?.message || 'Erreur lors de la création',
      color: 'error',
    }
  }
}

const osTypeLabels: Record<string, string> = {
  demarrage: 'Démarrage', suspension: 'Suspension', reprise: 'Reprise',
  arret: 'Arrêt', modification: 'Modification', autre: 'Autre',
}
const osStatutColor = (s: string) =>
  ({ draft: 'default', submitted: 'info', approved: 'success', rejected: 'error', executed: 'success', archived: 'secondary' }[s] || 'default')
const osStatutLabel = (s: string) =>
  ({ draft: 'Brouillon', submitted: 'Soumis', approved: 'Approuvé', rejected: 'Rejeté', executed: 'Exécuté', archived: 'Archivé' }[s] || s)
const onOSFormSubmit = async (payload: Record<string, any>) => {
  try {
    await ordreServicesStore.createOrdreService(contratId.value, payload)
    osDialog.value = false
    osSnackbar.value = { show: true, text: 'Ordre de service créé avec succès', color: 'success' }
    loadOrdreServices()
    await store.fetchContrat(contratId.value)
  }
  catch (e: any) {
    osSnackbar.value = { show: true, text: e?.data?.message || 'Erreur', color: 'error' }
  }
}

const onReceptionFormSubmit = async (payload: Record<string, any>) => {
  try {
    await receptionsStore.createReception(contratId.value, payload)
    receptionDialog.value = false
    receptionSnackbar.value = { show: true, text: 'Réception créée avec succès', color: 'success' }
    loadReceptions()
    await store.fetchContrat(contratId.value)
  }
  catch (e: any) {
    receptionSnackbar.value = { show: true, text: e?.data?.message || 'Erreur', color: 'error' }
  }
}
</script>

<template>
  <div v-if="isLoadingContrat" class="d-flex justify-center align-center pa-12">
    <VProgressCircular indeterminate color="primary" size="48" />
  </div>

  <VAlert v-else-if="loadError" type="error" variant="tonal" class="ma-4">
    {{ loadError }}
    <template #append>
      <VBtn variant="text" size="small" @click="router.push({ name: 'apps-contrats' })">
        Retour à la liste
      </VBtn>
    </template>
  </VAlert>

  <div v-else-if="contrat">
    <!-- ─── Header ─── -->
    <VRow class="mb-4">
      <VCol cols="12">
        <VCard>
          <VCardText class="pa-4">
            <div class="d-flex align-center gap-3 flex-wrap">
              <VBtn icon variant="text" @click="router.back()">
                <VIcon icon="tabler-arrow-left" />
              </VBtn>
              <div class="flex-grow-1">
                <div class="d-flex align-center gap-2 flex-wrap">
                  <h1 class="text-h5 font-weight-bold">{{ contrat.reference }}</h1>
                  <span v-if="contrat.numero" class="text-caption text-medium-emphasis">({{ contrat.numero }})</span>
                  <VChip :color="statutColor(contrat.statut)" size="small">{{ statutLabel(contrat.statut) }}</VChip>
                </div>
                <p class="text-body-2 text-medium-emphasis mb-0 mt-1">{{ contrat.objet }}</p>
              </div>
              <VSpacer />

              <!-- Actions selon statut -->
              <div class="d-flex gap-2 flex-wrap">
                <VBtn
                  v-if="contrat.statut === 'draft'"
                  color="info"
                  prepend-icon="tabler-send"
                  size="small"
                  @click="doContratAction('submitContrat')"
                >
                  Soumettre
                </VBtn>
                <VBtn
                  v-if="contrat.statut === 'submitted'"
                  color="success"
                  prepend-icon="tabler-check"
                  size="small"
                  @click="doContratAction('approveContrat')"
                >
                  Approuver
                </VBtn>
                <VBtn
                  v-if="contrat.statut === 'submitted'"
                  color="error"
                  prepend-icon="tabler-x"
                  size="small"
                  variant="tonal"
                  @click="rejectDialog = true; motifRejet = ''"
                >
                  Rejeter
                </VBtn>
                <VBtn
                  v-if="contrat.statut === 'approved'"
                  color="secondary"
                  prepend-icon="tabler-archive"
                  size="small"
                  @click="doContratAction('archiveContrat')"
                >
                  Archiver
                </VBtn>
              </div>
            </div>

            <!-- Barre de progression -->
            <div class="mt-3">
              <div class="d-flex justify-space-between mb-1">
                <span class="text-caption text-medium-emphasis">Progression des étapes</span>
                <span class="text-caption font-weight-bold">{{ progressionPct }}%</span>
              </div>
              <VProgressLinear
                :model-value="progressionPct"
                :color="progressionPct === 100 ? 'success' : 'primary'"
                height="6"
                rounded
              />
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- ─── Onglets ─── -->
    <VCard>
      <VTabs v-model="activeTab" bg-color="surface">
        <VTab value="info">
          <VIcon icon="tabler-info-circle" class="me-1" size="18" />
          Informations
        </VTab>
        <VTab value="suivi">
          <VIcon icon="tabler-timeline" class="me-1" size="18" />
          Suivi des étapes
          <VBadge
            v-if="contrat.etapes?.some((e: any) => isEnRetard(e))"
            color="error"
            content="!"
            inline
            class="ms-1"
          />
        </VTab>
        <VTab value="ged">
          <VIcon icon="tabler-paperclip" class="me-1" size="18" />
          Documents
        </VTab>
        <VTab value="avenants">
          <VIcon icon="tabler-file-plus" class="me-1" size="18" />
          Avenants
          <VBadge
            v-if="avenantsStore.avenants.length"
            :content="avenantsStore.avenants.length"
            inline
            color="primary"
            class="ms-1"
          />
        </VTab>
        <VTab value="ordre-services">
          <VIcon icon="tabler-clipboard-text" class="me-1" size="18" />
          Ordres de service
          <VBadge
            v-if="ordreServicesStore.ordreServices.length"
            :content="ordreServicesStore.ordreServices.length"
            inline
            color="primary"
            class="ms-1"
          />
        </VTab>
        <VTab value="receptions">
          <VIcon icon="tabler-package-import" class="me-1" size="18" />
          Réceptions
          <VBadge
            v-if="receptionsStore.receptions.length"
            :content="receptionsStore.receptions.length"
            inline
            color="primary"
            class="ms-1"
          />
        </VTab>
        <VTab value="fournisseur">
          <VIcon icon="tabler-building-store" class="me-1" size="18" />
          Titulaire
        </VTab>
        <VTab value="finance">
          <VIcon icon="tabler-coin" class="me-1" size="18" />
          Finance
        </VTab>
      </VTabs>

      <VDivider />

      <VTabsWindow v-model="activeTab">

        <!-- ── Onglet Informations ── -->
        <VTabsWindowItem value="info">
          <VCardText>
            <VRow>
              <VCol cols="12" md="6">
                <VTable density="compact">
                  <tbody>
                    <tr>
                      <td class="text-medium-emphasis text-caption" style="width:40%">Référence</td>
                      <td class="font-weight-medium">{{ contrat.reference }}</td>
                    </tr>
                    <tr v-if="contrat.numero">
                      <td class="text-medium-emphasis text-caption">N° officiel</td>
                      <td>{{ contrat.numero }}</td>
                    </tr>
                    <tr>
                      <td class="text-medium-emphasis text-caption">Exercice</td>
                      <td>{{ contrat.exercice }}</td>
                    </tr>
                    <tr>
                      <td class="text-medium-emphasis text-caption">Mode de passation</td>
                      <td>{{ contrat.mode_passation ?? '-' }}</td>
                    </tr>
                    <tr>
                      <td class="text-medium-emphasis text-caption">Montant initial</td>
                      <td class="font-weight-bold text-primary">{{ formatMontant(contrat.montant_initial) }}</td>
                    </tr>
                    <tr>
                      <td class="text-medium-emphasis text-caption">Montant actuel</td>
                      <td class="font-weight-bold">{{ formatMontant(contrat.montant_actuel) }}</td>
                    </tr>
                    <tr>
                      <td class="text-medium-emphasis text-caption">Devise</td>
                      <td>{{ contrat.devise }}</td>
                    </tr>
                  </tbody>
                </VTable>
              </VCol>
              <VCol cols="12" md="6">
                <VTable density="compact">
                  <tbody>
                    <tr>
                      <td class="text-medium-emphasis text-caption" style="width:40%">Date de signature</td>
                      <td>{{ formatDate(contrat.date_signature) }}</td>
                    </tr>
                    <tr>
                      <td class="text-medium-emphasis text-caption">Date de début</td>
                      <td>{{ formatDate(contrat.date_debut) }}</td>
                    </tr>
                    <tr>
                      <td class="text-medium-emphasis text-caption">Date de fin</td>
                      <td>{{ formatDate(contrat.date_fin) }}</td>
                    </tr>
                    <tr>
                      <td class="text-medium-emphasis text-caption">Date prévisionnelle de réception</td>
                      <td>{{ formatDate(contrat.date_previsionnelle_reception) }}</td>
                    </tr>
                    <tr>
                      <td class="text-medium-emphasis text-caption">Durée d'exécution</td>
                      <td>{{ contrat.duree_execution ? contrat.duree_execution + ' jours' : '-' }}</td>
                    </tr>
                    <tr>
                      <td class="text-medium-emphasis text-caption">Compte budget</td>
                      <td>{{ contrat.compte_budget?.libelle ?? '-' }}</td>
                    </tr>
                    <tr>
                      <td class="text-medium-emphasis text-caption">Agent responsable</td>
                      <td>{{ contrat.agent ? `${contrat.agent.prenom} ${contrat.agent.nom}` : '-' }}</td>
                    </tr>
                    <tr>
                      <td class="text-medium-emphasis text-caption">Avis lié</td>
                      <td>{{ contrat.avis?.reference ?? '-' }}</td>
                    </tr>
                    <tr>
                      <td class="text-medium-emphasis text-caption">PV d'attribution</td>
                      <td>{{ contrat.pv?.reference ?? '-' }}</td>
                    </tr>
                  </tbody>
                </VTable>
              </VCol>
              <VCol v-if="contrat.observations" cols="12">
                <VAlert type="info" variant="tonal" density="compact">
                  <strong>Observations :</strong> {{ contrat.observations }}
                </VAlert>
              </VCol>
              <VCol v-if="contrat.motif_rejet" cols="12">
                <VAlert type="error" variant="tonal" density="compact">
                  <strong>Motif de rejet :</strong> {{ contrat.motif_rejet }}
                </VAlert>
              </VCol>
            </VRow>
          </VCardText>
        </VTabsWindowItem>

        <!-- ── Onglet Suivi des étapes ── -->
        <VTabsWindowItem value="suivi">
          <VCardText>
            <!-- Alerte étapes en retard -->
            <VAlert
              v-if="contrat.etapes?.some((e: any) => isEnRetard(e))"
              type="warning"
              variant="tonal"
              class="mb-4"
              prepend-icon="tabler-alert-triangle"
            >
              <strong>{{ contrat.etapes?.filter((e: any) => isEnRetard(e)).length }} étape(s) en retard</strong>
              — Vérifiez les dates limites dépassées.
            </VAlert>

            <VTimeline side="end" density="compact" class="mt-2">
              <VTimelineItem
                v-for="etape in contrat.etapes"
                :key="etape.id"
                :dot-color="isEnRetard(etape) ? 'error' : etapeStatutColor(etape.statut)"
                size="small"
              >
                <template #icon>
                  <VIcon :icon="etapeIcons[etape.type_etape] ?? 'tabler-circle'" size="14" />
                </template>
                <template #opposite>
                  <div class="text-right">
                    <span class="text-caption d-block">
                      {{ etape.date_effective ? formatDate(etape.date_effective) : (etape.date_prevue ? formatDate(etape.date_prevue) : '—') }}
                    </span>
                    <span v-if="etape.date_limite" class="text-caption d-block" :class="isEnRetard(etape) ? 'text-error' : 'text-medium-emphasis'">
                      Limite : {{ formatDate(etape.date_limite) }}
                    </span>
                  </div>
                </template>

                <VCard variant="outlined" :color="isEnRetard(etape) ? 'error' : undefined" class="pa-3">
                  <div class="d-flex align-center gap-2">
                    <div class="flex-grow-1">
                      <div class="d-flex align-center gap-2 flex-wrap">
                        <span class="font-weight-bold text-body-2">{{ etapeLabels[etape.type_etape] ?? etape.type_etape }}</span>
                        <VChip :color="etapeStatutColor(etape.statut)" size="x-small">
                          {{ etapeStatutLabel(etape.statut) }}
                        </VChip>
                        <VChip v-if="isEnRetard(etape)" color="error" size="x-small" prepend-icon="tabler-clock-exclamation">
                          {{ joursRetard(etape) }}j de retard
                        </VChip>
                      </div>
                      <p v-if="etape.commentaire" class="text-caption text-medium-emphasis mt-1 mb-0">
                        {{ etape.commentaire }}
                      </p>
                    </div>
                    <div class="d-flex gap-1">
                      <VBtn
                        v-if="etape.piece_jointe"
                        icon
                        variant="text"
                        size="small"
                        color="success"
                        @click="downloadEtapePiece(etape)"
                      >
                        <VIcon icon="tabler-download" size="16" />
                        <VTooltip activator="parent">Télécharger justificatif</VTooltip>
                      </VBtn>
                      <VBtn icon variant="text" size="small" color="primary" @click="openEtapeEdit(etape)">
                        <VIcon icon="tabler-edit" size="16" />
                        <VTooltip activator="parent">Mettre à jour</VTooltip>
                      </VBtn>
                    </div>
                  </div>
                </VCard>
              </VTimelineItem>
            </VTimeline>
          </VCardText>
        </VTabsWindowItem>

        <!-- ── Onglet GED ── -->
        <VTabsWindowItem value="ged">
          <VCardText>
            <DocumentsPanel
              documentable-type="contrats"
              :documentable-id="contratId"
            />
          </VCardText>
        </VTabsWindowItem>

        <!-- ── Onglet Avenants ── -->
        <VTabsWindowItem value="avenants">
          <VCardText>
            <div class="d-flex align-center mb-4">
              <span class="text-subtitle-1 font-weight-bold">Avenants</span>
              <VSpacer />
              <VBtn
                color="primary"
                size="small"
                prepend-icon="tabler-plus"
                :disabled="contrat.statut === 'archived'"
                @click="avenantDialog = true"
              >
                Nouvel avenant
              </VBtn>
            </div>

            <VAlert
              v-if="contrat.statut === 'archived'"
              type="warning"
              variant="tonal"
              class="mb-4"
            >
              Aucun avenant ne peut être créé sur un contrat archivé.
            </VAlert>

            <!-- Liste + Timeline -->
            <div v-if="avenantsStore.avenants.length > 0" class="mb-4">
              <h4 class="text-body-1 font-weight-bold mb-2">Historique</h4>
              <VTimeline side="end" density="compact">
                <VTimelineItem
                  v-for="av in avenantsStore.avenants"
                  :key="av.id"
                  :dot-color="avenantStatutColor(av.statut)"
                  size="small"
                >
                  <VCard variant="outlined" class="pa-3">
                    <div class="d-flex align-center gap-2 flex-wrap">
                      <span class="font-weight-bold">{{ av.numero }}</span>
                      <VChip :color="avenantStatutColor(av.statut)" size="x-small">
                        {{ avenantStatutLabel(av.statut) }}
                      </VChip>
                      <span class="text-caption">{{ avenantTypeLabel[av.type_avenant] }}</span>
                      <span class="text-caption text-medium-emphasis">{{ formatAvenantDate(av.date_signature) }}</span>
                      <VSpacer />
                      <VBtn
                        size="x-small"
                        variant="text"
                        @click="$router.push(`/apps/contrats/avenants/${av.id}`)"
                      >
                        Voir
                      </VBtn>
                    </div>
                    <p v-if="av.montant_variation != null" class="text-caption mb-0 mt-1">
                      Montant : {{ formatAvenantMontant(av.ancien_montant) }} → {{ formatAvenantMontant(av.nouveau_montant) }}
                    </p>
                    <p v-if="av.nouvelle_date_fin" class="text-caption mb-0">
                      Date fin : {{ formatAvenantDate(av.nouvelle_date_fin) }}
                    </p>
                  </VCard>
                </VTimelineItem>
              </VTimeline>
            </div>

            <div v-else class="text-center py-6 text-medium-emphasis">
              <VIcon icon="tabler-file-off" size="40" class="mb-2 opacity-30" />
              <p class="text-body-2">Aucun avenant pour ce contrat</p>
              <VBtn
                v-if="contrat.statut !== 'archived'"
                color="primary"
                variant="tonal"
                size="small"
                class="mt-2"
                @click="avenantDialog = true"
              >
                Créer un avenant
              </VBtn>
            </div>

            <!-- Impact financier (résumé) -->
            <VCard v-if="financeSummary && avenantsStore.avenants.some((a: any) => a.statut === 'approved')" variant="tonal" color="primary" class="mt-4">
              <VCardText>
                <p class="text-caption text-medium-emphasis mb-1">Montant actuel du contrat (après avenants)</p>
                <p class="text-h6 font-weight-bold mb-0">{{ formatCurrencyXOF(financeSummary.montant_contrat) }}</p>
                <p class="text-caption mt-1 mb-0">Total engagé : {{ formatCurrencyXOF(financeSummary.total_engaged) }} — Reste à payer : {{ formatCurrencyXOF(financeSummary.remaining) }}</p>
              </VCardText>
            </VCard>
          </VCardText>
        </VTabsWindowItem>

        <!-- ── Onglet Ordres de service ── -->
        <VTabsWindowItem value="ordre-services">
          <VCardText>
            <div class="d-flex align-center mb-4">
              <span class="text-subtitle-1 font-weight-bold">Ordres de service</span>
              <VSpacer />
              <VBtn
                color="primary"
                size="small"
                prepend-icon="tabler-plus"
                :disabled="contrat.statut === 'archived'"
                @click="osDialog = true"
              >
                Nouvel OS
              </VBtn>
            </div>

            <VAlert
              v-if="contrat.statut === 'archived'"
              type="warning"
              variant="tonal"
              class="mb-4"
            >
              Aucun ordre de service ne peut être créé sur un contrat archivé.
            </VAlert>

            <div v-if="ordreServicesStore.ordreServices.length > 0" class="mb-4">
              <VTable density="compact">
                <thead>
                  <tr>
                    <th>Numéro</th>
                    <th>Type</th>
                    <th>Objet</th>
                    <th>Date émission</th>
                    <th>Impact</th>
                    <th>Statut</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in ordreServicesStore.ordreServices" :key="item.id">
                    <td class="font-weight-medium">{{ item.numero }}</td>
                    <td>{{ osTypeLabels[item.type_os] ?? item.type_os }}</td>
                    <td class="text-truncate" style="max-width:200px">{{ item.objet }}</td>
                    <td>{{ formatDate(item.date_emission) }}</td>
                    <td>
                      <span v-if="item.impact_delai !== 'none' && item.delai_jours != null">
                        {{ item.impact_delai === 'extend' ? '+' : '-' }}{{ item.delai_jours }} j
                      </span>
                      <span v-else>-</span>
                    </td>
                    <td>
                      <VChip :color="osStatutColor(item.statut)" size="x-small">
                        {{ osStatutLabel(item.statut) }}
                      </VChip>
                    </td>
                    <td>
                      <VBtn size="x-small" variant="text" @click="$router.push(`/apps/contrats/ordre-services/${item.id}`)">
                        Voir
                      </VBtn>
                    </td>
                  </tr>
                </tbody>
              </VTable>
            </div>

            <div v-else class="text-center py-6 text-medium-emphasis">
              <VIcon icon="tabler-clipboard-off" size="40" class="mb-2 opacity-30" />
              <p class="text-body-2">Aucun ordre de service pour ce contrat</p>
              <VBtn
                v-if="contrat.statut !== 'archived'"
                color="primary"
                variant="tonal"
                size="small"
                class="mt-2"
                @click="osDialog = true"
              >
                Créer un OS
              </VBtn>
            </div>
          </VCardText>
        </VTabsWindowItem>

        <!-- ── Onglet Réceptions ── -->
        <VTabsWindowItem value="receptions">
          <VCardText>
            <div class="d-flex align-center mb-4">
              <span class="text-subtitle-1 font-weight-bold">Réceptions (PV)</span>
              <VSpacer />
              <VBtn
                color="primary"
                size="small"
                prepend-icon="tabler-plus"
                :disabled="contrat.statut === 'archived'"
                @click="receptionDialog = true"
              >
                Nouvelle réception
              </VBtn>
            </div>

            <VAlert
              v-if="contrat.statut === 'archived'"
              type="warning"
              variant="tonal"
              class="mb-4"
            >
              Aucune réception ne peut être créée sur un contrat archivé.
            </VAlert>

            <VAlert
              v-if="contrat.date_previsionnelle_reception"
              type="info"
              variant="tonal"
              density="compact"
              class="mb-4"
              icon="tabler-calendar-event"
            >
              Date prévisionnelle de réception :
              <strong>{{ formatDate(contrat.date_previsionnelle_reception) }}</strong>
            </VAlert>

            <div v-if="contrat.status_execution" class="mb-3">
              <VChip size="small" color="info" variant="tonal">
                Statut exécution : {{ contrat.status_execution === 'reception_provisoire' ? 'Réception provisoire' : contrat.status_execution === 'reception_definitive' ? 'Réception définitive' : contrat.status_execution }}
              </VChip>
              <VChip v-if="contrat.cloturable" size="small" color="success" class="ms-2">
                Contrat clôturable
              </VChip>
            </div>

            <div v-if="receptionsStore.receptions.length > 0" class="mb-4">
              <VTable density="compact">
                <thead>
                  <tr>
                    <th>Numéro</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Conformité</th>
                    <th>Statut</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in receptionsStore.receptions" :key="item.id">
                    <td class="font-weight-medium">{{ item.numero }}</td>
                    <td>{{ item.type_reception === 'provisoire' ? 'Provisoire' : item.type_reception === 'partielle' ? 'Partielle' : 'Définitive' }}</td>
                    <td>{{ formatDate(item.date_reception) }}</td>
                    <td>
                      <VChip
                        :color="item.statut_conformite === 'conforme' ? 'success' : item.statut_conformite === 'non_conforme' ? 'error' : 'warning'"
                        size="x-small"
                      >
                        {{ item.statut_conformite === 'conforme' ? 'Conforme' : item.statut_conformite === 'non_conforme' ? 'Non conforme' : 'Avec réserves' }}
                      </VChip>
                    </td>
                    <td>
                      <VChip
                        :color="item.statut === 'draft' ? 'default' : item.statut === 'submitted' ? 'info' : item.statut === 'approved' ? 'success' : 'error'"
                        size="x-small"
                      >
                        {{ item.statut === 'draft' ? 'Brouillon' : item.statut === 'submitted' ? 'Soumis' : item.statut === 'approved' ? 'Approuvé' : 'Rejeté' }}
                      </VChip>
                    </td>
                    <td>
                      <VBtn size="x-small" variant="text" @click="$router.push(`/apps/contrats/receptions/${item.id}`)">
                        Voir
                      </VBtn>
                    </td>
                  </tr>
                </tbody>
              </VTable>
            </div>

            <div v-else class="text-center py-6 text-medium-emphasis">
              <VIcon icon="tabler-package-off" size="40" class="mb-2 opacity-30" />
              <p class="text-body-2">Aucune réception pour ce contrat</p>
              <VBtn
                v-if="contrat.statut !== 'archived'"
                color="primary"
                variant="tonal"
                size="small"
                class="mt-2"
                @click="receptionDialog = true"
              >
                Créer une réception
              </VBtn>
            </div>
          </VCardText>
        </VTabsWindowItem>

        <!-- ── Onglet Fournisseur ── -->
        <VTabsWindowItem value="fournisseur">
          <VCardText v-if="contrat.fournisseur">
            <VRow>
              <VCol cols="12" md="6">
                <h3 class="text-subtitle-1 font-weight-bold mb-3">Informations générales</h3>
                <VTable density="compact">
                  <tbody>
                    <tr>
                      <td class="text-medium-emphasis text-caption" style="width:40%">Raison sociale</td>
                      <td class="font-weight-bold">{{ contrat.fournisseur.raison_sociale }}</td>
                    </tr>
                    <tr v-if="contrat.fournisseur.sigle">
                      <td class="text-medium-emphasis text-caption">Sigle</td>
                      <td>{{ contrat.fournisseur.sigle }}</td>
                    </tr>
                    <tr>
                      <td class="text-medium-emphasis text-caption">NIF</td>
                      <td>{{ contrat.fournisseur.nif ?? '-' }}</td>
                    </tr>
                    <tr>
                      <td class="text-medium-emphasis text-caption">RC</td>
                      <td>{{ contrat.fournisseur.rc ?? '-' }}</td>
                    </tr>
                    <tr>
                      <td class="text-medium-emphasis text-caption">Téléphone</td>
                      <td>{{ contrat.fournisseur.telephone ?? '-' }}</td>
                    </tr>
                    <tr>
                      <td class="text-medium-emphasis text-caption">Fax</td>
                      <td>{{ contrat.fournisseur.fax ?? '-' }}</td>
                    </tr>
                    <tr>
                      <td class="text-medium-emphasis text-caption">Email</td>
                      <td>{{ contrat.fournisseur.email ?? '-' }}</td>
                    </tr>
                    <tr>
                      <td class="text-medium-emphasis text-caption">Adresse</td>
                      <td>{{ contrat.fournisseur.adresse ?? '-' }}</td>
                    </tr>
                    <tr>
                      <td class="text-medium-emphasis text-caption">Ville / Région</td>
                      <td>{{ [contrat.fournisseur.ville, contrat.fournisseur.region].filter(Boolean).join(', ') || '-' }}</td>
                    </tr>
                    <tr>
                      <td class="text-medium-emphasis text-caption">Pays</td>
                      <td>{{ contrat.fournisseur.pays ?? '-' }}</td>
                    </tr>
                    <tr>
                      <td class="text-medium-emphasis text-caption">Domaine d'activité</td>
                      <td>{{ contrat.fournisseur.domaine_activite?.libelle ?? '-' }}</td>
                    </tr>
                  </tbody>
                </VTable>
              </VCol>
              <VCol cols="12" md="6">
                <h3 class="text-subtitle-1 font-weight-bold mb-3">Représentant légal</h3>
                <VTable density="compact">
                  <tbody>
                    <tr>
                      <td class="text-medium-emphasis text-caption" style="width:40%">Civilité</td>
                      <td>{{ contrat.fournisseur.civilite ?? '-' }}</td>
                    </tr>
                    <tr>
                      <td class="text-medium-emphasis text-caption">Nom complet</td>
                      <td class="font-weight-medium">{{ contrat.fournisseur.representant ?? '-' }}</td>
                    </tr>
                    <tr>
                      <td class="text-medium-emphasis text-caption">Qualité / Fonction</td>
                      <td>{{ contrat.fournisseur.qualite_fonction ?? contrat.fournisseur.fonction_representant ?? '-' }}</td>
                    </tr>
                  </tbody>
                </VTable>

                <h3 class="text-subtitle-1 font-weight-bold mb-3 mt-4">Informations bancaires</h3>
                <div v-if="contrat.fournisseur.banques?.length">
                  <VCard
                    v-for="banque in contrat.fournisseur.banques"
                    :key="banque.id"
                    variant="outlined"
                    class="pa-3 mb-2"
                  >
                    <div class="d-flex align-center gap-2 mb-1">
                      <VIcon icon="tabler-building-bank" size="16" color="primary" />
                      <span class="font-weight-medium text-body-2">{{ banque.banque?.libelle }}</span>
                      <VChip v-if="banque.principal" size="x-small" color="primary">Principal</VChip>
                    </div>
                    <p class="text-caption mb-0">N° compte : <strong>{{ banque.numero_compte }}</strong></p>
                    <p v-if="banque.rib" class="text-caption mb-0">RIB : {{ banque.rib }}</p>
                    <p v-if="banque.iban" class="text-caption mb-0">IBAN : {{ banque.iban }}</p>
                    <p v-if="banque.swift" class="text-caption mb-0">SWIFT : {{ banque.swift }}</p>
                  </VCard>
                </div>
                <p v-else class="text-caption text-medium-emphasis">Aucune information bancaire enregistrée.</p>
              </VCol>
            </VRow>
          </VCardText>
        </VTabsWindowItem>

        <!-- ── Onglet Finance ── -->
        <VTabsWindowItem value="finance">
          <VCardText>
            <template v-if="isLoadingFinance">
              <div class="d-flex justify-center align-center py-10">
                <VProgressCircular indeterminate color="primary" />
              </div>
            </template>

            <template v-else-if="financeSummary">
              <!-- KPI Cards -->
              <VRow class="mb-4">
                <VCol cols="12" sm="6" md="3">
                  <VCard variant="tonal" color="primary">
                    <VCardText class="text-center">
                      <p class="text-caption text-medium-emphasis mb-1">Montant contrat</p>
                      <p class="text-h6 font-weight-bold mb-0">{{ formatCurrencyXOF(financeSummary.montant_contrat) }}</p>
                    </VCardText>
                  </VCard>
                </VCol>
                <VCol cols="12" sm="6" md="3">
                  <VCard variant="tonal" color="info">
                    <VCardText class="text-center">
                      <p class="text-caption text-medium-emphasis mb-1">Total engagé</p>
                      <p class="text-h6 font-weight-bold mb-0">{{ formatCurrencyXOF(financeSummary.total_engaged) }}</p>
                    </VCardText>
                  </VCard>
                </VCol>
                <VCol cols="12" sm="6" md="3">
                  <VCard variant="tonal" color="success">
                    <VCardText class="text-center">
                      <p class="text-caption text-medium-emphasis mb-1">Total payé</p>
                      <p class="text-h6 font-weight-bold mb-0">{{ formatCurrencyXOF(financeSummary.total_paid) }}</p>
                    </VCardText>
                  </VCard>
                </VCol>
                <VCol cols="12" sm="6" md="3">
                  <VCard variant="tonal" :color="financeSummary.remaining > 0 ? 'warning' : 'success'">
                    <VCardText class="text-center">
                      <p class="text-caption text-medium-emphasis mb-1">Reste à payer</p>
                      <p class="text-h6 font-weight-bold mb-0">{{ formatCurrencyXOF(financeSummary.remaining) }}</p>
                      <VChip
                        :color="paidStatusConfig[financeSummary.paid_status]?.color"
                        size="x-small"
                        class="mt-1"
                      >
                        {{ paidStatusConfig[financeSummary.paid_status]?.label }}
                      </VChip>
                    </VCardText>
                  </VCard>
                </VCol>
              </VRow>

              <!-- Alerte dépassement -->
              <VAlert
                v-if="financeSummary.total_engaged > financeSummary.montant_contrat"
                type="warning"
                variant="tonal"
                class="mb-4"
                prepend-icon="tabler-alert-triangle"
              >
                Le total engagé dépasse le montant du contrat.
              </VAlert>

              <!-- Header engagements -->
              <div class="d-flex align-center mb-3">
                <span class="text-subtitle-1 font-weight-bold">Engagements</span>
                <VSpacer />
                <VBtn
                  color="primary"
                  size="small"
                  prepend-icon="tabler-plus"
                  @click="openCreateEngagement"
                >
                  Nouvel engagement
                </VBtn>
              </div>

              <!-- Liste engagements -->
              <div v-if="financeEngagements.length === 0" class="text-center py-6 text-medium-emphasis">
                <VIcon icon="tabler-receipt-off" size="40" class="mb-2 opacity-30" />
                <p class="text-body-2">Aucun engagement enregistré</p>
              </div>

              <VCard
                v-for="eng in financeEngagements"
                :key="eng.id"
                variant="outlined"
                class="mb-3"
              >
                <VCardText class="pa-3">
                  <div class="d-flex align-center gap-2 flex-wrap">
                    <div class="flex-grow-1">
                      <div class="d-flex align-center gap-2 flex-wrap">
                        <span class="font-weight-bold text-body-2">{{ eng.numero }}</span>
                        <VChip :color="engStatutConfig[eng.statut]?.color" size="x-small" label>
                          {{ engStatutConfig[eng.statut]?.label }}
                        </VChip>
                        <span class="text-caption text-medium-emphasis">
                          {{ formatCurrencyXOF(eng.montant_engage) }} · {{ eng.exercice }}
                        </span>
                      </div>
                      <p v-if="eng.compte_budget" class="text-caption text-medium-emphasis mb-0 mt-1">
                        Compte : {{ eng.compte_budget.libelle }}
                      </p>
                    </div>
                    <div class="d-flex gap-1">
                      <VBtn
                        v-if="eng.statut === 'draft'"
                        icon variant="text" size="small" color="primary"
                        @click="openEditEngagement(eng)"
                      >
                        <VIcon icon="tabler-edit" size="16" />
                        <VTooltip activator="parent">Modifier</VTooltip>
                      </VBtn>
                      <VBtn
                        v-if="eng.statut === 'draft'"
                        icon variant="text" size="small" color="info"
                        @click="doEngagementAction('submit', eng)"
                      >
                        <VIcon icon="tabler-send" size="16" />
                        <VTooltip activator="parent">Soumettre</VTooltip>
                      </VBtn>
                      <VBtn
                        v-if="eng.statut === 'submitted'"
                        icon variant="text" size="small" color="success"
                        @click="doEngagementAction('approve', eng)"
                      >
                        <VIcon icon="tabler-check" size="16" />
                        <VTooltip activator="parent">Approuver</VTooltip>
                      </VBtn>
                      <VBtn
                        v-if="eng.statut === 'submitted'"
                        icon variant="text" size="small" color="error"
                        @click="openRejectEngagement(eng)"
                      >
                        <VIcon icon="tabler-x" size="16" />
                        <VTooltip activator="parent">Rejeter</VTooltip>
                      </VBtn>
                      <VBtn
                        v-if="eng.statut === 'approved'"
                        icon variant="text" size="small" color="primary"
                        @click="openCreatePaiement(eng)"
                      >
                        <VIcon icon="tabler-cash-plus" size="16" />
                        <VTooltip activator="parent">Nouveau paiement</VTooltip>
                      </VBtn>
                      <VBtn
                        icon variant="text" size="small"
                        @click="toggleEngagementPaiements(eng.id)"
                      >
                        <VIcon :icon="expandedEngagement === eng.id ? 'tabler-chevron-up' : 'tabler-chevron-down'" size="16" />
                        <VTooltip activator="parent">Paiements</VTooltip>
                      </VBtn>
                    </div>
                  </div>

                  <!-- Paiements drilldown -->
                  <VExpandTransition>
                    <div v-if="expandedEngagement === eng.id" class="mt-3">
                      <VDivider class="mb-3" />
                      <p class="text-caption font-weight-bold text-medium-emphasis mb-2">PAIEMENTS</p>
                      <div v-if="!engagementPaiements[eng.id]?.length" class="text-caption text-medium-emphasis">
                        Aucun paiement pour cet engagement.
                      </div>
                      <VTable v-else density="compact">
                        <thead>
                          <tr>
                            <th>Référence</th>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Mode</th>
                            <th>Statut</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="pai in engagementPaiements[eng.id]" :key="pai.id">
                            <td>{{ pai.reference }}</td>
                            <td>{{ formatDate(pai.date_paiement) }}</td>
                            <td class="font-weight-bold">{{ formatCurrencyXOF(pai.montant) }}</td>
                            <td>{{ modeLabels[pai.mode_paiement] }}</td>
                            <td>
                              <VChip :color="paiStatutConfig[pai.statut]?.color" size="x-small" label>
                                {{ paiStatutConfig[pai.statut]?.label }}
                              </VChip>
                            </td>
                          </tr>
                        </tbody>
                      </VTable>
                    </div>
                  </VExpandTransition>
                </VCardText>
              </VCard>
            </template>

            <div v-else class="text-center py-8 text-medium-emphasis">
              <VBtn color="primary" variant="tonal" prepend-icon="tabler-refresh" @click="loadFinance">
                Charger les données financières
              </VBtn>
            </div>
          </VCardText>
        </VTabsWindowItem>

      </VTabsWindow>
    </VCard>
  </div>

  <!-- ─── Dialog Mise à jour étape ─── -->
  <VDialog v-model="etapeDialog" max-width="600">
    <VCard :title="`Étape : ${etapeLabels[selectedEtape?.type_etape] ?? ''}`">
      <VCardText>
        <VRow>
          <VCol cols="12">
            <VSelect v-model="etapeForm.statut" :items="etapeStatutOptions" label="Statut *" />
          </VCol>
          <VCol cols="12" md="4">
            <VTextField v-model="etapeForm.date_prevue" label="Date prévue" type="date" />
          </VCol>
          <VCol cols="12" md="4">
            <VTextField v-model="etapeForm.date_limite" label="Date limite" type="date" />
          </VCol>
          <VCol cols="12" md="4">
            <VTextField v-model="etapeForm.date_effective" label="Date effective" type="date" />
          </VCol>
          <VCol cols="12">
            <VTextarea v-model="etapeForm.commentaire" label="Commentaire" rows="3" />
          </VCol>
          <VCol cols="12">
            <VFileInput
              v-model="etapeFile"
              label="Pièce justificative (optionnel)"
              accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.png"
              prepend-icon="tabler-paperclip"
              show-size
              clearable
            />
            <p v-if="selectedEtape?.piece_jointe" class="text-caption text-success mt-1">
              <VIcon icon="tabler-check" size="14" /> Un fichier est déjà attaché — le remplacer en sélectionnant un nouveau.
            </p>
          </VCol>
        </VRow>
      </VCardText>
      <VCardActions class="justify-end pa-4">
        <VBtn variant="tonal" @click="etapeDialog = false">Annuler</VBtn>
        <VBtn color="primary" :loading="isSavingEtape" @click="saveEtape">Enregistrer</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- ─── Dialog Rejet ─── -->
  <VDialog v-model="rejectDialog" max-width="500">
    <VCard>
      <VCardTitle class="d-flex align-center gap-2 pa-4">
        <VIcon icon="tabler-x" color="error" />
        Rejeter le contrat
      </VCardTitle>
      <VCardText>
        <p class="mb-3">Vous allez rejeter le contrat <strong>{{ contrat?.reference }}</strong>.</p>
        <VTextarea v-model="motifRejet" label="Motif du rejet *" rows="3" required />
      </VCardText>
      <VCardActions class="justify-end pa-4">
        <VBtn variant="tonal" @click="rejectDialog = false">Annuler</VBtn>
        <VBtn color="error" :disabled="!motifRejet.trim()" @click="confirmReject">Confirmer</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- ─── Dialog Engagement ─── -->
  <VDialog v-model="engagementDialog" max-width="600" persistent>
    <VCard :title="isEditingEngagement ? 'Modifier l\'engagement' : 'Nouvel engagement'">
      <VCardText>
        <VRow>
          <VCol cols="12" md="6">
            <VTextField v-model="engagementForm.numero" label="Numéro *" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField v-model="engagementForm.date_engagement" label="Date *" type="date" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField v-model="engagementForm.exercice" label="Exercice *" maxlength="4" />
          </VCol>
          <VCol cols="12" md="6">
            <VSelect
              v-model="engagementForm.compte_budget_id"
              :items="comptesBudget"
              item-title="libelle"
              item-value="id"
              label="Compte budget"
              clearable
            />
          </VCol>
          <VCol cols="12">
            <VTextField
              v-model.number="engagementForm.montant_engage"
              label="Montant engagé (XOF) *"
              type="number"
              min="1"
            />
          </VCol>
          <VCol cols="12">
            <VTextarea v-model="engagementForm.commentaire_validation" label="Commentaire" rows="2" />
          </VCol>
        </VRow>
      </VCardText>
      <VCardActions class="justify-end pa-4">
        <VBtn variant="tonal" @click="engagementDialog = false">Annuler</VBtn>
        <VBtn color="primary" :loading="isSavingEngagement" @click="saveEngagement">Enregistrer</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- ─── Dialog Rejet Engagement ─── -->
  <VDialog v-model="engagementRejectDialog" max-width="500">
    <VCard>
      <VCardTitle class="d-flex align-center gap-2 pa-4">
        <VIcon icon="tabler-x" color="error" />
        Rejeter l'engagement
      </VCardTitle>
      <VCardText>
        <p class="mb-3">Engagement : <strong>{{ selectedEngagement?.numero }}</strong></p>
        <VTextarea v-model="engagementCommentaire" label="Motif du rejet" rows="3" />
      </VCardText>
      <VCardActions class="justify-end pa-4">
        <VBtn variant="tonal" @click="engagementRejectDialog = false">Annuler</VBtn>
        <VBtn color="error" @click="confirmRejectEngagement">Confirmer</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- ─── Dialog Paiement ─── -->
  <VDialog v-model="paiementDialog" max-width="600" persistent>
    <VCard title="Nouveau paiement">
      <VCardText>
        <VRow>
          <VCol cols="12" md="6">
            <VTextField v-model="paiementForm.reference" label="Référence *" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField v-model="paiementForm.date_paiement" label="Date *" type="date" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField
              v-model.number="paiementForm.montant"
              label="Montant (XOF) *"
              type="number"
              min="1"
            />
          </VCol>
          <VCol cols="12" md="6">
            <VSelect
              v-model="paiementForm.mode_paiement"
              :items="modeOptions"
              item-title="title"
              item-value="value"
              label="Mode de paiement *"
            />
          </VCol>
          <VCol cols="12">
            <VSelect
              v-model="paiementForm.banque_id"
              :items="banques"
              item-title="libelle"
              item-value="id"
              label="Banque"
              clearable
            />
          </VCol>
          <VCol cols="12">
            <VTextarea v-model="paiementForm.observation" label="Observation" rows="2" />
          </VCol>
        </VRow>
      </VCardText>
      <VCardActions class="justify-end pa-4">
        <VBtn variant="tonal" @click="paiementDialog = false">Annuler</VBtn>
        <VBtn color="primary" :loading="isSavingPaiement" @click="savePaiement">Enregistrer</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <VSnackbar v-model="snackbar.show" :color="snackbar.color" timeout="4000" location="top right">
    {{ snackbar.text }}
  </VSnackbar>

  <!-- Dialog nouvel avenant (fiche contrat) -->
  <VDialog v-model="avenantDialog" max-width="800" scrollable persistent>
    <VCard title="Nouvel avenant">
      <VCardText>
        <AvenantForm
          v-if="contrat"
          :contrat="contrat"
          :is-editing="false"
          @submit="onAvenantFormSubmit"
          @cancel="avenantDialog = false"
        />
      </VCardText>
    </VCard>
  </VDialog>

  <VSnackbar v-model="avenantSnackbar.show" :color="avenantSnackbar.color" timeout="4000" location="top right">
    {{ avenantSnackbar.text }}
  </VSnackbar>

  <!-- Dialog nouvel ordre de service (fiche contrat) -->
  <VDialog v-model="osDialog" max-width="800" scrollable persistent>
    <VCard title="Nouvel ordre de service">
      <VCardText>
        <OrdreServiceForm
          v-if="contrat"
          :contrat="contrat"
          :is-editing="false"
          @submit="onOSFormSubmit"
          @cancel="osDialog = false"
        />
      </VCardText>
    </VCard>
  </VDialog>

  <VSnackbar v-model="osSnackbar.show" :color="osSnackbar.color" timeout="4000" location="top right">
    {{ osSnackbar.text }}
  </VSnackbar>

  <!-- Dialog nouvelle réception (fiche contrat) -->
  <VDialog v-model="receptionDialog" max-width="900" scrollable persistent>
    <VCard title="Nouvelle réception">
      <VCardText>
        <ReceptionForm
          v-if="contrat"
          :model-value="{}"
          :contrat="contrat"
          :has-provisoire-approved="hasProvisoireApprovedReception"
          :is-editing="false"
          @submit="onReceptionFormSubmit"
          @cancel="receptionDialog = false"
        />
      </VCardText>
    </VCard>
  </VDialog>

  <VSnackbar v-model="receptionSnackbar.show" :color="receptionSnackbar.color" timeout="4000" location="top right">
    {{ receptionSnackbar.text }}
  </VSnackbar>
</template>
