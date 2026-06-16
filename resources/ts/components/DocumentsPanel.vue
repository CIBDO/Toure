<script setup lang="ts">
import { documentService, GED_CATEGORIES, type DocumentRecord } from '@/services/documentService'
import { useAbility } from '@/plugins/casl/composables/useAbility'

const props = defineProps<{
  documentableType: string
  documentableId: number
  entityLabel?: string
}>()

const ability = useAbility()
const can = (action: string, subject: string) => ability.can(action, subject)

const documents = ref<DocumentRecord[]>([])
const isLoading = ref(false)
const snackbar = ref({ show: false, text: '', color: 'success' })

const uploadDialog = ref(false)
const previewDialog = ref(false)
const deleteDialog = ref(false)
const selectedDoc = ref<DocumentRecord | null>(null)
const previewBlobUrl = ref<string | null>(null)
const isUploading = ref(false)

const uploadForm = ref({
  category: 'contrat_signe',
  title: '',
  description: '',
  date_document: '',
  tags: [] as string[],
  files: [] as File[],
})

const categoryOptions = computed(() =>
  Object.entries(GED_CATEGORIES).map(([value, title]) => ({ value, title })))

const fetchList = async () => {
  if (!props.documentableId) return
  isLoading.value = true
  try {
    const res = await documentService.list({
      documentable_type: props.documentableType,
      documentable_id: props.documentableId,
      per_page: 100,
    })
    documents.value = res.data ?? []
  }
  catch {
    documents.value = []
  }
  finally {
    isLoading.value = false
  }
}

watch(() => [props.documentableType, props.documentableId], fetchList, { immediate: true })

const formatDate = (d: string | undefined) => (d ? new Date(d).toLocaleDateString('fr-FR') : '-')
const formatSize = (bytes: number) => {
  if (bytes < 1024) return `${bytes} o`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} Ko`
  return `${(bytes / (1024 * 1024)).toFixed(1)} Mo`
}

const openUpload = () => {
  uploadForm.value = {
    category: 'contrat_signe',
    title: '',
    description: '',
    date_document: '',
    tags: [],
    files: [],
  }
  uploadDialog.value = true
}

const onFilesChange = (e: Event) => {
  const input = e.target as HTMLInputElement
  if (input?.files) uploadForm.value.files = Array.from(input.files)
}

const doUpload = async () => {
  const f = uploadForm.value
  if (!f.files.length) {
    snackbar.value = { show: true, text: 'Sélectionnez au moins un fichier', color: 'error' }
    return
  }
  isUploading.value = true
  let ok = 0
  let err = 0
  for (const file of f.files) {
    const title = f.files.length === 1 ? (f.title || file.name) : file.name.replace(/\.[^.]+$/, '')
    try {
      const form = new FormData()
      form.append('documentable_type', props.documentableType)
      form.append('documentable_id', String(props.documentableId))
      form.append('category', f.category)
      form.append('title', title)
      if (f.description) form.append('description', f.description)
      if (f.date_document) form.append('date_document', f.date_document)
      if (f.tags?.length) form.append('tags', JSON.stringify(f.tags))
      form.append('file', file)
      await documentService.upload(form)
      ok++
    }
    catch {
      err++
    }
  }
  isUploading.value = false
  uploadDialog.value = false
  fetchList()
  if (err) snackbar.value = { show: true, text: `${ok} ajouté(s), ${err} erreur(s)`, color: err ? 'warning' : 'success' }
  else snackbar.value = { show: true, text: ok > 1 ? `${ok} documents ajoutés` : 'Document ajouté', color: 'success' }
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
    fetchList()
  }
  catch (e: any) {
    snackbar.value = { show: true, text: e?.message || 'Erreur', color: 'error' }
  }
}
</script>

<template>
  <VCard>
    <VCardItem class="d-flex flex-wrap align-center gap-2">
      <VCardTitle class="text-subtitle-1">
        <VIcon icon="tabler-folder" start />
        Documents
      </VCardTitle>
      <VSpacer />
      <VBtn
        v-if="can('create', 'Document')"
        size="small"
        color="primary"
        prepend-icon="tabler-upload"
        @click="openUpload"
      >
        Ajouter document
      </VBtn>
    </VCardItem>
    <VCardText>
      <div v-if="isLoading" class="py-4">
        <VSkeletonLoader type="list-item@3" />
      </div>
      <div v-else-if="!documents.length" class="text-center text-medium-emphasis py-6">
        <VIcon icon="tabler-folder-off" size="40" class="mb-2 opacity-50" />
        <p class="mb-2">Aucun document</p>
        <VBtn
          v-if="can('create', 'Document')"
          size="small"
          variant="tonal"
          @click="openUpload"
        >
          Ajouter un document
        </VBtn>
      </div>
      <VList v-else density="compact">
        <VListItem
          v-for="doc in documents"
          :key="doc.id"
          class="px-0"
        >
          <template #prepend>
            <VIcon :icon="doc.mime_type?.startsWith('image/') ? 'tabler-photo' : 'tabler-file'" size="20" class="me-2" />
          </template>
          <VListItemTitle>{{ doc.title }}</VListItemTitle>
          <VListItemSubtitle>
            {{ GED_CATEGORIES[doc.category] ?? doc.category }} · {{ formatSize(doc.size) }} · {{ formatDate(doc.date_document) }}
          </VListItemSubtitle>
          <template #append>
            <div class="d-flex gap-1">
              <VBtn
                v-if="can('view', 'Document')"
                icon
                variant="text"
                size="x-small"
                @click="openPreview(doc)"
              >
                <VIcon icon="tabler-eye" size="16" />
                <VTooltip activator="parent">Aperçu</VTooltip>
              </VBtn>
              <VBtn
                v-if="can('download', 'Document')"
                icon
                variant="text"
                size="x-small"
                @click="doDownload(doc)"
              >
                <VIcon icon="tabler-download" size="16" />
                <VTooltip activator="parent">Télécharger</VTooltip>
              </VBtn>
              <VBtn
                v-if="can('delete', 'Document')"
                icon
                variant="text"
                size="x-small"
                color="error"
                @click="openDelete(doc)"
              >
                <VIcon icon="tabler-trash" size="16" />
                <VTooltip activator="parent">Supprimer</VTooltip>
              </VBtn>
            </div>
          </template>
        </VListItem>
      </VList>
    </VCardText>
  </VCard>

  <!-- Modal Upload (multiple + drag zone) -->
  <VDialog v-model="uploadDialog" max-width="520" persistent>
    <VCard title="Ajouter des documents">
      <VCardText>
        <VRow>
          <VCol cols="12">
            <VSelect
              v-model="uploadForm.category"
              :items="categoryOptions"
              item-title="title"
              item-value="value"
              label="Catégorie *"
            />
          </VCol>
          <VCol cols="12">
            <VTextField
              v-model="uploadForm.title"
              label="Titre (si un seul fichier)"
              placeholder="Optionnel pour plusieurs fichiers"
            />
          </VCol>
          <VCol cols="12">
            <VTextarea v-model="uploadForm.description" label="Description" rows="2" />
          </VCol>
          <VCol cols="12">
            <VTextField v-model="uploadForm.date_document" label="Date du document" type="date" />
          </VCol>
          <VCol cols="12">
            <VFileInput
              :model-value="uploadForm.files"
              label="Fichier(s) *"
              accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
              show-size
              multiple
              @update:model-value="(v: File[] | File) => uploadForm.files = Array.isArray(v) ? v : (v ? [v] : [])"
            />
          </VCol>
        </VRow>
      </VCardText>
      <VCardActions class="justify-end pa-4">
        <VBtn variant="tonal" @click="uploadDialog = false">Annuler</VBtn>
        <VBtn color="primary" :loading="isUploading" :disabled="!uploadForm.files.length" @click="doUpload">
          Enregistrer
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Modal Preview -->
  <VDialog v-model="previewDialog" max-width="800" persistent @click:outside="closePreview">
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
            Aperçu non disponible.
            <VBtn v-if="can('download', 'Document')" class="mt-2" size="small" @click="selectedDoc && doDownload(selectedDoc)">
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
        Supprimer le document
      </VCardTitle>
      <VCardText>
        Supprimer <strong>{{ selectedDoc?.title }}</strong> ? Cette action est irréversible.
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
.ged-preview-wrapper { min-height: 60vh; }
.ged-iframe { width: 100%; height: 60vh; border: none; }
.ged-preview-img { max-width: 100%; max-height: 60vh; object-fit: contain; }
</style>
