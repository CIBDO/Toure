<script setup lang="ts">
import { useApi } from '@/composables/useApi'

definePage({ meta: { title: 'Procès-Verbaux' } })

const snackbar = ref({ show: false, text: '', color: 'success' })
const dialog = ref(false)
const deleteDialog = ref(false)
const rejectDialog = ref(false)
const uploadDialog = ref(false)
const isEditing = ref(false)
const selectedItem = ref<any>(null)
const motifRejet = ref('')
const uploadFiles = ref<File[]>([])
const isUploading = ref(false)

const selectedUploadFile = computed(() => uploadFiles.value?.[0] ?? null)

// Filtres
const searchQuery = ref('')
const filterStatut = ref('')
const filterType = ref('')
const filterDateFrom = ref('')
const filterDateTo = ref('')
const showDateFilters = ref(false)

const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref([{ key: 'created_at', order: 'desc' }])

const pvs = ref<any[]>([])
const total = ref(0)
const isLoading = ref(false)

const emptyForm = () => ({
  reference: '',
  depouillement_id: null as number | null,
  avis_id: null as number | null,
  fournisseur_attributaire_id: null as number | null,
  date_pv: '',
  type_pv: 'attribution',
  montant_retenu: null as number | null,
  nb_soumission: 0,
  contenu: '',
  statut: 'draft',
  observations: '',
  motif_rejet: '',
})

const form = ref(emptyForm())

const headers = [
  { title: 'Référence', key: 'reference', sortable: true },
  { title: 'Avis', key: 'avis', sortable: false },
  { title: 'Type', key: 'type_pv', sortable: true },
  { title: 'Attributaire', key: 'fournisseur_attributaire', sortable: false },
  { title: 'Montant retenu', key: 'montant_retenu', sortable: true },
  { title: 'Nb soum.', key: 'nb_soumission', sortable: true },
  { title: 'Date PV', key: 'date_pv', sortable: true },
  { title: 'Statut', key: 'statut', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, width: '200px' },
]

const typeOptions = [
  { title: 'Attribution', value: 'attribution' },
  { title: 'Infructueux', value: 'infructueux' },
  { title: 'Annulation', value: 'annulation' },
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

const typeColor = (t: string) => ({
  attribution: 'success', infructueux: 'warning', annulation: 'error',
}[t] || 'default')

// Listes de référence
const { data: avisData } = await useApi<any>('/avis?itemsPerPage=-1').json()
const avisList = computed(() =>
  avisData.value?.data?.map((a: any) => ({
    title: `${a.reference} — ${a.objet?.substring(0, 50)}`,
    value: a.id,
  })) ?? [],
)

const { data: fournisseursData } = await useApi<any>('/fournisseurs?itemsPerPage=-1').json()
const fournisseursList = computed(() =>
  fournisseursData.value?.data?.map((f: any) => ({
    title: f.raison_sociale,
    value: f.id,
  })) ?? [],
)

const { data: depData } = await useApi<any>('/depouillements?itemsPerPage=-1').json()
const depList = computed(() =>
  depData.value?.data?.map((d: any) => ({
    title: `${d.reference} (${d.avis?.reference ?? '?'})`,
    value: d.id,
  })) ?? [],
)

const loadData = async () => {
  isLoading.value = true
  try {
    const params = new URLSearchParams()
    if (searchQuery.value) params.set('q', searchQuery.value)
    if (filterStatut.value) params.set('statut', filterStatut.value)
    if (filterType.value) params.set('type_pv', filterType.value)
    if (filterDateFrom.value) params.set('date_from', filterDateFrom.value)
    if (filterDateTo.value) params.set('date_to', filterDateTo.value)
    params.set('itemsPerPage', itemsPerPage.value.toString())
    params.set('page', page.value.toString())
    params.set('sortBy', sortBy.value[0]?.key ?? 'created_at')
    params.set('sortDesc', (sortBy.value[0]?.order === 'desc').toString())

    const { data } = await useApi(`/pvs?${params}`).json()
    pvs.value = data.value?.data ?? []
    total.value = data.value?.total ?? 0
  }
  finally {
    isLoading.value = false
  }
}

watch(
  [searchQuery, filterStatut, filterType, filterDateFrom, filterDateTo, itemsPerPage, page, sortBy],
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
  const { data } = await useApi(`/pvs/${item.id}`).json()
  form.value = { ...emptyForm(), ...data.value }
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

const openUpload = (item: any) => {
  selectedItem.value = item
  uploadFiles.value = []
  uploadDialog.value = true
}

const save = async () => {
  try {
    if (isEditing.value)
      await useApi(`/pvs/${selectedItem.value.id}`).put(form.value).json()
    else
      await useApi('/pvs').post(form.value).json()
    dialog.value = false
    snackbar.value = { show: true, text: `PV ${isEditing.value ? 'modifié' : 'créé'} avec succès`, color: 'success' }
    await loadData()
  }
  catch (e: any) {
    snackbar.value = { show: true, text: e?.data?.message || 'Une erreur est survenue', color: 'error' }
  }
}

const confirmDelete = async () => {
  try {
    await useApi(`/pvs/${selectedItem.value.id}`).delete().json()
    deleteDialog.value = false
    snackbar.value = { show: true, text: 'PV supprimé', color: 'success' }
    await loadData()
  }
  catch {
    snackbar.value = { show: true, text: 'Impossible de supprimer', color: 'error' }
  }
}

const doAction = async (action: 'submit' | 'approve', item: any) => {
  try {
    await useApi(`/pvs/${item.id}/${action}`).post({}).json()
    const labels = { submit: 'soumis', approve: 'approuvé' }
    snackbar.value = { show: true, text: `PV ${labels[action]} avec succès`, color: 'success' }
    await loadData()
  }
  catch {
    snackbar.value = { show: true, text: 'Erreur lors de l\'action', color: 'error' }
  }
}

const confirmReject = async () => {
  try {
    await useApi(`/pvs/${selectedItem.value.id}/reject`).post({ motif_rejet: motifRejet.value }).json()
    rejectDialog.value = false
    snackbar.value = { show: true, text: 'PV rejeté', color: 'warning' }
    await loadData()
  }
  catch {
    snackbar.value = { show: true, text: 'Erreur lors du rejet', color: 'error' }
  }
}

const downloadPdf = (item: any) => {
  window.open(`${import.meta.env.VITE_API_BASE_URL}/pvs/${item.id}/pdf`, '_blank')
}

const downloadSigne = async (item: any) => {
  try {
    const baseUrl = (import.meta.env.VITE_API_BASE_URL && String(import.meta.env.VITE_API_BASE_URL)) || '/api'
    const token = useCookie('accessToken').value
    const response = await fetch(`${baseUrl}/pvs/${item.id}/download-signe`, {
      headers: {
        Accept: 'application/pdf',
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
    })
    if (!response.ok) throw new Error('Fichier introuvable')
    const blob = await response.blob()
    const a = document.createElement('a')
    a.href = URL.createObjectURL(blob)
    a.download = `pv_signe_${item.reference || item.id}.pdf`
    a.click()
    URL.revokeObjectURL(a.href)
  }
  catch {
    snackbar.value = { show: true, text: 'Téléchargement impossible', color: 'error' }
  }
}

const submitUpload = async () => {
  const file = selectedUploadFile.value
  if (!file) return
  isUploading.value = true
  try {
    const formData = new FormData()
    formData.append('fichier', file)

    const baseUrl = (import.meta.env.VITE_API_BASE_URL && String(import.meta.env.VITE_API_BASE_URL)) || '/api'
    const token = useCookie('accessToken').value
    const response = await fetch(`${baseUrl}/pvs/${selectedItem.value.id}/upload-signe`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
      body: formData,
    })

    if (!response.ok) {
      const err = await response.json().catch(() => ({}))
      const message = err?.errors?.fichier?.[0] || err?.message || 'Erreur lors de l\'upload'
      throw new Error(message)
    }

    uploadDialog.value = false
    snackbar.value = { show: true, text: 'PV signé uploadé avec succès', color: 'success' }
    await loadData()
  }
  catch (e: any) {
    snackbar.value = { show: true, text: e?.message || 'Erreur lors de l\'upload', color: 'error' }
  }
  finally {
    isUploading.value = false
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
  const { data } = await useApi(`/pvs/${item.id}`).json()
  detailsItem.value = data.value
  detailsDialog.value = true
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardTitle class="d-flex align-center pa-4">
          <VIcon icon="tabler-file-check" class="me-2" />
          Procès-Verbaux (PV)
          <VSpacer />
          <VBtn prepend-icon="tabler-plus" color="primary" @click="openCreate">Nouveau PV</VBtn>
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
            <VCol cols="12" md="3">
              <VSelect
                v-model="filterType"
                :items="[{ title: 'Tous types', value: '' }, ...typeOptions]"
                label="Type"
                density="compact"
                clearable
              />
            </VCol>
            <VCol cols="12" md="3" class="d-flex align-center">
              <VBtn
                variant="tonal"
                :color="showDateFilters ? 'primary' : 'default'"
                prepend-icon="tabler-calendar-search"
                size="small"
                @click="showDateFilters = !showDateFilters"
              >
                Filtrer par date
              </VBtn>
            </VCol>
          </VRow>

          <VExpandTransition>
            <VRow v-if="showDateFilters" class="mb-3">
              <VCol cols="12">
                <VCard variant="tonal" color="info" class="pa-3">
                  <p class="text-caption text-medium-emphasis mb-2">Filtrer par date du PV</p>
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
            :items="pvs"
            :items-length="total"
            :loading="isLoading"
            class="elevation-0"
          >
            <template #item.avis="{ item }">
              <span class="text-caption">{{ item.avis?.reference ?? '-' }}</span>
            </template>
            <template #item.type_pv="{ item }">
              <VChip :color="typeColor(item.type_pv)" size="x-small">{{ item.type_pv }}</VChip>
            </template>
            <template #item.fournisseur_attributaire="{ item }">
              <span class="text-caption">{{ item.fournisseur_attributaire?.raison_sociale ?? '-' }}</span>
            </template>
            <template #item.montant_retenu="{ item }">
              <span class="text-caption font-weight-medium">{{ formatMontant(item.montant_retenu) }}</span>
            </template>
            <template #item.nb_soumission="{ item }">
              <VChip size="x-small" color="info" variant="tonal">{{ item.nb_soumission ?? 0 }}</VChip>
            </template>
            <template #item.statut="{ item }">
              <div class="d-flex align-center gap-1">
                <VChip :color="statutColor(item.statut)" size="small">{{ statutLabel(item.statut) }}</VChip>
                <VIcon v-if="item.fichier_pv_signe" icon="tabler-paperclip" size="14" color="success" title="PV signé disponible" />
              </div>
            </template>
            <template #item.date_pv="{ item }">
              <span class="text-caption">{{ formatDate(item.date_pv) }}</span>
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

              <!-- Soumettre -->
              <VBtn v-if="item.statut === 'draft'" icon variant="text" size="small" color="info" @click="doAction('submit', item)">
                <VIcon icon="tabler-send" />
                <VTooltip activator="parent">Soumettre</VTooltip>
              </VBtn>

              <!-- Approuver -->
              <VBtn v-if="item.statut === 'submitted'" icon variant="text" size="small" color="success" @click="doAction('approve', item)">
                <VIcon icon="tabler-check" />
                <VTooltip activator="parent">Approuver</VTooltip>
              </VBtn>

              <!-- Rejeter -->
              <VBtn v-if="item.statut === 'submitted'" icon variant="text" size="small" color="error" @click="openReject(item)">
                <VIcon icon="tabler-x" />
                <VTooltip activator="parent">Rejeter</VTooltip>
              </VBtn>

              <!-- Générer PDF -->
              <!-- <VBtn icon variant="text" size="small" color="secondary" @click="downloadPdf(item)">
                <VIcon icon="tabler-file-type-pdf" />
                <VTooltip activator="parent">Générer PDF</VTooltip>
              </VBtn> -->

              <!-- Upload PV signé -->
              <VBtn v-if="['approved', 'submitted'].includes(item.statut)" icon variant="text" size="small" color="teal" @click="openUpload(item)">
                <VIcon icon="tabler-upload" />
                <VTooltip activator="parent">Uploader PV signé</VTooltip>
              </VBtn>

              <!-- Télécharger PV signé -->
              <VBtn v-if="item.fichier_pv_signe" icon variant="text" size="small" color="success" @click="downloadSigne(item)">
                <VIcon icon="tabler-download" />
                <VTooltip activator="parent">Télécharger PV signé</VTooltip>
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
  <VDialog v-model="dialog" max-width="850" scrollable>
    <VCard :title="isEditing ? 'Modifier le PV' : 'Nouveau Procès-Verbal'">
      <VCardText>
        <VRow>
          <VCol cols="12" md="4">
            <VTextField v-model="form.reference" label="Référence *" placeholder="PV-2026-001" />
          </VCol>
          <VCol cols="12" md="4">
            <VSelect v-model="form.type_pv" :items="typeOptions" label="Type de PV *" />
          </VCol>
          <VCol cols="12" md="4">
            <VTextField v-model="form.date_pv" label="Date du PV *" type="date" />
          </VCol>
          <VCol cols="12" md="6">
            <VSelect v-model="form.avis_id" :items="avisList" label="Avis de référence *" />
          </VCol>
          <VCol cols="12" md="6">
            <VSelect v-model="form.depouillement_id" :items="depList" label="Dépouillement lié" clearable />
          </VCol>
          <VCol cols="12" md="4">
            <VTextField
              v-model.number="form.nb_soumission"
              label="Nombre de soumissions"
              type="number"
              min="0"
              hint="Nombre total d'offres reçues"
              persistent-hint
            />
          </VCol>
          <VCol v-if="form.type_pv === 'attribution'" cols="12" md="4">
            <VSelect
              v-model="form.fournisseur_attributaire_id"
              :items="fournisseursList"
              label="Fournisseur attributaire"
              clearable
            />
          </VCol>
          <VCol v-if="form.type_pv === 'attribution'" cols="12" md="4">
            <VTextField
              v-model.number="form.montant_retenu"
              label="Montant retenu (CFA)"
              type="number"
              min="0"
            />
          </VCol>
          <VCol cols="12">
            <VTextarea v-model="form.contenu" label="Contenu / Décision du PV" rows="5" placeholder="Rédigez ici le contenu du procès-verbal..." />
          </VCol>
          <VCol cols="12">
            <VTextarea v-model="form.observations" label="Observations" rows="2" />
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />
      <VCardActions class="justify-end pa-4">
        <VBtn variant="tonal" @click="dialog = false">Annuler</VBtn>
        <VBtn color="primary" prepend-icon="tabler-device-floppy" @click="save">
          {{ isEditing ? 'Enregistrer' : 'Créer le PV' }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- ─── Dialog Upload PV signé ─── -->
  <VDialog v-model="uploadDialog" max-width="500">
    <VCard>
      <VCardTitle class="d-flex align-center gap-2 pa-4">
        <VIcon icon="tabler-upload" color="teal" />
        Uploader le PV signé
      </VCardTitle>
      <VCardText>
        <p class="mb-3 text-body-2">
          PV : <strong>{{ selectedItem?.reference }}</strong>
          <br />
          <span class="text-caption text-medium-emphasis">Format accepté : PDF uniquement, max 10 Mo</span>
        </p>
        <VFileInput
          v-model="uploadFiles"
          label="Sélectionner le fichier PDF signé"
          accept="application/pdf,.pdf"
          prepend-icon="tabler-file-type-pdf"
          show-size
          clearable
        />
        <VAlert v-if="selectedItem?.fichier_pv_signe" type="warning" variant="tonal" density="compact" class="mt-2">
          Un PV signé existe déjà. Il sera remplacé.
        </VAlert>
      </VCardText>
      <VCardActions class="justify-end pa-4">
        <VBtn variant="tonal" @click="uploadDialog = false">Annuler</VBtn>
        <VBtn
          color="teal"
          prepend-icon="tabler-upload"
          :loading="isUploading"
          :disabled="!selectedUploadFile"
          @click="submitUpload"
        >
          Uploader
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- ─── Dialog Rejet ─── -->
  <VDialog v-model="rejectDialog" max-width="500">
    <VCard>
      <VCardTitle class="d-flex align-center gap-2 pa-4">
        <VIcon icon="tabler-x" color="error" />
        Rejeter le PV
      </VCardTitle>
      <VCardText>
        <p class="mb-3">Vous allez rejeter le PV <strong>{{ selectedItem?.reference }}</strong>.</p>
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
        Voulez-vous vraiment supprimer le PV <strong>{{ selectedItem?.reference }}</strong> ?
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
        <VIcon icon="tabler-file-check" color="primary" />
        {{ detailsItem.reference }}
        <VSpacer />
        <VChip :color="typeColor(detailsItem.type_pv)" size="small" class="me-2">{{ detailsItem.type_pv }}</VChip>
        <VChip :color="statutColor(detailsItem.statut)" size="small">{{ statutLabel(detailsItem.statut) }}</VChip>
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-4">

        <!-- Informations générales -->
        <p class="text-subtitle-2 font-weight-bold mb-3">Informations générales</p>
        <VRow dense>
          <VCol cols="6" md="3">
            <p class="text-caption text-medium-emphasis">Date du PV</p>
            <p class="text-body-2 mb-2">{{ formatDate(detailsItem.date_pv) }}</p>
          </VCol>
          <VCol cols="6" md="3">
            <p class="text-caption text-medium-emphasis">Nb soumissions</p>
            <p class="text-body-2 mb-2">{{ detailsItem.nb_soumission ?? '-' }}</p>
          </VCol>
          <VCol cols="12" md="6">
            <p class="text-caption text-medium-emphasis">Avis de référence</p>
            <p class="text-body-2 mb-2">
              <span class="font-weight-medium">{{ detailsItem.avis?.reference ?? '-' }}</span>
              <span v-if="detailsItem.avis?.objet" class="text-medium-emphasis"> — {{ detailsItem.avis.objet }}</span>
            </p>
          </VCol>
          <VCol v-if="detailsItem.fournisseur_attributaire" cols="12" md="6">
            <p class="text-caption text-medium-emphasis">Attributaire</p>
            <p class="text-body-2 mb-2 font-weight-medium">{{ detailsItem.fournisseur_attributaire.raison_sociale }}</p>
          </VCol>
          <VCol v-if="detailsItem.montant_retenu" cols="12" md="6">
            <p class="text-caption text-medium-emphasis">Montant retenu</p>
            <p class="text-body-2 mb-2 font-weight-medium">{{ formatMontant(detailsItem.montant_retenu) }}</p>
          </VCol>
          <VCol v-if="detailsItem.depouillement" cols="12" md="6">
            <p class="text-caption text-medium-emphasis">Dépouillement lié</p>
            <p class="text-body-2 mb-2">{{ detailsItem.depouillement.reference }}</p>
          </VCol>
          <VCol v-if="detailsItem.fichier_pv_signe" cols="12" md="6">
            <p class="text-caption text-medium-emphasis">PV signé</p>
            <VBtn size="x-small" color="success" variant="tonal" prepend-icon="tabler-download" @click="downloadSigne(detailsItem)">
              Télécharger
            </VBtn>
          </VCol>
        </VRow>

        <!-- Contenu -->
        <template v-if="detailsItem.contenu">
          <VDivider class="my-3" />
          <p class="text-subtitle-2 font-weight-bold mb-2">Contenu / Décision</p>
          <VCard variant="tonal" color="grey" class="pa-3">
            <p class="text-body-2" style="white-space: pre-wrap;">{{ detailsItem.contenu }}</p>
          </VCard>
        </template>

        <!-- Observations -->
        <template v-if="detailsItem.observations">
          <VDivider class="my-3" />
          <p class="text-subtitle-2 font-weight-bold mb-2">Observations</p>
          <p class="text-body-2">{{ detailsItem.observations }}</p>
        </template>

        <!-- Motif rejet -->
        <template v-if="detailsItem.motif_rejet">
          <VDivider class="my-3" />
          <VAlert type="error" variant="tonal" density="compact">
            <strong>Motif du rejet :</strong> {{ detailsItem.motif_rejet }}
          </VAlert>
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
