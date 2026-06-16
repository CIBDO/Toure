<script setup lang="ts">
import { usePaiements } from '@/composables/usePaiements'
import { paiementService } from '@/services/paiementService'
import type { Paiement } from '@/services/paiementService'
import { useAbility } from '@/plugins/casl/composables/useAbility'
import { formatCurrencyXOF } from '@/composables/useDashboard'

definePage({ meta: { title: 'Paiements', action: 'view', subject: 'Paiement' } })

const { can } = useAbility()
const { items, total, isLoading, fetch } = usePaiements()

const snackbar = ref({ show: false, text: '', color: 'success' })
const dialog = ref(false)
const deleteDialog = ref(false)
const rejectDialog = ref(false)
const isEditing = ref(false)
const isSaving = ref(false)
const selectedItem = ref<Paiement | null>(null)
const commentaireRejet = ref('')

// Filtres
const filterQ = ref('')
const filterStatut = ref('')
const filterMode = ref('')
const filterExercice = ref('')
const filterContratId = ref<number | null>(null)
const filterEngagementId = ref<number | null>(null)
const filterDateFrom = ref('')
const filterDateTo = ref('')
const page = ref(1)
const itemsPerPage = ref(10)

// Données pour les selects
const contrats = ref<any[]>([])
const engagements = ref<any[]>([])
const banques = ref<any[]>([])

const loadSelects = async () => {
  const [c, e, b] = await Promise.all([
    useApi('/contrats?itemsPerPage=-1').json(),
    useApi('/engagements?itemsPerPage=-1').json(),
    useApi('/banques?itemsPerPage=-1').json(),
  ])
  contrats.value = c.data.value?.data ?? []
  engagements.value = e.data.value?.data ?? []
  banques.value = b.data.value?.data ?? []
}

const formDefault = () => ({
  engagement_id: null as number | null,
  reference: '',
  date_paiement: '',
  montant: null as number | null,
  mode_paiement: 'virement' as Paiement['mode_paiement'],
  banque_id: null as number | null,
  observation: '',
})

const form = ref(formDefault())

const loadData = () => {
  fetch({
    q: filterQ.value || undefined,
    statut: filterStatut.value || undefined,
    mode_paiement: filterMode.value || undefined,
    exercice: filterExercice.value || undefined,
    contrat_id: filterContratId.value || undefined,
    engagement_id: filterEngagementId.value || undefined,
    date_from: filterDateFrom.value || undefined,
    date_to: filterDateTo.value || undefined,
    itemsPerPage: itemsPerPage.value,
    sortBy: 'created_at',
    sortDesc: true,
  })
}

onMounted(() => {
  loadData()
  loadSelects()
})

watch([filterQ, filterStatut, filterMode, filterExercice, filterContratId, filterEngagementId, filterDateFrom, filterDateTo, page, itemsPerPage], loadData)

const headers = [
  { title: 'Référence', key: 'reference', sortable: false },
  { title: 'Engagement', key: 'engagement', sortable: false },
  { title: 'Contrat', key: 'contrat', sortable: false },
  { title: 'Montant', key: 'montant', sortable: false },
  { title: 'Mode', key: 'mode_paiement', sortable: false },
  { title: 'Date', key: 'date_paiement', sortable: false },
  { title: 'Statut', key: 'statut', sortable: false },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
]

const statutConfig: Record<string, { label: string; color: string }> = {
  draft:     { label: 'Brouillon', color: 'default' },
  submitted: { label: 'Soumis',    color: 'info' },
  approved:  { label: 'Approuvé',  color: 'success' },
  rejected:  { label: 'Rejeté',    color: 'error' },
}

const modeLabels: Record<string, string> = {
  virement: 'Virement', cheque: 'Chèque', espece: 'Espèce', autre: 'Autre',
}

const getStatut = (s: string) => statutConfig[s] ?? { label: s, color: 'default' }
const formatDate = (d: string) => d ? new Date(d).toLocaleDateString('fr-FR') : '-'

const openCreate = () => {
  isEditing.value = false
  form.value = formDefault()
  dialog.value = true
}

const openEdit = (item: Paiement) => {
  isEditing.value = true
  selectedItem.value = item
  form.value = {
    engagement_id: item.engagement_id,
    reference: item.reference,
    date_paiement: item.date_paiement,
    montant: item.montant,
    mode_paiement: item.mode_paiement,
    banque_id: item.banque_id ?? null,
    observation: item.observation ?? '',
  }
  dialog.value = true
}

const saveForm = async () => {
  isSaving.value = true
  try {
    if (isEditing.value && selectedItem.value) {
      await paiementService.update(selectedItem.value.id, form.value as any)
      snackbar.value = { show: true, text: 'Paiement modifié avec succès', color: 'success' }
    }
    else {
      await paiementService.create(form.value as any)
      snackbar.value = { show: true, text: 'Paiement créé avec succès', color: 'success' }
    }
    dialog.value = false
    loadData()
  }
  catch (e: any) {
    const msg = e?.response?._data?.errors?.montant?.[0]
      || e?.response?._data?.message
      || e?.message
      || 'Erreur lors de l\'enregistrement'
    snackbar.value = { show: true, text: msg, color: 'error' }
  }
  finally {
    isSaving.value = false
  }
}

const doAction = async (action: 'submit' | 'approve', item: Paiement) => {
  try {
    await paiementService[action](item.id)
    const labels = { submit: 'soumis', approve: 'approuvé' }
    snackbar.value = { show: true, text: `Paiement ${labels[action]}`, color: 'success' }
    loadData()
  }
  catch (e: any) {
    snackbar.value = { show: true, text: e?.response?._data?.message || 'Erreur', color: 'error' }
  }
}

const openReject = (item: Paiement) => {
  selectedItem.value = item
  commentaireRejet.value = ''
  rejectDialog.value = true
}

const confirmReject = async () => {
  if (!selectedItem.value) return
  try {
    await paiementService.reject(selectedItem.value.id, commentaireRejet.value)
    rejectDialog.value = false
    snackbar.value = { show: true, text: 'Paiement rejeté', color: 'warning' }
    loadData()
  }
  catch (e: any) {
    snackbar.value = { show: true, text: e?.response?._data?.message || 'Erreur', color: 'error' }
  }
}

const openDelete = (item: Paiement) => {
  selectedItem.value = item
  deleteDialog.value = true
}

const confirmDelete = async () => {
  if (!selectedItem.value) return
  try {
    await paiementService.remove(selectedItem.value.id)
    deleteDialog.value = false
    snackbar.value = { show: true, text: 'Paiement supprimé', color: 'success' }
    loadData()
  }
  catch (e: any) {
    snackbar.value = { show: true, text: e?.response?._data?.message || 'Erreur', color: 'error' }
  }
}

const statutOptions = [
  { title: 'Tous les statuts', value: '' },
  { title: 'Brouillon', value: 'draft' },
  { title: 'Soumis', value: 'submitted' },
  { title: 'Approuvé', value: 'approved' },
  { title: 'Rejeté', value: 'rejected' },
]

const modeOptions = [
  { title: 'Tous les modes', value: '' },
  { title: 'Virement', value: 'virement' },
  { title: 'Chèque', value: 'cheque' },
  { title: 'Espèce', value: 'espece' },
  { title: 'Autre', value: 'autre' },
]
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardText class="pb-0">
          <div class="d-flex align-center gap-3 flex-wrap mb-4">
            <h2 class="text-h6 font-weight-bold flex-grow-1">Paiements</h2>
            <VBtn
              v-if="can('create', 'Paiement')"
              color="primary"
              prepend-icon="tabler-plus"
              @click="openCreate"
            >
              Nouveau paiement
            </VBtn>
          </div>

          <!-- Filtres -->
          <VRow dense class="mb-2">
            <VCol cols="12" sm="6" md="2">
              <VTextField
                v-model="filterQ"
                label="Référence"
                density="compact"
                prepend-inner-icon="tabler-search"
                clearable
              />
            </VCol>
            <VCol cols="12" sm="6" md="2">
              <VSelect v-model="filterStatut" :items="statutOptions" label="Statut" density="compact" clearable />
            </VCol>
            <VCol cols="12" sm="6" md="2">
              <VSelect v-model="filterMode" :items="modeOptions" label="Mode" density="compact" clearable />
            </VCol>
            <VCol cols="12" sm="6" md="2">
              <VSelect
                v-model="filterContratId"
                :items="contrats"
                item-title="reference"
                item-value="id"
                label="Contrat"
                density="compact"
                clearable
              />
            </VCol>
            <VCol cols="12" sm="6" md="2">
              <VTextField v-model="filterDateFrom" label="Du" type="date" density="compact" clearable />
            </VCol>
            <VCol cols="12" sm="6" md="2">
              <VTextField v-model="filterDateTo" label="Au" type="date" density="compact" clearable />
            </VCol>
          </VRow>
        </VCardText>

        <VDataTableServer
          :headers="headers"
          :items="items"
          :items-length="total"
          :loading="isLoading"
          v-model:items-per-page="itemsPerPage"
          v-model:page="page"
          class="text-no-wrap"
        >
          <template #item.engagement="{ item }">
            {{ item.engagement?.numero ?? '-' }}
          </template>
          <template #item.contrat="{ item }">
            {{ item.engagement?.contrat?.reference ?? '-' }}
          </template>
          <template #item.montant="{ item }">
            <span class="font-weight-bold">{{ formatCurrencyXOF(item.montant) }}</span>
          </template>
          <template #item.mode_paiement="{ item }">
            {{ modeLabels[item.mode_paiement] ?? item.mode_paiement }}
          </template>
          <template #item.date_paiement="{ item }">
            {{ formatDate(item.date_paiement) }}
          </template>
          <template #item.statut="{ item }">
            <VChip :color="getStatut(item.statut).color" size="small" label>
              {{ getStatut(item.statut).label }}
            </VChip>
          </template>
          <template #item.actions="{ item }">
            <div class="d-flex gap-1 justify-end">
              <VBtn
                v-if="can('update', 'Paiement') && item.statut === 'draft'"
                icon variant="text" size="small" color="primary"
                @click="openEdit(item)"
              >
                <VIcon icon="tabler-edit" size="18" />
                <VTooltip activator="parent">Modifier</VTooltip>
              </VBtn>
              <VBtn
                v-if="can('create', 'Paiement') && item.statut === 'draft'"
                icon variant="text" size="small" color="info"
                @click="doAction('submit', item)"
              >
                <VIcon icon="tabler-send" size="18" />
                <VTooltip activator="parent">Soumettre</VTooltip>
              </VBtn>
              <VBtn
                v-if="can('update', 'Paiement') && item.statut === 'submitted'"
                icon variant="text" size="small" color="success"
                @click="doAction('approve', item)"
              >
                <VIcon icon="tabler-check" size="18" />
                <VTooltip activator="parent">Approuver</VTooltip>
              </VBtn>
              <VBtn
                v-if="can('update', 'Paiement') && item.statut === 'submitted'"
                icon variant="text" size="small" color="error"
                @click="openReject(item)"
              >
                <VIcon icon="tabler-x" size="18" />
                <VTooltip activator="parent">Rejeter</VTooltip>
              </VBtn>
              <VBtn
                v-if="can('delete', 'Paiement') && item.statut === 'draft'"
                icon variant="text" size="small" color="error"
                @click="openDelete(item)"
              >
                <VIcon icon="tabler-trash" size="18" />
                <VTooltip activator="parent">Supprimer</VTooltip>
              </VBtn>
            </div>
          </template>
          <template #no-data>
            <div class="text-center text-medium-emphasis py-8">
              <VIcon icon="tabler-cash-off" size="48" class="mb-3 opacity-30" />
              <p>Aucun paiement trouvé</p>
            </div>
          </template>
        </VDataTableServer>
      </VCard>
    </VCol>
  </VRow>

  <!-- Dialog Créer / Modifier -->
  <VDialog v-model="dialog" max-width="640" persistent>
    <VCard :title="isEditing ? 'Modifier le paiement' : 'Nouveau paiement'">
      <VCardText>
        <VRow>
          <VCol cols="12" md="6">
            <VSelect
              v-model="form.engagement_id"
              :items="engagements"
              item-title="numero"
              item-value="id"
              label="Engagement *"
              :disabled="isEditing"
            />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField v-model="form.reference" label="Référence *" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField v-model="form.date_paiement" label="Date de paiement *" type="date" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField
              v-model.number="form.montant"
              label="Montant (XOF) *"
              type="number"
              min="1"
            />
          </VCol>
          <VCol cols="12" md="6">
            <VSelect
              v-model="form.mode_paiement"
              :items="modeOptions.filter(m => m.value)"
              item-title="title"
              item-value="value"
              label="Mode de paiement *"
            />
          </VCol>
          <VCol cols="12" md="6">
            <VSelect
              v-model="form.banque_id"
              :items="banques"
              item-title="libelle"
              item-value="id"
              label="Banque"
              clearable
            />
          </VCol>
          <VCol cols="12">
            <VTextarea v-model="form.observation" label="Observation" rows="2" />
          </VCol>
        </VRow>
      </VCardText>
      <VCardActions class="justify-end pa-4">
        <VBtn variant="tonal" @click="dialog = false">Annuler</VBtn>
        <VBtn color="primary" :loading="isSaving" @click="saveForm">Enregistrer</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Dialog Rejet -->
  <VDialog v-model="rejectDialog" max-width="500">
    <VCard>
      <VCardTitle class="d-flex align-center gap-2 pa-4">
        <VIcon icon="tabler-x" color="error" />
        Rejeter le paiement
      </VCardTitle>
      <VCardText>
        <p class="mb-3">Paiement : <strong>{{ selectedItem?.reference }}</strong></p>
        <VTextarea v-model="commentaireRejet" label="Motif du rejet" rows="3" />
      </VCardText>
      <VCardActions class="justify-end pa-4">
        <VBtn variant="tonal" @click="rejectDialog = false">Annuler</VBtn>
        <VBtn color="error" @click="confirmReject">Confirmer</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Dialog Suppression -->
  <VDialog v-model="deleteDialog" max-width="420">
    <VCard>
      <VCardTitle class="d-flex align-center gap-2 pa-4">
        <VIcon icon="tabler-alert-triangle" color="error" />
        Confirmer la suppression
      </VCardTitle>
      <VCardText>
        Supprimer le paiement <strong>{{ selectedItem?.reference }}</strong> ? Cette action est irréversible.
      </VCardText>
      <VCardActions class="justify-end pa-4">
        <VBtn variant="tonal" @click="deleteDialog = false">Annuler</VBtn>
        <VBtn color="error" @click="confirmDelete">Supprimer</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <VSnackbar v-model="snackbar.show" :color="snackbar.color" timeout="4000" location="top right">
    {{ snackbar.text }}
  </VSnackbar>
</template>
