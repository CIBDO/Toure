<script setup lang="ts">
import { useApi } from '@/composables/useApi'

definePage({ meta: { title: 'Dépouillements' } })

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
const filterAvis = ref('')
const filterDateFrom = ref('')
const filterDateTo = ref('')
const showDateFilters = ref(false)

const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref([{ key: 'created_at', order: 'desc' }])

const depouillements = ref<any[]>([])
const total = ref(0)
const isLoading = ref(false)

// Fournisseurs liés à l'avis sélectionné dans le formulaire
const avisFournisseurs = ref<any[]>([])

const emptyForm = () => ({
  reference: '',
  avis_id: null as number | null,
  date_depouillement: '',
  lieu: '',
  resultats: [] as any[],
  statut: 'draft',
  observations: '',
})

const form = ref(emptyForm())

const headers = [
  { title: 'Référence', key: 'reference', sortable: true },
  { title: 'Avis', key: 'avis', sortable: false },
  { title: 'Date', key: 'date_depouillement', sortable: true },
  { title: 'Lieu', key: 'lieu', sortable: false },
  { title: 'Offres', key: 'nb_offres', sortable: false },
  { title: 'Statut', key: 'statut', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, width: '200px' },
]

const statutOptions = [
  { title: 'Brouillon', value: 'draft' },
  { title: 'Soumis', value: 'submitted' },
  { title: 'Approuvé', value: 'approved' },
  { title: 'Rejeté', value: 'rejected' },
]

const statutColor = (s: string) => ({
  draft: 'default', submitted: 'info', approved: 'success', rejected: 'error',
}[s] || 'default')

const statutLabel = (s: string) => ({
  draft: 'Brouillon', submitted: 'Soumis', approved: 'Approuvé', rejected: 'Rejeté',
}[s] || s)

// Charger tous les avis (clos ou approuvés) pour la liste de sélection
const { data: avisData } = await useApi<any>('/avis?itemsPerPage=-1').json()
const avisList = computed(() =>
  avisData.value?.data?.map((a: any) => ({
    title: `${a.reference} — ${a.objet?.substring(0, 50)}`,
    value: a.id,
    fournisseurs: a.fournisseurs ?? [],
  })) ?? [],
)

// Quand l'avis change dans le formulaire, charger ses fournisseurs
watch(() => form.value.avis_id, async (newId) => {
  if (!newId) {
    avisFournisseurs.value = []
    return
  }
  const found = avisList.value.find((a: any) => a.value === newId)
  if (found?.fournisseurs?.length) {
    avisFournisseurs.value = found.fournisseurs
  }
  else {
    // Charger depuis l'API si pas disponible
    const { data } = await useApi(`/avis/${newId}`).json()
    avisFournisseurs.value = data.value?.fournisseurs ?? []
  }
})

const loadData = async () => {
  isLoading.value = true
  try {
    const params = new URLSearchParams()
    if (searchQuery.value) params.set('q', searchQuery.value)
    if (filterStatut.value) params.set('statut', filterStatut.value)
    if (filterAvis.value) params.set('avis_id', filterAvis.value)
    if (filterDateFrom.value) params.set('date_from', filterDateFrom.value)
    if (filterDateTo.value) params.set('date_to', filterDateTo.value)
    params.set('itemsPerPage', itemsPerPage.value.toString())
    params.set('page', page.value.toString())
    params.set('sortBy', sortBy.value[0]?.key ?? 'created_at')
    params.set('sortDesc', (sortBy.value[0]?.order === 'desc').toString())

    const { data } = await useApi(`/depouillements?${params}`).json()
    depouillements.value = data.value?.data ?? []
    total.value = data.value?.total ?? 0
  }
  finally {
    isLoading.value = false
  }
}

watch(
  [searchQuery, filterStatut, filterAvis, filterDateFrom, filterDateTo, itemsPerPage, page, sortBy],
  loadData,
  { deep: true },
)
onMounted(loadData)

const openCreate = () => {
  isEditing.value = false
  form.value = emptyForm()
  avisFournisseurs.value = []
  dialog.value = true
}

const openEdit = async (item: any) => {
  isEditing.value = true
  selectedItem.value = item
  const { data } = await useApi(`/depouillements/${item.id}`).json()
  const dep = data.value
  form.value = {
    ...emptyForm(),
    ...dep,
    resultats: dep.resultats ?? [],
  }
  // Charger les fournisseurs de l'avis lié
  if (dep.avis_id) {
    const { data: avisDetail } = await useApi(`/avis/${dep.avis_id}`).json()
    avisFournisseurs.value = avisDetail.value?.fournisseurs ?? []
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

// Ajouter un résultat avec fournisseur lié à l'avis
const addResultat = () => {
  form.value.resultats.push({
    fournisseur_id: null,
    fournisseur_nom: '',
    montant: 0,
    note_technique: null,
    note_financiere: null,
    rang: form.value.resultats.length + 1,
    observations: '',
    retenu: false,
  })
}

const removeResultat = (index: number) => {
  form.value.resultats.splice(index, 1)
  // Renuméroter les rangs
  form.value.resultats.forEach((r, i) => { r.rang = i + 1 })
}

// Quand un fournisseur est sélectionné dans un résultat, remplir son nom
const onFournisseurSelect = (resultat: any, fournisseurId: number) => {
  const found = avisFournisseurs.value.find((f: any) => f.id === fournisseurId)
  if (found) resultat.fournisseur_nom = found.raison_sociale
}

const save = async () => {
  try {
    if (isEditing.value)
      await useApi(`/depouillements/${selectedItem.value.id}`).put(form.value).json()
    else
      await useApi('/depouillements').post(form.value).json()
    dialog.value = false
    snackbar.value = { show: true, text: `Dépouillement ${isEditing.value ? 'modifié' : 'créé'} avec succès`, color: 'success' }
    await loadData()
  }
  catch (e: any) {
    snackbar.value = { show: true, text: e?.data?.message || 'Une erreur est survenue', color: 'error' }
  }
}

const confirmDelete = async () => {
  try {
    await useApi(`/depouillements/${selectedItem.value.id}`).delete().json()
    deleteDialog.value = false
    snackbar.value = { show: true, text: 'Dépouillement supprimé', color: 'success' }
    await loadData()
  }
  catch {
    snackbar.value = { show: true, text: 'Impossible de supprimer', color: 'error' }
  }
}

const doAction = async (action: 'submit' | 'approve', item: any) => {
  try {
    await useApi(`/depouillements/${item.id}/${action}`).post({}).json()
    const labels = { submit: 'soumis', approve: 'approuvé' }
    snackbar.value = { show: true, text: `Dépouillement ${labels[action]}`, color: 'success' }
    await loadData()
  }
  catch {
    snackbar.value = { show: true, text: 'Erreur lors de l\'action', color: 'error' }
  }
}

const confirmReject = async () => {
  try {
    await useApi(`/depouillements/${selectedItem.value.id}/reject`).post({ motif_rejet: motifRejet.value }).json()
    rejectDialog.value = false
    snackbar.value = { show: true, text: 'Dépouillement rejeté', color: 'warning' }
    await loadData()
  }
  catch {
    snackbar.value = { show: true, text: 'Erreur lors du rejet', color: 'error' }
  }
}

const formatMontant = (v: number) => v ? new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v) : '-'

const formatDate = (v: string | null | undefined) => {
  if (!v) return '-'
  const d = new Date(v)
  if (isNaN(d.getTime())) return v
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const detailsDialog = ref(false)
const detailsItem = ref<any>(null)

const openDetails = async (item: any) => {
  const { data } = await useApi(`/depouillements/${item.id}`).json()
  detailsItem.value = data.value
  detailsDialog.value = true
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardTitle class="d-flex align-center pa-4">
          <VIcon icon="tabler-clipboard-list" class="me-2" />
          Dépouillements
          <VSpacer />
          <VBtn prepend-icon="tabler-plus" color="primary" @click="openCreate">Nouveau Dépouillement</VBtn>
        </VCardTitle>

        <VDivider />

        <VCardText>
          <!-- Filtres -->
          <VRow class="mb-2">
            <VCol cols="12" md="3">
              <VTextField
                v-model="searchQuery"
                placeholder="Référence..."
                prepend-inner-icon="tabler-search"
                density="compact"
                clearable
              />
            </VCol>
            <VCol cols="12" md="3">
              <VSelect
                v-model="filterStatut"
                :items="[{ title: 'Tous statuts', value: '' }, ...statutOptions]"
                label="Statut"
                density="compact"
                clearable
              />
            </VCol>
            <VCol cols="12" md="4">
              <VSelect
                v-model="filterAvis"
                :items="[{ title: 'Tous les avis', value: '' }, ...avisList]"
                label="Avis"
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
                  <p class="text-caption text-medium-emphasis mb-2">Filtrer par date de dépouillement</p>
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
            :items="depouillements"
            :items-length="total"
            :loading="isLoading"
            class="elevation-0"
          >
            <template #item.avis="{ item }">
              <div>
                <span class="text-caption font-weight-medium">{{ item.avis?.reference }}</span>
                <br />
                <span class="text-caption text-medium-emphasis">{{ item.avis?.objet?.substring(0, 40) }}</span>
              </div>
            </template>
            <template #item.nb_offres="{ item }">
              <VChip size="x-small" color="info" variant="tonal">
                {{ item.resultats?.length ?? 0 }} offre(s)
              </VChip>
            </template>
            <template #item.statut="{ item }">
              <VChip :color="statutColor(item.statut)" size="small">{{ statutLabel(item.statut) }}</VChip>
            </template>
            <template #item.date_depouillement="{ item }">
              <span class="text-caption">{{ formatDate(item.date_depouillement) }}</span>
            </template>

            <template #item.actions="{ item }">
              <VBtn icon variant="text" size="small" color="secondary" @click="openDetails(item)">
                <VIcon icon="tabler-eye" />
                <VTooltip activator="parent">Détails</VTooltip>
              </VBtn>
              <VBtn icon variant="text" size="small" color="primary" @click="openEdit(item)">
                <VIcon icon="tabler-edit" />
                <VTooltip activator="parent">Modifier</VTooltip>
              </VBtn>
              <VBtn v-if="item.statut === 'draft'" icon variant="text" size="small" color="info" @click="doAction('submit', item)">
                <VIcon icon="tabler-send" />
                <VTooltip activator="parent">Soumettre</VTooltip>
              </VBtn>
              <VBtn v-if="item.statut === 'submitted'" icon variant="text" size="small" color="success" @click="doAction('approve', item)">
                <VIcon icon="tabler-check" />
                <VTooltip activator="parent">Approuver</VTooltip>
              </VBtn>
              <VBtn v-if="item.statut === 'submitted'" icon variant="text" size="small" color="error" @click="openReject(item)">
                <VIcon icon="tabler-x" />
                <VTooltip activator="parent">Rejeter</VTooltip>
              </VBtn>
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
    <VCard :title="isEditing ? 'Modifier le dépouillement' : 'Nouveau dépouillement'">
      <VCardText>
        <VRow>
          <VCol cols="12" md="4">
            <VTextField v-model="form.reference" label="Référence *" placeholder="DEP-2026-001" />
          </VCol>
          <VCol cols="12" md="8">
            <VSelect
              v-model="form.avis_id"
              :items="avisList"
              label="Avis de référence *"
              clearable
            />
          </VCol>
          <VCol cols="12" md="4">
            <VTextField v-model="form.date_depouillement" label="Date du dépouillement *" type="date" />
          </VCol>
          <VCol cols="12" md="8">
            <VTextField v-model="form.lieu" label="Lieu" placeholder="Salle de réunion, Siège CANAM..." />
          </VCol>
          <VCol cols="12">
            <VTextarea v-model="form.observations" label="Observations" rows="2" />
          </VCol>
        </VRow>

        <!-- Résultats des offres -->
        <VDivider class="my-4" />
        <div class="d-flex align-center mb-3">
          <VIcon icon="tabler-trophy" class="me-2" color="primary" />
          <span class="text-subtitle-1 font-weight-bold">Résultats des offres</span>
          <VSpacer />
          <VBtn prepend-icon="tabler-plus" size="small" variant="tonal" color="primary" @click="addResultat">
            Ajouter une offre
          </VBtn>
        </div>

        <div v-if="form.resultats.length === 0" class="text-center py-4 text-medium-emphasis">
          <p class="text-body-2">Aucune offre enregistrée. Cliquez sur "Ajouter une offre" pour commencer.</p>
        </div>

        <div
          v-for="(r, index) in form.resultats"
          :key="index"
          class="mb-3 pa-3 border rounded"
          :class="r.retenu ? 'border-success' : ''"
        >
          <div class="d-flex align-center mb-2">
            <VChip :color="r.retenu ? 'success' : 'default'" size="small" class="me-2">
              Rang {{ r.rang }}
            </VChip>
            <VChip v-if="r.retenu" color="success" size="x-small" prepend-icon="tabler-star">
              Attributaire
            </VChip>
            <VSpacer />
            <VBtn icon variant="text" size="small" color="error" @click="removeResultat(index)">
              <VIcon icon="tabler-x" />
            </VBtn>
          </div>

          <VRow>
            <VCol cols="12" md="5">
              <VSelect
                v-if="avisFournisseurs.length > 0"
                v-model="r.fournisseur_id"
                :items="avisFournisseurs.map((f: any) => ({ title: f.raison_sociale, value: f.id }))"
                label="Fournisseur"
                density="compact"
                clearable
                @update:model-value="(val) => onFournisseurSelect(r, val)"
              />
              <VTextField
                v-else
                v-model="r.fournisseur_nom"
                label="Fournisseur (nom libre)"
                density="compact"
                placeholder="Nom du soumissionnaire"
              />
            </VCol>
            <VCol cols="12" md="4">
              <VTextField v-model.number="r.montant" label="Montant offre (CFA)" type="number" density="compact" />
            </VCol>
            <VCol cols="12" md="3">
              <VTextField v-model.number="r.rang" label="Rang" type="number" density="compact" min="1" />
            </VCol>
            <VCol cols="12" md="3">
              <VTextField v-model.number="r.note_technique" label="Note technique /100" type="number" density="compact" min="0" max="100" />
            </VCol>
            <VCol cols="12" md="3">
              <VTextField v-model.number="r.note_financiere" label="Note financière /100" type="number" density="compact" min="0" max="100" />
            </VCol>
            <VCol cols="12" md="4">
              <VTextField v-model="r.observations" label="Observations" density="compact" />
            </VCol>
            <VCol cols="12" md="2" class="d-flex align-center">
              <VCheckbox
                v-model="r.retenu"
                label="Retenu"
                color="success"
                density="compact"
                @change="r.retenu && form.resultats.forEach((x, i) => { if (i !== index) x.retenu = false })"
              />
            </VCol>
          </VRow>
        </div>
      </VCardText>

      <VDivider />
      <VCardActions class="justify-end pa-4">
        <VBtn variant="tonal" @click="dialog = false">Annuler</VBtn>
        <VBtn color="primary" prepend-icon="tabler-device-floppy" @click="save">
          {{ isEditing ? 'Enregistrer' : 'Créer' }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- ─── Dialog Rejet ─── -->
  <VDialog v-model="rejectDialog" max-width="500">
    <VCard>
      <VCardTitle class="d-flex align-center gap-2 pa-4">
        <VIcon icon="tabler-x" color="error" />
        Rejeter le dépouillement
      </VCardTitle>
      <VCardText>
        <p class="mb-3">Vous allez rejeter le dépouillement <strong>{{ selectedItem?.reference }}</strong>.</p>
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
        Voulez-vous vraiment supprimer le dépouillement <strong>{{ selectedItem?.reference }}</strong> ?
      </VCardText>
      <VCardActions class="justify-end pa-4">
        <VBtn variant="tonal" @click="deleteDialog = false">Annuler</VBtn>
        <VBtn color="error" @click="confirmDelete">Supprimer</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- ─── Dialog Détails ─── -->
  <VDialog v-model="detailsDialog" max-width="800" scrollable>
    <VCard v-if="detailsItem">
      <VCardTitle class="d-flex align-center gap-2 pa-4">
        <VIcon icon="tabler-clipboard-list" color="primary" />
        {{ detailsItem.reference }}
        <VSpacer />
        <VChip :color="statutColor(detailsItem.statut)" size="small">{{ statutLabel(detailsItem.statut) }}</VChip>
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-4">

        <!-- Informations générales -->
        <p class="text-subtitle-2 font-weight-bold mb-3">Informations générales</p>
        <VRow dense>
          <VCol cols="6" md="3">
            <p class="text-caption text-medium-emphasis">Date</p>
            <p class="text-body-2 mb-2">{{ formatDate(detailsItem.date_depouillement) }}</p>
          </VCol>
          <VCol cols="6" md="3">
            <p class="text-caption text-medium-emphasis">Lieu</p>
            <p class="text-body-2 mb-2">{{ detailsItem.lieu || '-' }}</p>
          </VCol>
          <VCol cols="12" md="6">
            <p class="text-caption text-medium-emphasis">Avis de référence</p>
            <p class="text-body-2 mb-2">
              <span class="font-weight-medium">{{ detailsItem.avis?.reference }}</span>
              <span v-if="detailsItem.avis?.objet" class="text-medium-emphasis"> — {{ detailsItem.avis.objet }}</span>
            </p>
          </VCol>
          <VCol v-if="detailsItem.motif_rejet" cols="12">
            <VAlert type="error" variant="tonal" density="compact" class="mb-2">
              <strong>Motif du rejet :</strong> {{ detailsItem.motif_rejet }}
            </VAlert>
          </VCol>
          <VCol v-if="detailsItem.observations" cols="12">
            <p class="text-caption text-medium-emphasis">Observations</p>
            <p class="text-body-2">{{ detailsItem.observations }}</p>
          </VCol>
        </VRow>

        <!-- Résultats des offres -->
        <template v-if="detailsItem.resultats?.length">
          <VDivider class="my-3" />
          <p class="text-subtitle-2 font-weight-bold mb-3">
            Résultats des offres
            <VChip size="x-small" color="primary" class="ms-1">{{ detailsItem.resultats.length }}</VChip>
          </p>
          <VTable density="compact">
            <thead>
              <tr>
                <th>Rang</th>
                <th>Fournisseur</th>
                <th class="text-right">Montant</th>
                <th class="text-center">Note tech.</th>
                <th class="text-center">Note fin.</th>
                <th class="text-center">Retenu</th>
                <th>Observations</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="r in detailsItem.resultats"
                :key="r.id"
                :class="r.retenu ? 'bg-success-subtle' : ''"
              >
                <td>
                  <VChip :color="r.retenu ? 'success' : 'default'" size="x-small">{{ r.rang }}</VChip>
                </td>
                <td>
                  <span class="text-body-2">{{ r.fournisseur?.raison_sociale || r.fournisseur_nom || '-' }}</span>
                </td>
                <td class="text-right text-body-2">{{ formatMontant(r.montant) }}</td>
                <td class="text-center text-body-2">{{ r.note_technique ?? '-' }}</td>
                <td class="text-center text-body-2">{{ r.note_financiere ?? '-' }}</td>
                <td class="text-center">
                  <VIcon v-if="r.retenu" icon="tabler-star-filled" color="success" size="18" />
                  <VIcon v-else icon="tabler-minus" color="default" size="16" />
                </td>
                <td class="text-caption text-medium-emphasis">{{ r.observations || '-' }}</td>
              </tr>
            </tbody>
          </VTable>
        </template>
        <div v-else class="text-center py-4 text-medium-emphasis">
          <p class="text-body-2">Aucune offre enregistrée.</p>
        </div>

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
