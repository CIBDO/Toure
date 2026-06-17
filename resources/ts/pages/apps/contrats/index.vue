<script setup lang="ts">
import { useContratsStore } from '@/stores/contrats'
import { useApi } from '@/composables/useApi'

definePage({ meta: { title: 'Contrats' } })

const store = useContratsStore()
const router = useRouter()
const snackbar = ref({ show: false, text: '', color: 'success' })
const dialog = ref(false)
const deleteDialog = ref(false)
const rejectDialog = ref(false)
const isEditing = ref(false)
const selectedItem = ref<any>(null)
const motifRejet = ref('')

// Filtres
const searchQuery = ref('')
const filterStatut = ref('')
const filterExercice = ref('')
const filterFournisseur = ref<number | null>(null)
const filterDateFrom = ref('')
const filterDateTo = ref('')
const showDateFilters = ref(false)

const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref([{ key: 'created_at', order: 'desc' }])

const modeOptions = [
  { title: 'AO Ouvert', value: 'AO_OUVERT' },
  { title: 'AO Restreint', value: 'AO_RESTREINT' },
  { title: 'Consultation', value: 'CONSULTATION' },
  { title: 'Gré à gré', value: 'GRE_A_GRE' },
  { title: 'Entente directe', value: 'ENTENTE_DIRECTE' },
]

const statutOptions = [
  { title: 'Brouillon', value: 'draft' },
  { title: 'Soumis', value: 'submitted' },
  { title: 'Approuvé', value: 'approved' },
  { title: 'Rejeté', value: 'rejected' },
  { title: 'Archivé', value: 'archived' },
]

const statutColor = (s: string) => ({
  draft: 'default', submitted: 'info', approved: 'success', rejected: 'error', archived: 'secondary',
}[s] || 'default')

const statutLabel = (s: string) => ({
  draft: 'Brouillon', submitted: 'Soumis', approved: 'Approuvé', rejected: 'Rejeté', archived: 'Archivé',
}[s] || s)

const headers = [
  { title: 'Référence', key: 'reference', sortable: true },
  { title: 'Objet', key: 'objet', sortable: true },
  { title: 'Titulaire', key: 'fournisseur', sortable: false },
  { title: 'Montant', key: 'montant_initial', sortable: true },
  { title: 'Exercice', key: 'exercice', sortable: true },
  { title: 'Date signature', key: 'date_signature', sortable: true },
  { title: 'Date prév. réception', key: 'date_previsionnelle_reception', sortable: true },
  { title: 'Statut', key: 'statut', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, width: '220px' },
]

const emptyForm = () => ({
  reference: '', numero: '', objet: '',
  pv_id: null as number | null,
  avis_id: null as number | null,
  fournisseur_id: null as number | null,
  compte_budget_id: null as number | null,
  agent_id: null as number | null,
  montant_initial: 0, devise: 'CFA',
  date_signature: '', date_debut: '', date_fin: '', date_previsionnelle_reception: '',
  duree_execution: null as number | null,
  mode_passation: '', exercice: new Date().getFullYear().toString(),
  statut: 'draft', observations: '',
})

const form = ref(emptyForm())

// Listes de référence
const { data: fournisseursData } = await useApi<any>('/fournisseurs?itemsPerPage=-1').json()
const fournisseursList = computed(() =>
  fournisseursData.value?.data?.map((f: any) => ({ title: f.raison_sociale, value: f.id })) ?? [],
)

const { data: comptesData } = await useApi<any>('/comptes-budget?itemsPerPage=-1').json()
const comptesList = computed(() =>
  comptesData.value?.data?.map((c: any) => ({ title: `${c.code} — ${c.libelle}`, value: c.id })) ?? [],
)

const { data: avisData } = await useApi<any>('/avis?itemsPerPage=-1').json()
const avisList = computed(() =>
  avisData.value?.data?.map((a: any) => ({ title: `${a.reference} — ${a.objet?.substring(0, 50)}`, value: a.id })) ?? [],
)

const { data: pvsData } = await useApi<any>('/pvs?itemsPerPage=-1').json()
const pvsList = computed(() =>
  pvsData.value?.data?.map((p: any) => ({ title: p.reference, value: p.id })) ?? [],
)

const { data: usersData } = await useApi<any>('/users?itemsPerPage=-1').json()
const usersList = computed(() =>
  usersData.value?.users?.map((u: any) => ({ title: `${u.prenom} ${u.nom}`, value: u.id })) ?? [],
)

const loadData = async () => {
  await store.fetchContrats({
    q: searchQuery.value,
    statut: filterStatut.value,
    exercice: filterExercice.value,
    fournisseur_id: filterFournisseur.value ?? '',
    date_from: filterDateFrom.value,
    date_to: filterDateTo.value,
    itemsPerPage: itemsPerPage.value,
    page: page.value,
    sortBy: sortBy.value[0]?.key,
    sortDesc: sortBy.value[0]?.order === 'desc',
  })
}

watch(
  [searchQuery, filterStatut, filterExercice, filterFournisseur, filterDateFrom, filterDateTo, itemsPerPage, page, sortBy],
  loadData,
  { deep: true },
)
onMounted(loadData)

const openCreate = () => {
  isEditing.value = false
  form.value = emptyForm()
  dialog.value = true
}

const openEdit = async (item: any) => {
  isEditing.value = true
  selectedItem.value = item
  const full = await store.fetchContrat(item.id)
  form.value = {
    ...emptyForm(),
    ...full,
    date_signature: normalizeDateField(full.date_signature),
    date_debut: normalizeDateField(full.date_debut),
    date_fin: normalizeDateField(full.date_fin),
    date_previsionnelle_reception: normalizeDateField(full.date_previsionnelle_reception),
  }
  dialog.value = true
}

const viewContrat = (item: any) => router.push({ name: 'apps-contrats-id', params: { id: item.id } })

const openDelete = (item: any) => {
  selectedItem.value = item
  deleteDialog.value = true
}

const openReject = (item: any) => {
  selectedItem.value = item
  motifRejet.value = ''
  rejectDialog.value = true
}

const save = async () => {
  try {
    if (isEditing.value)
      await store.updateContrat(selectedItem.value.id, form.value)
    else
      await store.createContrat(form.value)
    dialog.value = false
    snackbar.value = { show: true, text: `Contrat ${isEditing.value ? 'modifié' : 'créé'} avec succès`, color: 'success' }
    await loadData()
  }
  catch (e: any) {
    snackbar.value = { show: true, text: e?.data?.message || 'Une erreur est survenue', color: 'error' }
  }
}

const confirmDelete = async () => {
  try {
    await store.deleteContrat(selectedItem.value.id)
    deleteDialog.value = false
    snackbar.value = { show: true, text: 'Contrat supprimé avec succès', color: 'success' }
    await loadData()
  }
  catch {
    snackbar.value = { show: true, text: 'Impossible de supprimer ce contrat', color: 'error' }
  }
}

const doAction = async (action: 'submitContrat' | 'approveContrat' | 'archiveContrat', item: any) => {
  try {
    await store[action](item.id)
    const labels = { submitContrat: 'soumis', approveContrat: 'approuvé', archiveContrat: 'archivé' }
    snackbar.value = { show: true, text: `Contrat ${labels[action]} avec succès`, color: 'success' }
    await loadData()
  }
  catch {
    snackbar.value = { show: true, text: 'Erreur lors de l\'action', color: 'error' }
  }
}

const confirmReject = async () => {
  try {
    await store.rejectContrat(selectedItem.value.id, { motif_rejet: motifRejet.value })
    rejectDialog.value = false
    snackbar.value = { show: true, text: 'Contrat rejeté', color: 'warning' }
    await loadData()
  }
  catch {
    snackbar.value = { show: true, text: 'Erreur lors du rejet', color: 'error' }
  }
}

const formatMontant = (v: number) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v)

const formatDate = (d?: string | null) => {
  if (!d) return '-'
  const normalized = d.slice(0, 10)
  const [year, month, day] = normalized.split('-')
  if (year && month && day)
    return `${day}/${month}/${year}`
  return new Date(d).toLocaleDateString('fr-FR')
}

const normalizeDateField = (d?: string | null) => (d ? d.slice(0, 10) : '')
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardTitle class="d-flex align-center pa-4">
          <VIcon icon="tabler-file-contract" class="me-2" />
          Gestion des Contrats
          <VSpacer />
          <VBtn prepend-icon="tabler-plus" color="primary" @click="openCreate">Nouveau Contrat</VBtn>
        </VCardTitle>

        <VDivider />

        <VCardText>
          <!-- Filtres -->
          <VRow class="mb-2">
            <VCol cols="12" md="3">
              <VTextField
                v-model="searchQuery"
                placeholder="Référence, objet..."
                prepend-inner-icon="tabler-search"
                density="compact"
                clearable
              />
            </VCol>
            <VCol cols="12" md="2">
              <VSelect
                v-model="filterStatut"
                :items="[{ title: 'Tous statuts', value: '' }, ...statutOptions]"
                label="Statut"
                density="compact"
                clearable
              />
            </VCol>
            <VCol cols="12" md="2">
              <VTextField v-model="filterExercice" placeholder="Exercice" density="compact" clearable />
            </VCol>
            <VCol cols="12" md="3">
              <VAutocomplete
                v-model="filterFournisseur"
                :items="fournisseursList"
                label="Titulaire"
                density="compact"
                clearable
              />
            </VCol>
            <VCol cols="12" md="2" class="d-flex align-center">
              <VBtn
                variant="tonal"
                :color="showDateFilters ? 'primary' : 'default'"
                prepend-icon="tabler-calendar-search"
                size="small"
                @click="showDateFilters = !showDateFilters"
              >
                Dates
              </VBtn>
            </VCol>
          </VRow>

          <VExpandTransition>
            <VRow v-if="showDateFilters" class="mb-3">
              <VCol cols="12">
                <VCard variant="tonal" color="info" class="pa-3">
                  <p class="text-caption text-medium-emphasis mb-2">Filtrer par date de signature</p>
                  <VRow>
                    <VCol cols="12" md="6">
                      <VTextField v-model="filterDateFrom" label="Du" type="date" density="compact" />
                    </VCol>
                    <VCol cols="12" md="6">
                      <VTextField v-model="filterDateTo" label="Au" type="date" density="compact" />
                    </VCol>
                  </VRow>
                </VCard>
              </VCol>
            </VRow>
          </VExpandTransition>

          <VDataTableServer
            v-model:items-per-page="itemsPerPage"
            v-model:page="page"
            v-model:sort-by="sortBy"
            :headers="headers"
            :items="store.contrats"
            :items-length="store.total"
            :loading="store.isLoading"
            class="elevation-0"
          >
            <template #item.objet="{ item }">
              <span class="text-truncate d-inline-block" style="max-width:200px" :title="item.objet">{{ item.objet }}</span>
            </template>
            <template #item.fournisseur="{ item }">
              <span class="text-caption">{{ item.fournisseur?.raison_sociale ?? '-' }}</span>
            </template>
            <template #item.montant_initial="{ item }">
              <span class="font-weight-bold text-primary">{{ formatMontant(item.montant_initial) }}</span>
            </template>
            <template #item.date_signature="{ item }">
              <span class="text-caption text-no-wrap">{{ formatDate(item.date_signature) }}</span>
            </template>
            <template #item.date_previsionnelle_reception="{ item }">
              <span class="text-caption text-no-wrap">{{ formatDate(item.date_previsionnelle_reception) }}</span>
            </template>
            <template #item.statut="{ item }">
              <VChip :color="statutColor(item.statut)" size="small">{{ statutLabel(item.statut) }}</VChip>
            </template>
            <template #item.actions="{ item }">
              <div class="d-flex align-center justify-start flex-nowrap gap-0">
              <!-- Voir détails -->
              <VBtn icon variant="text" size="small" color="secondary" @click="viewContrat(item)">
                <VIcon icon="tabler-eye" />
                <VTooltip activator="parent">Voir détails</VTooltip>
              </VBtn>

              <!-- Éditer -->
              <VBtn icon variant="text" size="small" color="primary" @click="openEdit(item)">
                <VIcon icon="tabler-edit" />
                <VTooltip activator="parent">Modifier</VTooltip>
              </VBtn>

              <!-- Soumettre -->
              <VBtn v-if="item.statut === 'draft'" icon variant="text" size="small" color="info" @click="doAction('submitContrat', item)">
                <VIcon icon="tabler-send" />
                <VTooltip activator="parent">Soumettre</VTooltip>
              </VBtn>

              <!-- Approuver -->
              <VBtn v-if="item.statut === 'submitted'" icon variant="text" size="small" color="success" @click="doAction('approveContrat', item)">
                <VIcon icon="tabler-check" />
                <VTooltip activator="parent">Approuver</VTooltip>
              </VBtn>

              <!-- Rejeter -->
              <VBtn v-if="item.statut === 'submitted'" icon variant="text" size="small" color="error" @click="openReject(item)">
                <VIcon icon="tabler-x" />
                <VTooltip activator="parent">Rejeter</VTooltip>
              </VBtn>

              <!-- Archiver -->
              <VBtn v-if="item.statut === 'approved'" icon variant="text" size="small" color="secondary" @click="doAction('archiveContrat', item)">
                <VIcon icon="tabler-archive" />
                <VTooltip activator="parent">Archiver</VTooltip>
              </VBtn>

              <!-- Supprimer -->
              <VBtn icon variant="text" size="small" color="error" @click="openDelete(item)">
                <VIcon icon="tabler-trash" />
                <VTooltip activator="parent">Supprimer</VTooltip>
              </VBtn>
              </div>
            </template>
          </VDataTableServer>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <!-- ─── Dialog Création / Édition ─── -->
  <VDialog v-model="dialog" max-width="950" scrollable>
    <VCard :title="isEditing ? 'Modifier le contrat' : 'Nouveau contrat'">
      <VCardText>
        <VRow>
          <VCol cols="12" md="4">
            <VTextField v-model="form.reference" label="Référence interne *" placeholder="CTR-2026-001" />
          </VCol>
          <VCol cols="12" md="4">
            <VTextField v-model="form.numero" label="Numéro officiel" placeholder="N°2026/CANAM/001" />
          </VCol>
          <VCol cols="12" md="4">
            <VTextField v-model="form.exercice" label="Exercice *" placeholder="2026" />
          </VCol>
          <VCol cols="12">
            <VTextField v-model="form.objet" label="Objet du contrat *" />
          </VCol>
          <VCol cols="12" md="6">
            <VAutocomplete v-model="form.fournisseur_id" :items="fournisseursList" label="Titulaire (Fournisseur) *" />
          </VCol>
          <VCol cols="12" md="6">
            <VSelect v-model="form.compte_budget_id" :items="comptesList" label="Compte budget" clearable />
          </VCol>
          <VCol cols="12" md="6">
            <VSelect v-model="form.avis_id" :items="avisList" label="Avis lié" clearable />
          </VCol>
          <VCol cols="12" md="6">
            <VSelect v-model="form.pv_id" :items="pvsList" label="PV d'attribution" clearable />
          </VCol>
          <VCol cols="12" md="6">
            <VSelect v-model="form.agent_id" :items="usersList" label="Agent responsable" clearable />
          </VCol>
          <VCol cols="12" md="3">
            <VTextField v-model.number="form.montant_initial" label="Montant initial (CFA) *" type="number" min="0" />
          </VCol>
          <VCol cols="12" md="3">
            <VTextField v-model="form.devise" label="Devise" />
          </VCol>
          <VCol cols="12" md="4">
            <VSelect v-model="form.mode_passation" :items="modeOptions" label="Mode de passation" clearable />
          </VCol>
          <VCol cols="12" md="4">
            <VTextField v-model="form.date_signature" label="Date de signature" type="date" />
          </VCol>
          <VCol cols="12" md="4">
            <VTextField v-model.number="form.duree_execution" label="Durée d'exécution (jours)" type="number" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField v-model="form.date_debut" label="Date de début" type="date" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField v-model="form.date_fin" label="Date de fin" type="date" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField v-model="form.date_previsionnelle_reception" label="Date prévisionnelle de réception" type="date" />
          </VCol>
          <VCol cols="12">
            <VTextarea v-model="form.observations" label="Observations" rows="3" />
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />
      <VCardActions class="justify-end pa-4">
        <VBtn variant="tonal" @click="dialog = false">Annuler</VBtn>
        <VBtn color="primary" prepend-icon="tabler-device-floppy" @click="save">
          {{ isEditing ? 'Enregistrer' : 'Créer le contrat' }}
        </VBtn>
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
        <p class="mb-3">Vous allez rejeter le contrat <strong>{{ selectedItem?.reference }}</strong>.</p>
        <VTextarea v-model="motifRejet" label="Motif du rejet *" rows="3" required />
      </VCardText>
      <VCardActions class="justify-end pa-4">
        <VBtn variant="tonal" @click="rejectDialog = false">Annuler</VBtn>
        <VBtn color="error" :disabled="!motifRejet.trim()" @click="confirmReject">Confirmer</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- ─── Dialog Suppression ─── -->
  <VDialog v-model="deleteDialog" max-width="420">
    <VCard>
      <VCardTitle class="d-flex align-center gap-2 pa-4">
        <VIcon icon="tabler-alert-triangle" color="error" />
        Confirmer la suppression
      </VCardTitle>
      <VCardText>
        Voulez-vous vraiment supprimer le contrat <strong>{{ selectedItem?.reference }}</strong> ?
        <br />
        <span class="text-caption text-medium-emphasis">Les étapes de suivi seront également supprimées.</span>
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
