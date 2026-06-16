<script setup lang="ts">
import { documentService, GED_ENTITY_OPTIONS, GED_CATEGORIES, type DocumentRecord, type DocumentFilters } from '@/services/documentService'
import { useAbility } from '@/plugins/casl/composables/useAbility'
import { $api } from '@/utils/api'

definePage({ meta: { title: 'GED — Documents', action: 'view', subject: 'Document' } })

const ability = useAbility()
const can = (action: string, subject: string) => ability.can(action, subject)

const snackbar = ref({ show: false, text: '', color: 'success' })
const isLoading = ref(false)
const documents = ref<DocumentRecord[]>([])
const total = ref(0)
const page = ref(1)
const perPage = ref(15)

const filterEntity = ref('')
const filterEntityId = ref<number | string>('')
const filterCategory = ref('')
const filterQ = ref('')
const filterFrom = ref('')
const filterTo = ref('')

const uploadDialog = ref(false)
const uploadForm = ref({
  documentable_type: 'contrats',
  documentable_id: null as number | null,
  category: 'contrat_signe',
  title: '',
  description: '',
  date_document: '',
  tags: [] as string[],
  file: null as File | File[] | null,
})
const uploadEntityOptions = ref<{ id: number; reference?: string; objet?: string; numero?: string }[]>([])

const entityOptionsForSelect = ref<{ id: number; reference?: string; objet?: string; numero?: string }[]>([])
const loadEntityOptions = async () => {
  if (!filterEntity.value) {
    entityOptionsForSelect.value = []
    return
  }
  try {
    const endpoints: Record<string, string> = {
      avis: 'avis',
      pv: 'pvs',
      contrats: 'contrats',
      engagements: 'engagements',
      payments: 'paiements',
    }
    const endpoint = endpoints[filterEntity.value]
    if (!endpoint) return
    const res = await $api<{ data?: any[] }>(`/${endpoint}?itemsPerPage=-1`)
    entityOptionsForSelect.value = Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : [])
  }
  catch {
    entityOptionsForSelect.value = []
  }
}

watch(filterEntity, () => {
  filterEntityId.value = ''
  loadEntityOptions()
})

const loadUploadEntityOptions = async () => {
  const type = uploadForm.value.documentable_type
  if (!type) return
  try {
    const endpoints: Record<string, string> = {
      avis: 'avis',
      pv: 'pvs',
      contrats: 'contrats',
      engagements: 'engagements',
      payments: 'paiements',
    }
    const endpoint = endpoints[type]
    if (!endpoint) return
    const res = await $api<{ data?: any[] }>(`/${endpoint}?itemsPerPage=-1`)
    uploadEntityOptions.value = Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : [])
  }
  catch {
    uploadEntityOptions.value = []
  }
}

const categoryOptions = computed(() =>
  Object.entries(GED_CATEGORIES).map(([value, title]) => ({ value, title })))

watch(uploadForm, (f) => {
  if (f.documentable_type && uploadDialog.value) loadUploadEntityOptions()
}, { deep: true })
watch(uploadDialog, (open) => {
  if (open) loadUploadEntityOptions()
})

const fetchDocuments = async () => {
  isLoading.value = true
  try {
    const filters: DocumentFilters = {
      page: page.value,
      per_page: perPage.value,
      documentable_type: filterEntity.value || undefined,
      documentable_id: filterEntityId.value ? Number(filterEntityId.value) : undefined,
      category: filterCategory.value || undefined,
      q: filterQ.value || undefined,
      from: filterFrom.value || undefined,
      to: filterTo.value || undefined,
    }
    const res = await documentService.list(filters)
    documents.value = res.data ?? []
    total.value = res.total ?? 0
  }
  catch (e: any) {
    snackbar.value = { show: true, text: e?.message || 'Erreur chargement', color: 'error' }
  }
  finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchDocuments()
  if (filterEntity.value) loadEntityOptions()
})

watch([page, perPage, filterEntity, filterEntityId, filterCategory, filterQ, filterFrom, filterTo], fetchDocuments)

const entityLabel = (doc: DocumentRecord) => {
  const d = doc.documentable
  if (!d) return doc.documentable_type?.split('\\').pop() ?? '-'
  return (d.reference ?? d.numero ?? d.objet ?? `#${doc.documentable_id}`) as string
}

const formatDate = (d: string | undefined) => (d ? new Date(d).toLocaleDateString('fr-FR') : '-')
const formatSize = (bytes: number) => {
  if (bytes < 1024) return `${bytes} o`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} Ko`
  return `${(bytes / (1024 * 1024)).toFixed(1)} Mo`
}

const editDialog = ref(false)
const previewDialog = ref(false)
const deleteDialog = ref(false)
const selectedDoc = ref<DocumentRecord | null>(null)
const previewBlobUrl = ref<string | null>(null)
const isUploading = ref(false)
const isSavingEdit = ref(false)

const editForm = ref({
  title: '',
  category: 'contrat_signe',
  description: '',
  date_document: '',
  tags: [] as string[],
})

const openUpload = () => {
  uploadForm.value = {
    documentable_type: 'contrats',
    documentable_id: null,
    category: 'contrat_signe',
    title: '',
    description: '',
    date_document: '',
    tags: [],
    file: null,
  }
  uploadDialog.value = true
}

const doUpload = async () => {
  const f = uploadForm.value
  const file = Array.isArray(f.file) ? f.file[0] : f.file
  if (!file || !f.title || !f.category || !f.documentable_type || !f.documentable_id) {
    snackbar.value = { show: true, text: 'Titre, catégorie, entité et fichier obligatoires', color: 'error' }
    return
  }
  isUploading.value = true
  try {
    const form = new FormData()
    form.append('documentable_type', f.documentable_type)
    form.append('documentable_id', String(f.documentable_id))
    form.append('category', f.category)
    form.append('title', f.title)
    if (f.description) form.append('description', f.description)
    if (f.date_document) form.append('date_document', f.date_document)
    if (f.tags?.length) form.append('tags', JSON.stringify(f.tags))
    form.append('file', file)
    await documentService.upload(form)
    snackbar.value = { show: true, text: 'Document ajouté', color: 'success' }
    uploadDialog.value = false
    fetchDocuments()
  }
  catch (e: any) {
    snackbar.value = { show: true, text: e?.message || 'Erreur upload', color: 'error' }
  }
  finally {
    isUploading.value = false
  }
}

const openEdit = (doc: DocumentRecord) => {
  selectedDoc.value = doc
  editForm.value = {
    title: doc.title,
    category: doc.category,
    description: doc.description ?? '',
    date_document: doc.date_document ?? '',
    tags: doc.tags ?? [],
  }
  editDialog.value = true
}

const saveEdit = async () => {
  if (!selectedDoc.value) return
  isSavingEdit.value = true
  try {
    await documentService.update(selectedDoc.value.id, editForm.value)
    snackbar.value = { show: true, text: 'Métadonnées mises à jour', color: 'success' }
    editDialog.value = false
    fetchDocuments()
  }
  catch (e: any) {
    snackbar.value = { show: true, text: e?.message || 'Erreur', color: 'error' }
  }
  finally {
    isSavingEdit.value = false
  }
}

const openPreview = async (doc: DocumentRecord) => {
  selectedDoc.value = doc
  previewBlobUrl.value = null
  previewDialog.value = true
  try {
    previewBlobUrl.value = await documentService.getPreviewBlobUrl(doc.id)
  }
  catch {
    snackbar.value = { show: true, text: 'Aperçu non disponible', color: 'warning' }
  }
}

const closePreview = () => {
  if (previewBlobUrl.value) URL.revokeObjectURL(previewBlobUrl.value)
  previewBlobUrl.value = null
  previewDialog.value = false
}

const isPreviewable = (mime: string) => {
  const ok = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp']
  return ok.some(t => mime?.toLowerCase().includes(t))
}

const doDownload = async (doc: DocumentRecord) => {
  try {
    const blob = await documentService.getDownloadBlob(doc.id)
    const a = document.createElement('a')
    a.href = URL.createObjectURL(blob)
    a.download = doc.original_name || 'document'
    a.click()
    URL.revokeObjectURL(a.href)
    snackbar.value = { show: true, text: 'Téléchargement démarré', color: 'success' }
  }
  catch {
    snackbar.value = { show: true, text: 'Téléchargement impossible', color: 'error' }
  }
}

const openDelete = (doc: DocumentRecord) => {
  selectedDoc.value = doc
  deleteDialog.value = true
}

const confirmDelete = async () => {
  if (!selectedDoc.value) return
  try {
    await documentService.remove(selectedDoc.value.id)
    deleteDialog.value = false
    snackbar.value = { show: true, text: 'Document supprimé', color: 'success' }
    fetchDocuments()
  }
  catch (e: any) {
    snackbar.value = { show: true, text: e?.message || 'Erreur', color: 'error' }
  }
}

const headers = [
  { title: 'Titre', key: 'title', sortable: false },
  { title: 'Catégorie', key: 'category', sortable: false },
  { title: 'Entité', key: 'entity', sortable: false },
  { title: 'Date doc', key: 'date_document', sortable: false },
  { title: 'Déposé par', key: 'created_by_user', sortable: false },
  { title: 'Taille', key: 'size', sortable: false },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
]
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardItem class="d-flex flex-wrap align-center gap-2">
          <VCardTitle class="d-flex align-center gap-2">
            <VIcon icon="tabler-archive" size="24" />
            GED — Gestion Électronique des Documents
          </VCardTitle>
          <VSpacer />
          <VBtn
            v-if="can('create', 'Document')"
            color="primary"
            prepend-icon="tabler-plus"
            @click="openUpload"
          >
            Ajouter document
          </VBtn>
        </VCardItem>

        <VCardText class="pb-0">
          <VRow dense class="mb-3">
            <VCol cols="12" sm="6" md="2">
              <VSelect
                v-model="filterEntity"
                :items="GED_ENTITY_OPTIONS"
                item-title="title"
                item-value="value"
                label="Entité"
                density="compact"
                clearable
              />
            </VCol>
            <VCol cols="12" sm="6" md="2">
              <VSelect
                v-model="filterEntityId"
                :items="entityOptionsForSelect"
                :item-title="(item: any) => item.reference || item.numero || item.objet || item.id"
                item-value="id"
                label="Référence"
                density="compact"
                clearable
              />
            </VCol>
            <VCol cols="12" sm="6" md="2">
              <VSelect
                v-model="filterCategory"
                :items="categoryOptions"
                item-title="title"
                item-value="value"
                label="Catégorie"
                density="compact"
                clearable
              />
            </VCol>
            <VCol cols="12" sm="6" md="2">
              <VTextField
                v-model="filterQ"
                label="Recherche"
                density="compact"
                prepend-inner-icon="tabler-search"
                clearable
              />
            </VCol>
            <VCol cols="12" sm="6" md="1">
              <VTextField v-model="filterFrom" label="Du" type="date" density="compact" clearable />
            </VCol>
            <VCol cols="12" sm="6" md="1">
              <VTextField v-model="filterTo" label="Au" type="date" density="compact" clearable />
            </VCol>
          </VRow>
        </VCardText>

        <VDataTableServer
          :headers="headers"
          :items="documents"
          :items-length="total"
          :loading="isLoading"
          v-model:items-per-page="perPage"
          v-model:page="page"
          class="text-no-wrap"
        >
          <template #item.category="{ item }">
            {{ GED_CATEGORIES[item.category] ?? item.category }}
          </template>
          <template #item.entity="{ item }">
            {{ entityLabel(item) }}
          </template>
          <template #item.date_document="{ item }">
            {{ formatDate(item.date_document) }}
          </template>
          <template #item.created_by_user="{ item }">
            {{ item.created_by_user?.name || (item.created_by_user?.prenom && item.created_by_user?.nom ? `${item.created_by_user.prenom} ${item.created_by_user.nom}` : '-') }}
          </template>
          <template #item.size="{ item }">
            {{ formatSize(item.size) }}
          </template>
          <template #item.actions="{ item }">
            <div class="d-flex gap-1 justify-end">
              <VBtn
                v-if="can('view', 'Document')"
                icon
                variant="text"
                size="small"
                @click="openPreview(item)"
              >
                <VIcon icon="tabler-eye" size="18" />
                <VTooltip activator="parent">Aperçu</VTooltip>
              </VBtn>
              <VBtn
                v-if="can('download', 'Document')"
                icon
                variant="text"
                size="small"
                @click="doDownload(item)"
              >
                <VIcon icon="tabler-download" size="18" />
                <VTooltip activator="parent">Télécharger</VTooltip>
              </VBtn>
              <VBtn
                v-if="can('update', 'Document')"
                icon
                variant="text"
                size="small"
                @click="openEdit(item)"
              >
                <VIcon icon="tabler-edit" size="18" />
                <VTooltip activator="parent">Modifier</VTooltip>
              </VBtn>
              <VBtn
                v-if="can('delete', 'Document')"
                icon
                variant="text"
                size="small"
                color="error"
                @click="openDelete(item)"
              >
                <VIcon icon="tabler-trash" size="18" />
                <VTooltip activator="parent">Supprimer</VTooltip>
              </VBtn>
            </div>
          </template>
          <template #no-data>
            <div class="text-center text-medium-emphasis py-8">
              <VIcon icon="tabler-folder-off" size="48" class="mb-3 opacity-30" />
              <p>Aucun document trouvé</p>
            </div>
          </template>
          <template #loading>
            <VSkeletonLoader type="table-row@5" />
          </template>
        </VDataTableServer>
      </VCard>
    </VCol>
  </VRow>

  <!-- Modal Upload -->
  <VDialog v-model="uploadDialog" max-width="560" persistent>
    <VCard title="Ajouter un document">
      <VCardText>
        <VRow>
          <VCol cols="12" md="6">
            <VSelect
              v-model="uploadForm.documentable_type"
              :items="GED_ENTITY_OPTIONS"
              item-title="title"
              item-value="value"
              label="Entité *"
            />
          </VCol>
          <VCol cols="12" md="6">
            <VSelect
              v-model="uploadForm.documentable_id"
              :items="uploadEntityOptions"
              :item-title="(item: any) => item.reference || item.numero || item.objet || item.id"
              item-value="id"
              label="Référence *"
            />
          </VCol>
          <VCol cols="12" md="6">
            <VSelect
              v-model="uploadForm.category"
              :items="categoryOptions"
              item-title="title"
              item-value="value"
              label="Catégorie *"
            />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField v-model="uploadForm.title" label="Titre *" />
          </VCol>
          <VCol cols="12">
            <VTextarea v-model="uploadForm.description" label="Description" rows="2" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField v-model="uploadForm.date_document" label="Date du document" type="date" />
          </VCol>
          <VCol cols="12">
            <VFileInput
              v-model="uploadForm.file"
              label="Fichier *"
              accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
              show-size
              multiple
            />
          </VCol>
        </VRow>
      </VCardText>
      <VCardActions class="justify-end pa-4">
        <VBtn variant="tonal" @click="uploadDialog = false">Annuler</VBtn>
        <VBtn color="primary" :loading="isUploading" @click="doUpload">Enregistrer</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Modal Edit -->
  <VDialog v-model="editDialog" max-width="500" persistent>
    <VCard title="Modifier les métadonnées">
      <VCardText>
        <VRow>
          <VCol cols="12">
            <VSelect
              v-model="editForm.category"
              :items="categoryOptions"
              item-title="title"
              item-value="value"
              label="Catégorie"
            />
          </VCol>
          <VCol cols="12">
            <VTextField v-model="editForm.title" label="Titre" />
          </VCol>
          <VCol cols="12">
            <VTextarea v-model="editForm.description" label="Description" rows="2" />
          </VCol>
          <VCol cols="12">
            <VTextField v-model="editForm.date_document" label="Date du document" type="date" />
          </VCol>
        </VRow>
      </VCardText>
      <VCardActions class="justify-end pa-4">
        <VBtn variant="tonal" @click="editDialog = false">Annuler</VBtn>
        <VBtn color="primary" :loading="isSavingEdit" @click="saveEdit">Enregistrer</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Modal Preview -->
  <VDialog v-model="previewDialog" max-width="900" persistent @click:outside="closePreview">
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between">
        <span>{{ selectedDoc?.title }}</span>
        <VBtn icon variant="text" size="small" @click="closePreview">
          <VIcon icon="tabler-x" />
        </VBtn>
      </VCardTitle>
      <VCardText class="pa-0">
        <div v-if="previewBlobUrl" class="ged-preview-wrapper">
          <iframe
            v-if="selectedDoc?.mime_type === 'application/pdf'"
            :src="previewBlobUrl"
            class="ged-iframe"
            title="Aperçu"
          />
          <img
            v-else-if="selectedDoc?.mime_type?.startsWith('image/')"
            :src="previewBlobUrl"
            alt="Aperçu"
            class="ged-preview-img"
          >
          <div v-else class="pa-4 text-center text-medium-emphasis">
            Aperçu non disponible pour ce type de fichier.
            <VBtn v-if="can('download', 'Document')" class="mt-2" @click="selectedDoc && doDownload(selectedDoc)">
              Télécharger
            </VBtn>
          </div>
        </div>
        <div v-else class="pa-8 text-center">
          <VProgressCircular indeterminate color="primary" />
        </div>
      </VCardText>
    </VCard>
  </VDialog>

  <!-- Confirm Delete -->
  <VDialog v-model="deleteDialog" max-width="420" persistent>
    <VCard>
      <VCardTitle class="d-flex align-center gap-2 pa-4">
        <VIcon icon="tabler-alert-triangle" color="error" />
        Confirmer la suppression
      </VCardTitle>
      <VCardText>
        Supprimer le document <strong>{{ selectedDoc?.title }}</strong> ? Cette action est irréversible.
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

<style scoped>
.ged-preview-wrapper { min-height: 70vh; }
.ged-iframe { width: 100%; height: 70vh; border: none; }
.ged-preview-img { max-width: 100%; max-height: 70vh; object-fit: contain; }
</style>
