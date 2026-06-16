<script setup lang="ts">
import { useEngagements } from '@/composables/useEngagements'
import { engagementService } from '@/services/engagementService'
import type { Engagement } from '@/services/engagementService'
import { useAbility } from '@/plugins/casl/composables/useAbility'
import { formatCurrencyXOF } from '@/composables/useDashboard'

definePage({ meta: { title: 'Engagements', action: 'view', subject: 'Engagement' } })

const { can } = useAbility()
const { items, total, isLoading, fetch } = useEngagements()

const snackbar = ref({ show: false, text: '', color: 'success' })
const dialog = ref(false)
const deleteDialog = ref(false)
const rejectDialog = ref(false)
const isEditing = ref(false)
const isSaving = ref(false)
const selectedItem = ref<Engagement | null>(null)
const commentaireRejet = ref('')

// Filtres
const filterQ = ref('')
const filterStatut = ref('')
const filterExercice = ref('')
const filterContratId = ref<number | null>(null)
const filterDateFrom = ref('')
const filterDateTo = ref('')
const page = ref(1)
const itemsPerPage = ref(10)

// Contrats pour le select
const contrats = ref<any[]>([])
const loadContrats = async () => {
  const { data } = await useApi('/contrats?itemsPerPage=-1').json()
  contrats.value = data.value?.data ?? []
}

const formDefault = () => ({
  contrat_id: null as number | null,
  numero: '',
  date_engagement: '',
  exercice: new Date().getFullYear().toString(),
  compte_budget_id: null as number | null,
  montant_engage: null as number | null,
  commentaire_validation: '',
})

const form = ref(formDefault())

// Comptes budget pour le select
const comptesBudget = ref<any[]>([])
const loadComptesBudget = async () => {
  const { data } = await useApi('/comptes-budget?itemsPerPage=-1').json()
  comptesBudget.value = data.value?.data ?? []
}

const loadData = () => {
  fetch({
    q: filterQ.value || undefined,
    statut: filterStatut.value || undefined,
    exercice: filterExercice.value || undefined,
    contrat_id: filterContratId.value || undefined,
    date_from: filterDateFrom.value || undefined,
    date_to: filterDateTo.value || undefined,
    itemsPerPage: itemsPerPage.value,
    sortBy: 'created_at',
    sortDesc: true,
  })
}

onMounted(() => {
  loadData()
  loadContrats()
  loadComptesBudget()
})

watch([filterQ, filterStatut, filterExercice, filterContratId, filterDateFrom, filterDateTo, page, itemsPerPage], loadData)

const headers = [
  { title: 'Numéro', key: 'numero', sortable: false },
  { title: 'Contrat', key: 'contrat', sortable: false },
  { title: 'Montant engagé', key: 'montant_engage', sortable: false },
  { title: 'Exercice', key: 'exercice', sortable: false },
  { title: 'Date', key: 'date_engagement', sortable: false },
  { title: 'Statut', key: 'statut', sortable: false },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
]

const statutConfig: Record<string, { label: string; color: string }> = {
  draft:     { label: 'Brouillon', color: 'default' },
  submitted: { label: 'Soumis',    color: 'info' },
  approved:  { label: 'Approuvé',  color: 'success' },
  rejected:  { label: 'Rejeté',    color: 'error' },
  archived:  { label: 'Archivé',   color: 'secondary' },
}

const getStatut = (s: string) => statutConfig[s] ?? { label: s, color: 'default' }
const formatDate = (d: string) => d ? new Date(d).toLocaleDateString('fr-FR') : '-'

const openCreate = () => {
  isEditing.value = false
  form.value = formDefault()
  dialog.value = true
}

const openEdit = (item: Engagement) => {
  isEditing.value = true
  selectedItem.value = item
  form.value = {
    contrat_id: item.contrat_id,
    numero: item.numero,
    date_engagement: item.date_engagement,
    exercice: item.exercice,
    compte_budget_id: item.compte_budget_id ?? null,
    montant_engage: item.montant_engage,
    commentaire_validation: item.commentaire_validation ?? '',
  }
  dialog.value = true
}

const saveForm = async () => {
  isSaving.value = true
  try {
    if (isEditing.value && selectedItem.value) {
      await engagementService.update(selectedItem.value.id, form.value as any)
      snackbar.value = { show: true, text: 'Engagement modifié avec succès', color: 'success' }
    }
    else {
      await engagementService.create(form.value as any)
      snackbar.value = { show: true, text: 'Engagement créé avec succès', color: 'success' }
    }
    dialog.value = false
    loadData()
  }
  catch (e: any) {
    const msg = e?.response?._data?.message || e?.message || 'Erreur lors de l\'enregistrement'
    snackbar.value = { show: true, text: msg, color: 'error' }
  }
  finally {
    isSaving.value = false
  }
}

const doAction = async (action: 'submit' | 'approve', item: Engagement) => {
  try {
    await engagementService[action](item.id)
    const labels = { submit: 'soumis', approve: 'approuvé' }
    snackbar.value = { show: true, text: `Engagement ${labels[action]}`, color: 'success' }
    loadData()
  }
  catch (e: any) {
    snackbar.value = { show: true, text: e?.response?._data?.message || 'Erreur', color: 'error' }
  }
}

const openReject = (item: Engagement) => {
  selectedItem.value = item
  commentaireRejet.value = ''
  rejectDialog.value = true
}

const confirmReject = async () => {
  if (!selectedItem.value) return
  try {
    await engagementService.reject(selectedItem.value.id, commentaireRejet.value)
    rejectDialog.value = false
    snackbar.value = { show: true, text: 'Engagement rejeté', color: 'warning' }
    loadData()
  }
  catch (e: any) {
    snackbar.value = { show: true, text: e?.response?._data?.message || 'Erreur', color: 'error' }
  }
}

const openDelete = (item: Engagement) => {
  selectedItem.value = item
  deleteDialog.value = true
}

const confirmDelete = async () => {
  if (!selectedItem.value) return
  try {
    await engagementService.remove(selectedItem.value.id)
    deleteDialog.value = false
    snackbar.value = { show: true, text: 'Engagement supprimé', color: 'success' }
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
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardText class="pb-0">
          <div class="d-flex align-center gap-3 flex-wrap mb-4">
            <h2 class="text-h6 font-weight-bold flex-grow-1">Engagements budgétaires</h2>
            <VBtn
              v-if="can('create', 'Engagement')"
              color="primary"
              prepend-icon="tabler-plus"
              @click="openCreate"
            >
              Nouvel engagement
            </VBtn>
          </div>

          <!-- Filtres -->
          <VRow dense class="mb-2">
            <VCol cols="12" sm="6" md="3">
              <VTextField
                v-model="filterQ"
                label="Rechercher (numéro)"
                density="compact"
                prepend-inner-icon="tabler-search"
                clearable
              />
            </VCol>
            <VCol cols="12" sm="6" md="2">
              <VSelect
                v-model="filterStatut"
                :items="statutOptions"
                label="Statut"
                density="compact"
                clearable
              />
            </VCol>
            <VCol cols="12" sm="6" md="2">
              <VTextField
                v-model="filterExercice"
                label="Exercice"
                density="compact"
                clearable
              />
            </VCol>
            <VCol cols="12" sm="6" md="3">
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
            <VCol cols="12" sm="6" md="1">
              <VTextField v-model="filterDateFrom" label="Du" type="date" density="compact" clearable />
            </VCol>
            <VCol cols="12" sm="6" md="1">
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
          <template #item.contrat="{ item }">
            {{ item.contrat?.reference ?? '-' }}
          </template>
          <template #item.montant_engage="{ item }">
            <span class="font-weight-bold">{{ formatCurrencyXOF(item.montant_engage) }}</span>
          </template>
          <template #item.date_engagement="{ item }">
            {{ formatDate(item.date_engagement) }}
          </template>
          <template #item.statut="{ item }">
            <VChip :color="getStatut(item.statut).color" size="small" label>
              {{ getStatut(item.statut).label }}
            </VChip>
          </template>
          <template #item.actions="{ item }">
            <div class="d-flex gap-1 justify-end">
              <VBtn
                v-if="can('update', 'Engagement') && item.statut === 'draft'"
                icon variant="text" size="small" color="primary"
                @click="openEdit(item)"
              >
                <VIcon icon="tabler-edit" size="18" />
                <VTooltip activator="parent">Modifier</VTooltip>
              </VBtn>
              <VBtn
                v-if="can('create', 'Engagement') && item.statut === 'draft'"
                icon variant="text" size="small" color="info"
                @click="doAction('submit', item)"
              >
                <VIcon icon="tabler-send" size="18" />
                <VTooltip activator="parent">Soumettre</VTooltip>
              </VBtn>
              <VBtn
                v-if="can('update', 'Engagement') && item.statut === 'submitted'"
                icon variant="text" size="small" color="success"
                @click="doAction('approve', item)"
              >
                <VIcon icon="tabler-check" size="18" />
                <VTooltip activator="parent">Approuver</VTooltip>
              </VBtn>
              <VBtn
                v-if="can('update', 'Engagement') && item.statut === 'submitted'"
                icon variant="text" size="small" color="error"
                @click="openReject(item)"
              >
                <VIcon icon="tabler-x" size="18" />
                <VTooltip activator="parent">Rejeter</VTooltip>
              </VBtn>
              <VBtn
                v-if="can('delete', 'Engagement') && item.statut === 'draft'"
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
              <VIcon icon="tabler-receipt-off" size="48" class="mb-3 opacity-30" />
              <p>Aucun engagement trouvé</p>
            </div>
          </template>
        </VDataTableServer>
      </VCard>
    </VCol>
  </VRow>

  <!-- Dialog Créer / Modifier -->
  <VDialog v-model="dialog" max-width="640" persistent>
    <VCard :title="isEditing ? 'Modifier l\'engagement' : 'Nouvel engagement'">
      <VCardText>
        <VRow>
          <VCol cols="12" md="6">
            <VSelect
              v-model="form.contrat_id"
              :items="contrats"
              item-title="reference"
              item-value="id"
              label="Contrat *"
              :disabled="isEditing"
            />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField v-model="form.numero" label="Numéro *" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField v-model="form.date_engagement" label="Date d'engagement *" type="date" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField v-model="form.exercice" label="Exercice *" maxlength="4" />
          </VCol>
          <VCol cols="12" md="6">
            <VSelect
              v-model="form.compte_budget_id"
              :items="comptesBudget"
              item-title="libelle"
              item-value="id"
              label="Compte budget"
              clearable
            />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField
              v-model.number="form.montant_engage"
              label="Montant engagé (XOF) *"
              type="number"
              min="1"
            />
          </VCol>
          <VCol cols="12">
            <VTextarea v-model="form.commentaire_validation" label="Commentaire" rows="2" />
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
        Rejeter l'engagement
      </VCardTitle>
      <VCardText>
        <p class="mb-3">Engagement : <strong>{{ selectedItem?.numero }}</strong></p>
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
        Supprimer l'engagement <strong>{{ selectedItem?.numero }}</strong> ? Cette action est irréversible.
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
