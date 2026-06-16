<script setup lang="ts">
import { MODE_PASSATION_OPTIONS, modePassationLabel } from '@/constants/modesPassation'
import { useAvisStore } from '@/stores/avis'
import { useFournisseursStore } from '@/stores/fournisseurs'
import type { Fournisseur } from '@/stores/fournisseurs'
import { useExpressionsBesoinStore } from '@/stores/expressionsBesoin'

const formatDate = (v: string | null | undefined) => {
  if (!v) return '-'
  const d = new Date(v)
  if (isNaN(d.getTime())) return v
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

definePage({ meta: { title: 'Avis de Passation' } })

const store = useAvisStore()
const fournisseursStore = useFournisseursStore()
const expressionsStore = useExpressionsBesoinStore()
const snackbar = ref({ show: false, text: '', color: 'success' })
const dialog = ref(false)
const deleteDialog = ref(false)
const rejectDialog = ref(false)
const isEditing = ref(false)
const selectedItem = ref<any>(null)
const motifRejet = ref('')
const activeTab = ref('general')
const formRef = ref<any>(null)

const requiredRule = (v: unknown) => !!String(v ?? '').trim() || 'Ce champ est obligatoire'
const dureeRule = (v: number | null) => (v != null && v > 0) || 'Ce champ est obligatoire'
const expressionRule = (v: number | null) => (v != null && v > 0) || 'Sélectionnez une expression de besoin'

const formatApiErrors = (e: any) => {
  const errors = e?.data?.errors
  if (errors && typeof errors === 'object')
    return Object.values(errors).flat().join(' · ')

  return e?.data?.message || e?.message || 'Une erreur est survenue'
}

const buildAvisPayload = () => {
  const items = form.value.items
    .filter((item: any) => item.expression_besoin_id != null)
    .map((item: any) => ({
      expression_besoin_id: item.expression_besoin_id,
      designation: item.designation,
      description_detaillee: item.description_detaillee || undefined,
      quantite: item.quantite ?? 1,
      unite: item.unite || undefined,
      lieu: item.lieu || undefined,
    }))

  const payload: Record<string, any> = {
    reference: form.value.reference?.trim(),
    objet: form.value.objet?.trim(),
    mode_passation: form.value.mode_passation,
    exercice: String(form.value.exercice ?? '').trim(),
    duree: Number(form.value.duree),
    date_limite_depot: form.value.date_limite_depot,
    date_ouverture_plis: form.value.date_ouverture_plis,
    date_publication: form.value.date_publication,
    statut: form.value.statut,
    fournisseurs: form.value.fournisseurs,
  }

  if (form.value.article_pour?.trim())
    payload.article_pour = form.value.article_pour.trim()
  if (form.value.article_relatif?.trim())
    payload.article_relatif = form.value.article_relatif.trim()
  if (form.value.delai != null && form.value.delai > 0)
    payload.delai = Number(form.value.delai)
  if (form.value.observations?.trim())
    payload.observations = form.value.observations.trim()
  if (items.length)
    payload.items = items

  return payload
}

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

const modeOptions = MODE_PASSATION_OPTIONS

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
const detailsLoading = ref(false)

const itemDesignation = (item: any) =>
  item?.designation
  || item?.expression_besoin?.libelle
  || item?.expressionBesoin?.libelle
  || '-'

const itemExpressionCode = (item: any) =>
  item?.expression_besoin?.code ?? item?.expressionBesoin?.code ?? null

const openDetails = async (item: any) => {
  detailsDialog.value = true
  detailsLoading.value = true
  detailsItem.value = null
  try {
    detailsItem.value = await store.fetchAvisById(item.id)
  }
  finally {
    detailsLoading.value = false
  }
}

const editFromDetails = async () => {
  if (!detailsItem.value)
    return
  const item = { ...detailsItem.value }
  detailsDialog.value = false
  await openEdit(item)
}

const headers = [
  { title: 'Référence', key: 'reference', sortable: true },
  { title: 'Objet', key: 'objet', sortable: true },
  { title: 'Mode', key: 'mode_passation', sortable: true },
  { title: 'Exercice', key: 'exercice', sortable: true },
  { title: 'Date limite', key: 'date_limite_depot', sortable: true },
  { title: 'Date ouverture', key: 'date_ouverture_plis', sortable: true },
  { title: 'Statut', key: 'statut', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, width: '1%', align: 'end' },
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

const isFournisseurEligible = (f: Fournisseur) => {
  const modes = f.modes_passation ?? []
  if (modes.length > 0 && form.value.mode_passation && !modes.includes(form.value.mode_passation))
    return false

  const duree = form.value.duree
  if (duree != null && duree > 0) {
    if (f.duree_min != null && duree < f.duree_min)
      return false
    if (f.duree_max != null && duree > f.duree_max)
      return false
  }

  return true
}

const eligibleFournisseurOptions = computed(() =>
  fournisseursStore.fournisseurs
    .filter(isFournisseurEligible)
    .map(f => ({
      title: `${f.raison_sociale}${f.sigle ? ` (${f.sigle})` : ''}`,
      value: f.id,
    })),
)

const pruneIneligibleFournisseurs = () => {
  const eligibleIds = new Set(eligibleFournisseurOptions.value.map(o => o.value))
  form.value.fournisseurs = form.value.fournisseurs.filter(id => eligibleIds.has(id))
}

watch(
  () => [form.value.mode_passation, form.value.duree] as const,
  pruneIneligibleFournisseurs,
)

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
    expressionsStore.fetchExpressions({ itemsPerPage: -1, actif: true }),
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
  form.value.items.push({
    expression_besoin_id: null,
    designation: '',
    description_detaillee: '',
    quantite: 1,
    unite: '',
    lieu: '',
  })
}

const expressionOptions = computed(() =>
  expressionsStore.expressions.map(e => ({
    title: `${e.code} — ${e.libelle}`,
    value: e.id,
  })),
)

const applyExpressionToItem = (item: any, expressionId: number | null) => {
  item.expression_besoin_id = expressionId
  const expression = expressionsStore.expressions.find(e => e.id === expressionId)
  if (!expression)
    return

  item.designation = expression.libelle
  item.description_detaillee = expression.description ?? ''
  item.unite = expression.unite_defaut ?? item.unite ?? ''
}

const removeItem = (index: number) => {
  form.value.items.splice(index, 1)
}

const save = async () => {
  const incompleteItems = form.value.items.some((item: any) => !item.expression_besoin_id)
  if (incompleteItems) {
    activeTab.value = 'items'
    snackbar.value = { show: true, text: 'Chaque ligne doit avoir une expression de besoin sélectionnée', color: 'error' }
    return
  }

  const { valid } = await formRef.value?.validate()
  if (!valid) {
    activeTab.value = 'general'
    snackbar.value = { show: true, text: 'Veuillez renseigner tous les champs obligatoires', color: 'error' }
    return
  }

  const payload = buildAvisPayload()

  try {
    if (isEditing.value)
      await store.updateAvis(selectedItem.value.id, payload)
    else
      await store.createAvis(payload)
    dialog.value = false
    snackbar.value = { show: true, text: `Avis ${isEditing.value ? 'modifié' : 'créé'} avec succès`, color: 'success' }
    await loadData()
  }
  catch (e: any) {
    snackbar.value = { show: true, text: formatApiErrors(e), color: 'error' }
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
              <VChip size="x-small" variant="tonal" color="info">{{ modePassationLabel(item.mode_passation) }}</VChip>
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
              <div class="avis-row-actions d-flex align-center justify-end flex-nowrap">
                <!-- Détails -->
                <VBtn icon variant="text" size="small" color="secondary" @click="openDetails(item)">
                  <VIcon icon="tabler-eye" size="20" />
                  <VTooltip activator="parent" location="top">Détails</VTooltip>
                </VBtn>

                <!-- Éditer -->
                <VBtn icon variant="text" size="small" color="primary" @click="openEdit(item)">
                  <VIcon icon="tabler-edit" size="20" />
                  <VTooltip activator="parent" location="top">Modifier</VTooltip>
                </VBtn>

                <!-- Soumettre (draft → submitted) -->
                <VBtn v-if="item.statut === 'draft'" icon variant="text" size="small" color="info" @click="doAction('submitAvis', item)">
                  <VIcon icon="tabler-send" size="20" />
                  <VTooltip activator="parent" location="top">Soumettre</VTooltip>
                </VBtn>

                <!-- Approuver (submitted → approved) -->
                <VBtn v-if="item.statut === 'submitted'" icon variant="text" size="small" color="success" @click="doAction('approveAvis', item)">
                  <VIcon icon="tabler-check" size="20" />
                  <VTooltip activator="parent" location="top">Approuver</VTooltip>
                </VBtn>

                <!-- Rejeter (submitted) -->
                <VBtn v-if="item.statut === 'submitted'" icon variant="text" size="small" color="error" @click="openReject(item)">
                  <VIcon icon="tabler-x" size="20" />
                  <VTooltip activator="parent" location="top">Rejeter</VTooltip>
                </VBtn>

                <!-- Publier (approved → published) -->
                <VBtn v-if="item.statut === 'approved'" icon variant="text" size="small" color="primary" @click="doAction('publishAvis', item)">
                  <VIcon icon="tabler-world" size="20" />
                  <VTooltip activator="parent" location="top">Publier</VTooltip>
                </VBtn>

                <!-- Clore (published → closed) -->
                <VBtn v-if="item.statut === 'published'" icon variant="text" size="small" color="warning" @click="doAction('closeAvis', item)">
                  <VIcon icon="tabler-lock" size="20" />
                  <VTooltip activator="parent" location="top">Clore</VTooltip>
                </VBtn>

                <!-- Supprimer -->
                <VBtn icon variant="text" size="small" color="error" @click="openDelete(item)">
                  <VIcon icon="tabler-trash" size="20" />
                  <VTooltip activator="parent" location="top">Supprimer</VTooltip>
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
                  <VTextField v-model="form.reference" label="Référence *" placeholder="AO-2026-001" :rules="[requiredRule]" />
                </VCol>
                <VCol cols="12" md="4">
                  <VSelect v-model="form.mode_passation" :items="modeOptions" label="Mode de passation *" :rules="[requiredRule]" />
                </VCol>
                <VCol cols="12" md="4">
                  <VTextField v-model="form.exercice" label="Exercice *" placeholder="2026" :rules="[requiredRule]" />
                </VCol>
                <VCol cols="12">
                  <VTextField v-model="form.objet" label="Objet *" placeholder="Acquisition de matériel informatique..." :rules="[requiredRule]" />
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
                <VAlert
                  v-if="!form.mode_passation || !form.duree"
                  type="info"
                  variant="tonal"
                  density="compact"
                  class="mb-3"
                >
                  Renseignez le mode de passation et la durée de la consultation dans l'onglet Informations générales pour filtrer les fournisseurs éligibles.
                </VAlert>
                <p class="text-body-2 text-medium-emphasis mb-3">
                  Fournisseurs éligibles pour le mode <strong>{{ form.mode_passation }}</strong>
                  <template v-if="form.duree"> et une durée de <strong>{{ form.duree }} jour(s)</strong></template>.
                </p>
                <VAutocomplete
                  v-model="form.fournisseurs"
                  :items="eligibleFournisseurOptions"
                  label="Fournisseurs invités"
                  multiple
                  chips
                  closable-chips
                  prepend-inner-icon="tabler-building-store"
                  :no-data-text="form.mode_passation && form.duree ? 'Aucun fournisseur éligible' : 'Complétez le mode et la durée de l\'avis'"
                />
                <div v-if="form.fournisseurs.length > 0" class="mt-3">
                  <p class="text-caption text-medium-emphasis">{{ form.fournisseurs.length }} fournisseur(s) sélectionné(s) sur {{ eligibleFournisseurOptions.length }} éligible(s)</p>
                </div>
              </div>
            </VTabsWindowItem>

            <!-- ── Onglet Fournitures / Lignes ── -->
            <VTabsWindowItem value="items">
              <div class="mt-2">
                <p class="text-body-2 text-medium-emphasis mb-3">
                  Sélectionnez une expression de besoin du référentiel pour chaque ligne.
                  <RouterLink :to="{ name: 'apps-referentiels-expressions-besoin' }" class="text-primary">
                    Gérer le catalogue
                  </RouterLink>
                </p>
                <VAlert
                  v-if="expressionsStore.expressions.length === 0"
                  type="warning"
                  variant="tonal"
                  density="compact"
                  class="mb-3"
                >
                  Aucune expression de besoin disponible. Créez-en dans le référentiel ou exécutez le seeder
                  <code class="text-caption">php artisan db:seed --class=ExpressionBesoinSeeder</code>.
                </VAlert>
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
                      <VAutocomplete
                        :model-value="item.expression_besoin_id"
                        :items="expressionOptions"
                        label="Expression de besoin *"
                        density="compact"
                        :rules="[expressionRule]"
                        @update:model-value="applyExpressionToItem(item, $event)"
                      />
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
  <VDialog v-model="detailsDialog" max-width="920" scrollable>
    <VCard v-if="detailsLoading" class="pa-8">
      <div class="d-flex flex-column align-center justify-center py-10">
        <VProgressCircular indeterminate color="primary" size="48" />
        <p class="text-body-2 text-medium-emphasis mt-4">Chargement des détails...</p>
      </div>
    </VCard>

    <VCard v-else-if="detailsItem">
      <!-- En-tête -->
      <div class="avis-details-header pa-4 pa-sm-5">
        <div class="d-flex flex-wrap align-center gap-3">
          <VAvatar color="primary" variant="tonal" size="48" rounded>
            <VIcon icon="tabler-file-description" size="26" />
          </VAvatar>
          <div class="flex-grow-1 min-w-0">
            <p class="text-caption text-medium-emphasis mb-1">Avis de passation</p>
            <h2 class="text-h5 font-weight-bold mb-1">{{ detailsItem.reference }}</h2>
            <p class="text-body-2 text-medium-emphasis text-truncate mb-0">{{ detailsItem.objet }}</p>
          </div>
          <VChip :color="statutColor(detailsItem.statut)" size="small" class="font-weight-medium">
            {{ statutLabel(detailsItem.statut) }}
          </VChip>
        </div>
      </div>

      <VDivider />

      <VCardText class="pa-4 pa-sm-5">
        <VAlert
          v-if="detailsItem.motif_rejet"
          type="error"
          variant="tonal"
          density="compact"
          class="mb-4"
          icon="tabler-alert-circle"
        >
          <strong>Motif du rejet :</strong> {{ detailsItem.motif_rejet }}
        </VAlert>

        <!-- Synthèse -->
        <VRow class="mb-2">
          <VCol cols="12" sm="6" md="3">
            <VCard variant="tonal" color="info" class="h-100">
              <VCardText class="pa-3">
                <div class="d-flex align-center gap-2 mb-1">
                  <VIcon icon="tabler-gavel" size="18" />
                  <span class="text-caption font-weight-medium">Mode</span>
                </div>
                <p class="text-body-2 font-weight-medium mb-0">{{ modePassationLabel(detailsItem.mode_passation) }}</p>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="6" sm="3" md="3">
            <VCard variant="outlined" class="h-100">
              <VCardText class="pa-3">
                <div class="d-flex align-center gap-2 mb-1">
                  <VIcon icon="tabler-calendar" size="18" color="primary" />
                  <span class="text-caption text-medium-emphasis">Exercice</span>
                </div>
                <p class="text-h6 font-weight-bold mb-0">{{ detailsItem.exercice }}</p>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="6" sm="3" md="3">
            <VCard variant="outlined" class="h-100">
              <VCardText class="pa-3">
                <div class="d-flex align-center gap-2 mb-1">
                  <VIcon icon="tabler-clock" size="18" color="primary" />
                  <span class="text-caption text-medium-emphasis">Durée consultation</span>
                </div>
                <p class="text-h6 font-weight-bold mb-0">{{ detailsItem.duree ?? '-' }} <span class="text-caption font-weight-regular">j</span></p>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="12" sm="6" md="3">
            <VCard variant="outlined" class="h-100">
              <VCardText class="pa-3">
                <div class="d-flex align-center gap-2 mb-1">
                  <VIcon icon="tabler-truck-delivery" size="18" color="primary" />
                  <span class="text-caption text-medium-emphasis">Délai d'exécution</span>
                </div>
                <p class="text-h6 font-weight-bold mb-0">{{ detailsItem.delai ?? '-' }} <span class="text-caption font-weight-regular">j</span></p>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>

        <!-- Calendrier -->
        <p class="text-subtitle-2 font-weight-bold d-flex align-center gap-2 mb-3 mt-2">
          <VIcon icon="tabler-calendar-event" size="20" />
          Calendrier
        </p>
        <VRow dense class="mb-4">
          <VCol cols="12" md="4">
            <div class="avis-details-date-box pa-3 rounded border">
              <p class="text-caption text-medium-emphasis mb-1">Date de publication</p>
              <p class="text-body-1 font-weight-medium mb-0">{{ formatDate(detailsItem.date_publication) }}</p>
            </div>
          </VCol>
          <VCol cols="12" md="4">
            <div class="avis-details-date-box pa-3 rounded border">
              <p class="text-caption text-medium-emphasis mb-1">Date limite de dépôt</p>
              <p class="text-body-1 font-weight-medium mb-0">{{ formatDate(detailsItem.date_limite_depot) }}</p>
            </div>
          </VCol>
          <VCol cols="12" md="4">
            <div class="avis-details-date-box pa-3 rounded border">
              <p class="text-caption text-medium-emphasis mb-1">Date d'ouverture des plis</p>
              <p class="text-body-1 font-weight-medium mb-0">{{ formatDate(detailsItem.date_ouverture_plis) }}</p>
            </div>
          </VCol>
        </VRow>

        <!-- Articles -->
        <VRow v-if="detailsItem.article_pour || detailsItem.article_relatif" dense class="mb-4">
          <VCol v-if="detailsItem.article_pour" cols="12" md="6">
            <div class="pa-3 rounded border">
              <p class="text-caption text-medium-emphasis mb-1">Article pour</p>
              <p class="text-body-2 mb-0">{{ detailsItem.article_pour }}</p>
            </div>
          </VCol>
          <VCol v-if="detailsItem.article_relatif" cols="12" md="6">
            <div class="pa-3 rounded border">
              <p class="text-caption text-medium-emphasis mb-1">Article relatif</p>
              <p class="text-body-2 mb-0">{{ detailsItem.article_relatif }}</p>
            </div>
          </VCol>
        </VRow>

        <!-- Fournisseurs -->
        <div class="mb-4">
          <p class="text-subtitle-2 font-weight-bold d-flex align-center gap-2 mb-3">
            <VIcon icon="tabler-building-store" size="20" />
            Fournisseurs invités
            <VChip size="x-small" color="primary" variant="tonal">{{ detailsItem.fournisseurs?.length ?? 0 }}</VChip>
          </p>
          <div v-if="detailsItem.fournisseurs?.length" class="d-flex flex-wrap gap-2">
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
          <p v-else class="text-body-2 text-medium-emphasis mb-0">Aucun fournisseur invité.</p>
        </div>

        <!-- Lignes -->
        <div class="mb-2">
          <p class="text-subtitle-2 font-weight-bold d-flex align-center gap-2 mb-3">
            <VIcon icon="tabler-list-details" size="20" />
            Fournitures / Lignes
            <VChip size="x-small" color="primary" variant="tonal">{{ detailsItem.items?.length ?? 0 }}</VChip>
          </p>

          <VTable v-if="detailsItem.items?.length" density="comfortable" class="avis-details-table rounded border">
            <thead>
              <tr>
                <th class="text-caption font-weight-bold">#</th>
                <th class="text-caption font-weight-bold">Code</th>
                <th class="text-caption font-weight-bold">Expression / Désignation</th>
                <th class="text-caption font-weight-bold text-end">Qté</th>
                <th class="text-caption font-weight-bold">Unité</th>
                <th class="text-caption font-weight-bold">Lieu</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in detailsItem.items" :key="item.id ?? `line-${index}`">
                <td class="text-medium-emphasis">{{ item.ordre ?? index + 1 }}</td>
                <td>
                  <VChip v-if="itemExpressionCode(item)" size="x-small" variant="outlined" color="primary">
                    {{ itemExpressionCode(item) }}
                  </VChip>
                  <span v-else class="text-caption">-</span>
                </td>
                <td>
                  <p class="text-body-2 font-weight-medium mb-0">{{ itemDesignation(item) }}</p>
                  <p v-if="item.description_detaillee" class="text-caption text-medium-emphasis mb-0 mt-1">
                    {{ item.description_detaillee }}
                  </p>
                </td>
                <td class="text-end font-weight-medium">{{ item.quantite ?? '-' }}</td>
                <td>{{ item.unite || '-' }}</td>
                <td>{{ item.lieu || '-' }}</td>
              </tr>
            </tbody>
          </VTable>
          <VAlert v-else type="info" variant="tonal" density="compact">
            Aucune ligne de fourniture enregistrée pour cet avis.
          </VAlert>
        </div>

        <VExpandTransition>
          <div v-if="detailsItem.observations" class="mt-4">
            <p class="text-subtitle-2 font-weight-bold d-flex align-center gap-2 mb-2">
              <VIcon icon="tabler-notes" size="20" />
              Observations
            </p>
            <VCard variant="tonal" color="secondary">
              <VCardText class="text-body-2">{{ detailsItem.observations }}</VCardText>
            </VCard>
          </div>
        </VExpandTransition>
      </VCardText>

      <VDivider />
      <VCardActions class="justify-space-between pa-4">
        <VBtn variant="text" prepend-icon="tabler-pencil" color="primary" @click="editFromDetails">
          Modifier
        </VBtn>
        <VBtn variant="tonal" @click="detailsDialog = false">Fermer</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <VSnackbar v-model="snackbar.show" :color="snackbar.color" timeout="4000" location="top right">
    {{ snackbar.text }}
  </VSnackbar>
</template>

<style scoped>
.avis-details-header {
  background: rgb(var(--v-theme-surface-variant), 0.35);
}

.avis-details-date-box {
  border-color: rgba(var(--v-border-color), var(--v-border-opacity)) !important;
  background: rgb(var(--v-theme-surface));
  block-size: 100%;
}

.avis-row-actions {
  gap: 0;
  min-inline-size: max-content;
  white-space: nowrap;
}
</style>
