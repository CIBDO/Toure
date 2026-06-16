<script setup lang="ts">
import { useOrdreServicesStore } from '@/stores/ordreServices'
import OrdreServiceForm from '@/components/contracts/OrdreServiceForm.vue'

definePage({ meta: { title: 'Ordres de service' } })

const store = useOrdreServicesStore()
const router = useRouter()
const snackbar = ref({ show: false, text: '', color: 'success' })
const deleteDialog = ref(false)
const rejectDialog = ref(false)
const formDialog = ref(false)
const selectedItem = ref<any>(null)
const commentaireRejet = ref('')
const contratIdForCreate = ref<number | null>(null)

const filterStatut = ref('')
const filterContratId = ref<number | null>(null)
const filterTypeOs = ref('')
const filterDateFrom = ref('')
const filterDateTo = ref('')
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref([{ key: 'date_emission', order: 'desc' }])

const statutOptions = [
  { title: 'Brouillon', value: 'draft' },
  { title: 'Soumis', value: 'submitted' },
  { title: 'Approuvé', value: 'approved' },
  { title: 'Rejeté', value: 'rejected' },
  { title: 'Exécuté', value: 'executed' },
  { title: 'Archivé', value: 'archived' },
]

const typeLabels: Record<string, string> = {
  demarrage: 'Démarrage',
  suspension: 'Suspension',
  reprise: 'Reprise',
  arret: 'Arrêt',
  modification: 'Modification',
  autre: 'Autre',
}

const statutColor = (s: string) =>
  ({ draft: 'default', submitted: 'info', approved: 'success', rejected: 'error', executed: 'success', archived: 'secondary' }[s] || 'default')

const statutLabel = (s: string) =>
  ({ draft: 'Brouillon', submitted: 'Soumis', approved: 'Approuvé', rejected: 'Rejeté', executed: 'Exécuté', archived: 'Archivé' }[s] || s)

const headers = [
  { title: 'Numéro', key: 'numero', sortable: true },
  { title: 'Contrat', key: 'contrat', sortable: false },
  { title: 'Type', key: 'type_os', sortable: true },
  { title: 'Objet', key: 'objet', sortable: true },
  { title: 'Date émission', key: 'date_emission', sortable: true },
  { title: 'Impact délai', key: 'impact_delai', sortable: false },
  { title: 'Statut', key: 'statut', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, width: '220px' },
]

const { data: contratsData } = await useApi<any>('/contrats?itemsPerPage=-1').json()
const contratsList = computed(() =>
  contratsData.value?.data?.map((c: any) => ({
    title: `${c.reference ?? c.numero} — ${(c.objet || '').substring(0, 35)}...`,
    value: c.id,
  })) ?? [],
)

const loadData = async () => {
  await store.fetchOrdreServices({
    contrat_id: filterContratId.value ?? '',
    statut: filterStatut.value,
    type_os: filterTypeOs.value,
    date_emission_from: filterDateFrom.value,
    date_emission_to: filterDateTo.value,
    itemsPerPage: itemsPerPage.value,
    page: page.value,
    sortBy: sortBy.value[0]?.key,
    sortDesc: sortBy.value[0]?.order === 'desc',
  })
}

watch(
  [filterStatut, filterContratId, filterTypeOs, filterDateFrom, filterDateTo, itemsPerPage, page, sortBy],
  loadData,
  { deep: true },
)
onMounted(loadData)

const formatDate = (d: string) => (d ? new Date(d).toLocaleDateString('fr-FR') : '-')

const impactDisplay = (item: any) => {
  if (item.impact_delai === 'none' || item.delai_jours == null) return '-'
  const sign = item.impact_delai === 'extend' ? '+' : '-'
  return `${sign}${item.delai_jours} j`
}

const viewOS = (item: any) => router.push(`/apps/contrats/ordre-services/${item.id}`)

const openCreate = (contratId?: number) => {
  selectedItem.value = null
  contratIdForCreate.value = contratId ?? null
  formDialog.value = true
}

const openEdit = (item: any) => {
  selectedItem.value = item
  contratIdForCreate.value = item.contrat_id
  formDialog.value = true
}

const openDelete = (item: any) => {
  selectedItem.value = item
  deleteDialog.value = true
}

const openReject = (item: any) => {
  selectedItem.value = item
  commentaireRejet.value = ''
  rejectDialog.value = true
}

const contratForForm = computed(() => {
  if (selectedItem.value?.contrat) return selectedItem.value.contrat
  if (contratIdForCreate.value && contratsData.value?.data) {
    return contratsData.value.data.find((c: any) => c.id === contratIdForCreate.value)
  }
  return null
})

const onFormSubmit = async (payload: Record<string, any>) => {
  try {
    const cid = contratIdForCreate.value ?? selectedItem.value?.contrat_id
    if (!cid) {
      snackbar.value = { show: true, text: 'Veuillez sélectionner un contrat', color: 'error' }
      return
    }
    if (selectedItem.value) {
      await store.updateOrdreService(selectedItem.value.id, payload)
      snackbar.value = { show: true, text: 'Ordre de service modifié avec succès', color: 'success' }
    }
    else {
      await store.createOrdreService(cid, payload)
      snackbar.value = { show: true, text: 'Ordre de service créé avec succès', color: 'success' }
    }
    formDialog.value = false
    await loadData()
  }
  catch (e: any) {
    snackbar.value = {
      show: true,
      text: e?.data?.message || e?.message || 'Une erreur est survenue',
      color: 'error',
    }
  }
}

const confirmDelete = async () => {
  if (!selectedItem.value) return
  try {
    await store.deleteOrdreService(selectedItem.value.id)
    deleteDialog.value = false
    snackbar.value = { show: true, text: 'Ordre de service supprimé avec succès', color: 'success' }
    await loadData()
  }
  catch {
    snackbar.value = { show: true, text: 'Impossible de supprimer cet ordre de service', color: 'error' }
  }
}

const doAction = async (action: 'submitOrdreService' | 'approveOrdreService' | 'executeOrdreService', item: any) => {
  try {
    await store[action](item.id)
    const labels = { submitOrdreService: 'soumis', approveOrdreService: 'approuvé', executeOrdreService: 'exécuté' }
    snackbar.value = { show: true, text: `OS ${labels[action]} avec succès`, color: 'success' }
    await loadData()
  }
  catch (e: any) {
    snackbar.value = { show: true, text: e?.data?.message || 'Erreur lors de l\'action', color: 'error' }
  }
}

const confirmReject = async () => {
  if (!selectedItem.value) return
  try {
    await store.rejectOrdreService(selectedItem.value.id, commentaireRejet.value)
    rejectDialog.value = false
    snackbar.value = { show: true, text: 'Ordre de service rejeté', color: 'warning' }
    await loadData()
  }
  catch {
    snackbar.value = { show: true, text: 'Erreur lors du rejet', color: 'error' }
  }
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardTitle class="d-flex align-center pa-4">
          <VIcon icon="tabler-clipboard-text" class="me-2" />
          Ordres de service
          <VSpacer />
          <VBtn prepend-icon="tabler-plus" color="primary" @click="openCreate()">
            Nouvel OS
          </VBtn>
        </VCardTitle>

        <VDivider />

        <VCardText>
          <VRow class="mb-3">
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
              <VSelect
                v-model="filterTypeOs"
                :items="[{ title: 'Tous types', value: '' }, ...Object.entries(typeLabels).map(([value, title]) => ({ title, value }))]"
                item-title="title"
                item-value="value"
                label="Type"
                density="compact"
                clearable
              />
            </VCol>
            <VCol cols="12" md="3">
              <VAutocomplete
                v-model="filterContratId"
                :items="contratsList"
                label="Contrat"
                density="compact"
                clearable
              />
            </VCol>
            <VCol cols="12" md="2">
              <VTextField v-model="filterDateFrom" label="Date du" type="date" density="compact" />
            </VCol>
            <VCol cols="12" md="2">
              <VTextField v-model="filterDateTo" label="au" type="date" density="compact" />
            </VCol>
          </VRow>

          <VDataTableServer
            v-model:items-per-page="itemsPerPage"
            v-model:page="page"
            v-model:sort-by="sortBy"
            :headers="headers"
            :items="store.ordreServices"
            :items-length="store.total"
            :loading="store.isLoading"
            class="elevation-0"
          >
            <template #item.contrat="{ item }">
              <span class="text-caption">{{ item.contrat?.reference ?? item.contrat?.numero ?? '-' }}</span>
            </template>
            <template #item.type_os="{ item }">
              {{ typeLabels[item.type_os] ?? item.type_os }}
            </template>
            <template #item.date_emission="{ item }">
              {{ formatDate(item.date_emission) }}
            </template>
            <template #item.impact_delai="{ item }">
              {{ impactDisplay(item) }}
            </template>
            <template #item.statut="{ item }">
              <VChip :color="statutColor(item.statut)" size="small">
                {{ statutLabel(item.statut) }}
              </VChip>
            </template>
            <template #item.actions="{ item }">
              <VBtn icon variant="text" size="small" color="secondary" @click="viewOS(item)">
                <VIcon icon="tabler-eye" />
                <VTooltip activator="parent">Voir</VTooltip>
              </VBtn>
              <VBtn
                v-if="item.statut === 'draft'"
                icon variant="text"
                size="small"
                color="primary"
                @click="openEdit(item)"
              >
                <VIcon icon="tabler-edit" />
                <VTooltip activator="parent">Modifier</VTooltip>
              </VBtn>
              <VBtn v-if="item.statut === 'draft'" icon variant="text" size="small" color="info" @click="doAction('submitOrdreService', item)">
                <VIcon icon="tabler-send" />
                <VTooltip activator="parent">Soumettre</VTooltip>
              </VBtn>
              <VBtn v-if="item.statut === 'submitted'" icon variant="text" size="small" color="success" @click="doAction('approveOrdreService', item)">
                <VIcon icon="tabler-check" />
                <VTooltip activator="parent">Approuver</VTooltip>
              </VBtn>
              <VBtn v-if="item.statut === 'submitted'" icon variant="text" size="small" color="error" @click="openReject(item)">
                <VIcon icon="tabler-x" />
                <VTooltip activator="parent">Rejeter</VTooltip>
              </VBtn>
              <VBtn v-if="item.statut === 'approved'" icon variant="text" size="small" color="primary" @click="doAction('executeOrdreService', item)">
                <VIcon icon="tabler-player-play" />
                <VTooltip activator="parent">Exécuter</VTooltip>
              </VBtn>
              <VBtn v-if="item.statut === 'draft'" icon variant="text" size="small" color="error" @click="openDelete(item)">
                <VIcon icon="tabler-trash" />
                <VTooltip activator="parent">Supprimer</VTooltip>
              </VBtn>
            </template>
          </VDataTableServer>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <!-- Dialog formulaire -->
  <VDialog v-model="formDialog" max-width="800" scrollable persistent>
    <VCard :title="selectedItem ? 'Modifier l\'ordre de service' : 'Nouvel ordre de service'">
      <VCardText>
        <VSelect
          v-if="!selectedItem"
          v-model="contratIdForCreate"
          :items="contratsList"
          item-title="title"
          item-value="value"
          label="Contrat *"
          class="mb-4"
        />
        <OrdreServiceForm
          v-if="contratForForm || selectedItem"
          :model-value="selectedItem ?? {}"
          :contrat="contratForForm"
          :is-editing="!!selectedItem"
          @submit="onFormSubmit"
          @cancel="formDialog = false"
        />
      </VCardText>
    </VCard>
  </VDialog>

  <!-- Dialog rejet -->
  <VDialog v-model="rejectDialog" max-width="500">
    <VCard>
      <VCardTitle class="d-flex align-center gap-2 pa-4">
        <VIcon icon="tabler-x" color="error" />
        Rejeter l'ordre de service
      </VCardTitle>
      <VCardText>
        <p class="mb-3">
          OS <strong>{{ selectedItem?.numero }}</strong> — {{ selectedItem?.contrat?.reference }}
        </p>
        <VTextarea v-model="commentaireRejet" label="Commentaire de validation" rows="3" />
      </VCardText>
      <VCardActions class="justify-end pa-4">
        <VBtn variant="tonal" @click="rejectDialog = false">Annuler</VBtn>
        <VBtn color="error" @click="confirmReject">Confirmer</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Dialog suppression -->
  <VDialog v-model="deleteDialog" max-width="420">
    <VCard>
      <VCardTitle class="d-flex align-center gap-2 pa-4">
        <VIcon icon="tabler-alert-triangle" color="error" />
        Confirmer la suppression
      </VCardTitle>
      <VCardText>
        Voulez-vous vraiment supprimer l'ordre de service <strong>{{ selectedItem?.numero }}</strong> ?
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
