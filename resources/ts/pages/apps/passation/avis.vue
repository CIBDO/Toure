<script setup lang="ts">
import { useAvisStore } from '@/stores/avis'
import { useFournisseursStore } from '@/stores/fournisseurs'

const formatDate = (v: string | null | undefined) => {
  if (!v) return '-'
  const d = new Date(v)
  if (isNaN(d.getTime())) return v
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

definePage({ meta: { title: 'Avis de Passation' } })

const store = useAvisStore()
const fournisseursStore = useFournisseursStore()
const snackbar = ref({ show: false, text: '', color: 'success' })
const dialog = ref(false)
const deleteDialog = ref(false)
const rejectDialog = ref(false)
const isEditing = ref(false)
const selectedItem = ref<any>(null)
const motifRejet = ref('')
const activeTab = ref('general')
const formRef = ref<any>(null)

const requiredRule = (v: string) => !!v?.trim() || 'Ce champ est obligatoire'
const dureeRule = (v: number | null) => (v != null && v > 0) || 'Ce champ est obligatoire'

// Filtres
const searchQuery = ref('')
const filterStatut = ref('')
const filterExercice = ref('')
const filterMode = ref('')
const filterDateLimiteFrom = ref('')
const filterDateLimiteTo = ref('')
const filterDateOuvertureFrom = ref('')
const filterDateOuvertureTo = ref('')
const showDateFilters = ref(false)

const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref([{ key: 'created_at', order: 'desc' }])

const modeOptions = [
  { title: 'Appel d\'offres ouvert', value: 'AO_OUVERT' },
  { title: 'Appel d\'offres restreint', value: 'AO_RESTREINT' },
  { title: 'Consultation', value: 'CONSULTATION' },
  { title: 'Gré à gré', value: 'GRE_A_GRE' },
  { title: 'Entente directe', value: 'ENTENTE_DIRECTE' },
]

const statutOptions = [
  { title: 'Brouillon', value: 'draft' },
  { title: 'Soumis', value: 'submitted' },
  { title: 'Approuvé', value: 'approved' },
  { title: 'Rejeté', value: 'rejected' },
  { title: 'Publié', value: 'published' },
  { title: 'Clos', value: 'closed' },
  { title: 'Annulé', value: 'cancelled' },
]

const statutColor = (s: string) => ({
  draft: 'default', submitted: 'info', approved: 'success',
  rejected: 'error', published: 'primary', closed: 'secondary', cancelled: 'warning',
}[s] || 'default')

const statutLabel = (s: string) => ({
  draft: 'Brouillon', submitted: 'Soumis', approved: 'Approuvé',
  rejected: 'Rejeté', published: 'Publié', closed: 'Clos', cancelled: 'Annulé',
}[s] || s)

const detailsDialog = ref(false)
const detailsItem = ref<any>(null)

const openDetails = async (item: any) => {
  detailsItem.value = await store.fetchAvisById(item.id)
  detailsDialog.value = true
}

const headers = [
  { title: 'Référence', key: 'reference', sortable: true },
  { title: 'Objet', key: 'objet', sortable: true },
  { title: 'Mode', key: 'mode_passation', sortable: true },
  { title: 'Exercice', key: 'exercice', sortable: true },
  { title: 'Date limite', key: 'date_limite_depot', sortable: true },
  { title: 'Date ouverture', key: 'date_ouverture_plis', sortable: true },
  { title: 'Statut', key: 'statut', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, width: '200px' },
]

const emptyForm = () => ({
  reference: '', objet: '', mode_passation: 'AO_OUVERT', article_pour: '',
  article_relatif: '', exercice: new Date().getFullYear().toString(),
  duree: null as number | null, delai: null as number | null,
  date_limite_depot: '', date_ouverture_plis: '', date_publication: '',
  statut: 'draft', observations: '',
  fournisseurs: [] as number[],
  items: [] as any[],
})

const form = ref(emptyForm())

const loadData = async () => {
  await store.fetchAvis({
    q: searchQuery.value,
    statut: filterStatut.value,
    exercice: filterExercice.value,
    mode_passation: filterMode.value,
    date_limite_from: filterDateLimiteFrom.value,
    date_limite_to: filterDateLimiteTo.value,
    date_ouverture_from: filterDateOuvertureFrom.value,
    date_ouverture_to: filterDateOuvertureTo.value,
    itemsPerPage: itemsPerPage.value,
    page: page.value,
    sortBy: sortBy.value[0]?.key,
    sortDesc: sortBy.value[0]?.order === 'desc',
  })
}

onMounted(async () => {
  await Promise.all([
    loadData(),
    fournisseursStore.fetchFournisseurs({ itemsPerPage: -1, statut: 'actif' }),
  ])
})

watch(
  [searchQuery, filterStatut, filterExercice, filterMode,
    filterDateLimiteFrom, filterDateLimiteTo, filterDateOuvertureFrom, filterDateOuvertureTo,
    itemsPerPage, page, sortBy],
  loadData,
  { deep: true },
)

const openCreate = () => {
  isEditing.value = false
  form.value = emptyForm()
  activeTab.value = 'general'
  dialog.value = true
}

const openEdit = async (item: any) => {
  isEditing.value = true
  selectedItem.value = item
  activeTab.value = 'general'
  const full = await store.fetchAvisById(item.id)
  form.value = {
    ...emptyForm(),
    ...full,
    fournisseurs: full.fournisseurs?.map((f: any) => f.id) ?? [],
    items: (full.items ?? []).map(({ delai: _delai, ...item }: any) => item),
  }
  dialog.value = true
}

const openDelete = (item: any) => {
  selectedItem.value = item
  deleteDialog.value = true
}

const openReject = (item: any) => {
  selectedItem.value = item
  motifRejet.value = ''
  rejectDialog.value = true
}

const addItem = () => {
  form.value.items.push({ designation: '', description_detaillee: '', quantite: 1, unite: '', lieu: '' })
}

const removeItem = (index: number) => {
  form.value.items.splice(index, 1)
}

const save = async () => {
  const { valid } = await formRef.value?.validate()
  if (!valid) {
    activeTab.value = 'general'
    snackbar.value = { show: true, text: 'Veuillez renseigner la durée de la consultation et les dates obligatoires', color: 'error' }
    return
  }

  try {
    if (isEditing.value)
      await store.updateAvis(selectedItem.value.id, form.value)
    else
      await store.createAvis(form.value)
    dialog.value = false
    snackbar.value = { show: true, text: `Avis ${isEditing.value ? 'modifié' : 'créé'} avec succès`, color: 'success' }
    await loadData()
  }
  catch (e: any) {
    snackbar.value = { show: true, text: e?.data?.message || 'Une erreur est survenue', color: 'error' }
  }
}

const confirmDelete = async () => {
  try {
    await store.deleteAvis(selectedItem.value.id)
    deleteDialog.value = false
    snackbar.value = { show: true, text: 'Avis supprimé avec succès', color: 'success' }
    await loadData()
  }
  catch {
    snackbar.value = { show: true, text: 'Impossible de supprimer cet avis', color: 'error' }
  }
}

const doAction = async (action: string, item: any, body: any = {}) => {
  try {
    await store[action](item.id, body)
    const labels: Record<string, string> = {
      submitAvis: 'soumis', approveAvis: 'approuvé', rejectAvis: 'rejeté',
      publishAvis: 'publié', closeAvis: 'clos',
    }
    snackbar.value = { show: true, text: `Avis ${labels[action] ?? 'mis à jour'} avec succès`, color: 'success' }
    await loadData()
  }
  catch {
    snackbar.value = { show: true, text: 'Erreur lors de l\'action', color: 'error' }
  }
}

const confirmReject = async () => {
  try {
    await store.rejectAvis(selectedItem.value.id, { motif_rejet: motifRejet.value })
    rejectDialog.value = false
    snackbar.value = { show: true, text: 'Avis rejeté', color: 'warning' }
    await loadData()
  }
  catch {
    snackbar.value = { show: true, text: 'Erreur lors du rejet', color: 'error' }
  }
}

const resetDateFilters = () => {
  filterDateLimiteFrom.value = ''
  filterDateLimiteTo.value = ''
  filterDateOuvertureFrom.value = ''
  filterDateOuvertureTo.value = ''
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardTitle class="d-flex align-center pa-4">
          <VIcon icon="tabler-file-description" class="me-2" />
          Avis de Passation
          <VSpacer />
          <VBtn prepend-icon="tabler-plus" color="primary" @click="openCreate">Nouvel Avis</VBtn>
        </VCardTitle>

        <VDivider />

        <VCardText>
          <!-- Filtres principaux -->
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
              <VSelect
                v-model="filterMode"
                :items="[{ title: 'Tous modes', value: '' }, ...modeOptions]"
                label="Mode"
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
              <VBtn v-if="showDateFilters" icon variant="text" size="small" class="ms-1" @click="resetDateFilters">
                <VIcon icon="tabler-x" size="16" />
                <VTooltip activator="parent">Réinitialiser dates</VTooltip>
              </VBtn>
            </VCol>
          </VRow>

          <!-- Filtres de dates (conditionnels) -->
          <VExpandTransition>
            <VRow v-if="showDateFilters" class="mb-3">
              <VCol cols="12">
                <VCard variant="tonal" color="info" class="pa-3">
                  <p class="text-caption text-medium-emphasis mb-2">Filtres par dates</p>
                  <VRow>
                    <VCol cols="12" md="3">
                      <VTextField v-model="filterDateLimiteFrom" label="Date limite — du" type="date" density="compact" />
                    </VCol>
                    <VCol cols="12" md="3">
                      <VTextField v-model="filterDateLimiteTo" label="Date limite — au" type="date" density="compact" />
                    </VCol>
                    <VCol cols="12" md="3">
                      <VTextField v-model="filterDateOuvertureFrom" label="Date ouverture — du" type="date" density="compact" />
                    </VCol>
                    <VCol cols="12" md="3">
                      <VTextField v-model="filterDateOuvertureTo" label="Date ouverture — au" type="date" density="compact" />
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
            :items="store.avisList"
            :items-length="store.total"
            :loading="store.isLoading"
            class="elevation-0"
          >
            <template #item.objet="{ item }">
              <span class="text-truncate d-inline-block" style="max-width:220px" :title="item.objet">{{ item.objet }}</span>
            </template>
            <template #item.mode_passation="{ item }">
              <VChip size="x-small" variant="tonal" color="info">{{ item.mode_passation }}</VChip>
            </template>
            <template #item.date_limite_depot="{ item }">
              <span class="text-caption">{{ formatDate(item.date_limite_depot) }}</span>
            </template>
            <template #item.date_ouverture_plis="{ item }">
              <span class="text-caption">{{ formatDate(item.date_ouverture_plis) }}</span>
            </template>
            <template #item.statut="{ item }">
              <VChip :color="statutColor(item.statut)" size="small">{{ statutLabel(item.statut) }}</VChip>
            </template>
            <template #item.actions="{ item }">
              <!-- Détails -->
              <VBtn icon variant="text" size="small" color="secondary" @click="openDetails(item)">
                <VIcon icon="tabler-eye" />
                <VTooltip activator="parent">Détails</VTooltip>
              </VBtn>

              <!-- Éditer -->
              <VBtn icon variant="text" size="small" color="primary" @click="openEdit(item)">
                <VIcon icon="tabler-edit" />
                <VTooltip activator="parent">Modifier</VTooltip>
              </VBtn>

              <!-- Soumettre (draft → submitted) -->
              <VBtn v-if="item.statut === 'draft'" icon variant="text" size="small" color="info" @click="doAction('submitAvis', item)">
                <VIcon icon="tabler-send" />
                <VTooltip activator="parent">Soumettre</VTooltip>
              </VBtn>

              <!-- Approuver (submitted → approved) -->
              <VBtn v-if="item.statut === 'submitted'" icon variant="text" size="small" color="success" @click="doAction('approveAvis', item)">
                <VIcon icon="tabler-check" />
                <VTooltip activator="parent">Approuver</VTooltip>
              </VBtn>

              <!-- Rejeter (submitted) -->
              <VBtn v-if="item.statut === 'submitted'" icon variant="text" size="small" color="error" @click="openReject(item)">
                <VIcon icon="tabler-x" />
                <VTooltip activator="parent">Rejeter</VTooltip>
              </VBtn>

              <!-- Publier (approved → published) -->
              <VBtn v-if="item.statut === 'approved'" icon variant="text" size="small" color="primary" @click="doAction('publishAvis', item)">
                <VIcon icon="tabler-world" />
                <VTooltip activator="parent">Publier</VTooltip>
              </VBtn>

              <!-- Clore (published → closed) -->
              <VBtn v-if="item.statut === 'published'" icon variant="text" size="small" color="warning" @click="doAction('closeAvis', item)">
                <VIcon icon="tabler-lock" />
                <VTooltip activator="parent">Clore</VTooltip>
              </VBtn>

              <!-- Supprimer -->
              <VBtn icon variant="text" size="small" color="error" @click="openDelete(item)">
                <VIcon icon="tabler-trash" />
                <VTooltip activator="parent">Supprimer</VTooltip>
              </VBtn>
            </template>
          </VDataTableServer>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <!-- ─── Dialog Création / Édition ─── -->
  <VDialog v-model="dialog" max-width="950" scrollable>
    <VCard :title="isEditing ? 'Modifier l\'avis' : 'Nouvel avis de passation'">
      <VCardText class="pa-0">
        <VTabs v-model="activeTab" bg-color="surface">
          <VTab value="general">
            <VIcon icon="tabler-file-text" class="me-1" size="18" />
            Informations générales
          </VTab>
          <VTab value="fournisseurs">
            <VIcon icon="tabler-building-store" class="me-1" size="18" />
            Fournisseurs invités
            <VChip v-if="form.fournisseurs.length" size="x-small" color="primary" class="ms-1">{{ form.fournisseurs.length }}</VChip>
          </VTab>
          <VTab value="items">
            <VIcon icon="tabler-list" class="me-1" size="18" />
            Fournitures / Lignes
            <VChip v-if="form.items.length" size="x-small" color="primary" class="ms-1">{{ form.items.length }}</VChip>
          </VTab>
        </VTabs>

        <VDivider />

        <div class="pa-4">
          <VForm ref="formRef">
          <VTabsWindow v-model="activeTab">

            <!-- ── Onglet Informations générales ── -->
            <VTabsWindowItem value="general">
              <VRow class="mt-1">
                <VCol cols="12" md="4">
                  <VTextField v-model="form.reference" label="Référence *" placeholder="AO-2026-001" />
                </VCol>
                <VCol cols="12" md="4">
                  <VSelect v-model="form.mode_passation" :items="modeOptions" label="Mode de passation *" />
                </VCol>
                <VCol cols="12" md="4">
                  <VTextField v-model="form.exercice" label="Exercice *" placeholder="2026" />
                </VCol>
                <VCol cols="12">
                  <VTextField v-model="form.objet" label="Objet *" placeholder="Acquisition de matériel informatique..." />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField v-model="form.article_pour" label="Article pour" />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField v-model="form.article_relatif" label="Article relatif" />
                </VCol>
                <VCol cols="12" md="4">
                  <VTextField v-model.number="form.duree" label="Durée de la consultation (jours) *" type="number" min="1" :rules="[dureeRule]" />
                </VCol>
                <VCol cols="12" md="4">
                  <VTextField v-model.number="form.delai" label="Délai d'exécution (jours)" type="number" min="1" />
                </VCol>
                <VCol cols="12" md="4">
                  <VTextField v-model="form.date_limite_depot" label="Date limite de dépôt *" type="date" :rules="[requiredRule]" />
                </VCol>
                <VCol cols="12" md="4">
                  <VTextField v-model="form.date_ouverture_plis" label="Date d'ouverture des plis *" type="date" :rules="[requiredRule]" />
                </VCol>
                <VCol cols="12" md="4">
                  <VTextField v-model="form.date_publication" label="Date de publication *" type="date" :rules="[requiredRule]" />
                </VCol>
                <VCol cols="12" md="4">
                  <VSelect
                    v-model="form.statut"
                    :items="statutOptions"
                    label="Statut"
                  />
                </VCol>
                <VCol cols="12">
                  <VTextarea v-model="form.observations" label="Observations" rows="3" />
                </VCol>
              </VRow>
            </VTabsWindowItem>

            <!-- ── Onglet Fournisseurs invités ── -->
            <VTabsWindowItem value="fournisseurs">
              <div class="mt-2">
                <p class="text-body-2 text-medium-emphasis mb-3">
                  Sélectionnez les fournisseurs à inviter à soumissionner pour cet avis.
                </p>
                <VAutocomplete
                  v-model="form.fournisseurs"
                  :items="fournisseursStore.fournisseurs.map(f => ({ title: `${f.raison_sociale}${f.sigle ? ' (' + f.sigle + ')' : ''}`, value: f.id }))"
                  label="Fournisseurs invités"
                  multiple
                  chips
                  closable-chips
                  prepend-inner-icon="tabler-building-store"
                />
                <div v-if="form.fournisseurs.length > 0" class="mt-3">
                  <p class="text-caption text-medium-emphasis">{{ form.fournisseurs.length }} fournisseur(s) sélectionné(s)</p>
                </div>
              </div>
            </VTabsWindowItem>

            <!-- ── Onglet Fournitures / Lignes ── -->
            <VTabsWindowItem value="items">
              <div class="mt-2">
                <div v-if="form.items.length === 0" class="text-center py-6 text-medium-emphasis">
                  <VIcon icon="tabler-list" size="40" class="mb-2 opacity-30" />
                  <p class="text-body-2">Aucune ligne ajoutée</p>
                </div>

                <div
                  v-for="(item, index) in form.items"
                  :key="index"
                  class="mb-3 pa-3 border rounded"
                >
                  <div class="d-flex align-center mb-2">
                    <span class="text-caption font-weight-bold text-primary">Ligne {{ index + 1 }}</span>
                    <VSpacer />
                    <VBtn icon variant="text" size="small" color="error" @click="removeItem(index)">
                      <VIcon icon="tabler-x" />
                    </VBtn>
                  </div>
                  <VRow>
                    <VCol cols="12" md="6">
                      <VTextField v-model="item.designation" label="Désignation *" density="compact" />
                    </VCol>
                    <VCol cols="12" md="3">
                      <VTextField v-model.number="item.quantite" label="Qté" type="number" density="compact" />
                    </VCol>
                    <VCol cols="12" md="3">
                      <VTextField v-model="item.unite" label="Unité" density="compact" placeholder="pcs, kg, m..." />
                    </VCol>
                    <VCol cols="12" md="6">
                      <VTextField v-model="item.lieu" label="Lieu de livraison" density="compact" />
                    </VCol>
                    <VCol cols="12" md="6">
                      <VTextField v-model="item.description_detaillee" label="Description détaillée" density="compact" />
                    </VCol>
                  </VRow>
                </div>

                <VBtn prepend-icon="tabler-plus" variant="tonal" color="primary" class="mt-2" @click="addItem">
                  Ajouter une ligne
                </VBtn>
              </div>
            </VTabsWindowItem>

          </VTabsWindow>
          </VForm>
        </div>
      </VCardText>

      <VDivider />
      <VCardActions class="justify-end pa-4">
        <VBtn variant="tonal" @click="dialog = false">Annuler</VBtn>
        <VBtn color="primary" prepend-icon="tabler-device-floppy" @click="save">
          {{ isEditing ? 'Enregistrer' : 'Créer l\'avis' }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- ─── Dialog Rejet ─── -->
  <VDialog v-model="rejectDialog" max-width="500">
    <VCard>
      <VCardTitle class="d-flex align-center gap-2 pa-4">
        <VIcon icon="tabler-x" color="error" />
        Rejeter l'avis
      </VCardTitle>
      <VCardText>
        <p class="mb-3">
          Vous allez rejeter l'avis <strong>{{ selectedItem?.reference }}</strong>.
        </p>
        <VTextarea
          v-model="motifRejet"
          label="Motif du rejet *"
          placeholder="Expliquez la raison du rejet..."
          rows="3"
          required
        />
      </VCardText>
      <VCardActions class="justify-end pa-4">
        <VBtn variant="tonal" @click="rejectDialog = false">Annuler</VBtn>
        <VBtn color="error" :disabled="!motifRejet.trim()" @click="confirmReject">Confirmer le rejet</VBtn>
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
        Voulez-vous vraiment supprimer l'avis <strong>{{ selectedItem?.reference }}</strong> ?
        <br />
        <span class="text-caption text-medium-emphasis">Cette action est irréversible.</span>
      </VCardText>
      <VCardActions class="justify-end pa-4">
        <VBtn variant="tonal" @click="deleteDialog = false">Annuler</VBtn>
        <VBtn color="error" @click="confirmDelete">Supprimer</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- ─── Dialog Détails ─── -->
  <VDialog v-model="detailsDialog" max-width="750" scrollable>
    <VCard v-if="detailsItem">
      <VCardTitle class="d-flex align-center gap-2 pa-4">
        <VIcon icon="tabler-file-description" color="primary" />
        {{ detailsItem.reference }}
        <VSpacer />
        <VChip :color="statutColor(detailsItem.statut)" size="small">{{ statutLabel(detailsItem.statut) }}</VChip>
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-4">
        <!-- Informations générales -->
        <p class="text-subtitle-2 font-weight-bold mb-3">Informations générales</p>
        <VRow dense>
          <VCol cols="12" md="8">
            <p class="text-caption text-medium-emphasis">Objet</p>
            <p class="text-body-2 mb-3">{{ detailsItem.objet }}</p>
          </VCol>
          <VCol cols="12" md="4">
            <p class="text-caption text-medium-emphasis">Mode de passation</p>
            <VChip size="x-small" variant="tonal" color="info" class="mb-3">{{ detailsItem.mode_passation }}</VChip>
          </VCol>
          <VCol cols="6" md="3">
            <p class="text-caption text-medium-emphasis">Exercice</p>
            <p class="text-body-2 mb-2">{{ detailsItem.exercice }}</p>
          </VCol>
          <VCol cols="6" md="3">
            <p class="text-caption text-medium-emphasis">Durée de la consultation (jours)</p>
            <p class="text-body-2 mb-2">{{ detailsItem.duree ?? '-' }}</p>
          </VCol>
          <VCol cols="6" md="3">
            <p class="text-caption text-medium-emphasis">Délai d'exécution (jours)</p>
            <p class="text-body-2 mb-2">{{ detailsItem.delai ?? '-' }}</p>
          </VCol>
          <VCol cols="6" md="3">
            <p class="text-caption text-medium-emphasis">Date limite dépôt</p>
            <p class="text-body-2 mb-2">{{ formatDate(detailsItem.date_limite_depot) }}</p>
          </VCol>
          <VCol cols="6" md="3">
            <p class="text-caption text-medium-emphasis">Date ouverture plis</p>
            <p class="text-body-2 mb-2">{{ formatDate(detailsItem.date_ouverture_plis) }}</p>
          </VCol>
          <VCol cols="6" md="3">
            <p class="text-caption text-medium-emphasis">Date de publication</p>
            <p class="text-body-2 mb-2">{{ formatDate(detailsItem.date_publication) }}</p>
          </VCol>
          <VCol v-if="detailsItem.article_pour" cols="12" md="6">
            <p class="text-caption text-medium-emphasis">Article pour</p>
            <p class="text-body-2 mb-2">{{ detailsItem.article_pour }}</p>
          </VCol>
          <VCol v-if="detailsItem.article_relatif" cols="12" md="6">
            <p class="text-caption text-medium-emphasis">Article relatif</p>
            <p class="text-body-2 mb-2">{{ detailsItem.article_relatif }}</p>
          </VCol>
          <VCol v-if="detailsItem.motif_rejet" cols="12">
            <VAlert type="error" variant="tonal" density="compact" class="mb-2">
              <strong>Motif du rejet :</strong> {{ detailsItem.motif_rejet }}
            </VAlert>
          </VCol>
          <VCol v-if="detailsItem.observations" cols="12">
            <p class="text-caption text-medium-emphasis">Observations</p>
            <p class="text-body-2 mb-2">{{ detailsItem.observations }}</p>
          </VCol>
        </VRow>

        <!-- Fournisseurs -->
        <template v-if="detailsItem.fournisseurs?.length">
          <VDivider class="my-3" />
          <p class="text-subtitle-2 font-weight-bold mb-2">
            Fournisseurs invités
            <VChip size="x-small" color="primary" class="ms-1">{{ detailsItem.fournisseurs.length }}</VChip>
          </p>
          <div class="d-flex flex-wrap gap-2">
            <VChip
              v-for="f in detailsItem.fournisseurs"
              :key="f.id"
              size="small"
              variant="tonal"
              color="secondary"
              prepend-icon="tabler-building-store"
            >
              {{ f.raison_sociale }}{{ f.sigle ? ` (${f.sigle})` : '' }}
            </VChip>
          </div>
        </template>

        <!-- Lignes / Items -->
        <template v-if="detailsItem.items?.length">
          <VDivider class="my-3" />
          <p class="text-subtitle-2 font-weight-bold mb-2">
            Fournitures / Lignes
            <VChip size="x-small" color="primary" class="ms-1">{{ detailsItem.items.length }}</VChip>
          </p>
          <VTable density="compact">
            <thead>
              <tr>
                <th>#</th>
                <th>Désignation</th>
                <th>Qté</th>
                <th>Unité</th>
                <th>Lieu</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in detailsItem.items" :key="item.id">
                <td>{{ item.ordre }}</td>
                <td>{{ item.designation }}</td>
                <td>{{ item.quantite }}</td>
                <td>{{ item.unite ?? '-' }}</td>
                <td>{{ item.lieu ?? '-' }}</td>
              </tr>
            </tbody>
          </VTable>
        </template>
      </VCardText>
      <VDivider />
      <VCardActions class="justify-end pa-3">
        <VBtn variant="tonal" @click="detailsDialog = false">Fermer</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <VSnackbar v-model="snackbar.show" :color="snackbar.color" timeout="4000" location="top right">
    {{ snackbar.text }}
  </VSnackbar>
</template>
