<script setup lang="ts">
import { useReceptionsStore } from '@/stores/receptions'
import { useApi } from '@/composables/useApi'
import ReceptionForm from '@/components/contracts/ReceptionForm.vue'

definePage({ meta: { title: 'Réceptions' } })

const store = useReceptionsStore()
const router = useRouter()
const snackbar = ref({ show: false, text: '', color: 'success' })
const deleteDialog = ref(false)
const rejectDialog = ref(false)
const formDialog = ref(false)
const selectedItem = ref<any>(null)
const commentaireRejet = ref('')
const contratIdForCreate = ref<number | null>(null)

const filterStatut = ref('')
const filterTypeReception = ref('')
const filterContratId = ref<number | null>(null)
const filterDateFrom = ref('')
const filterDateTo = ref('')
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref([{ key: 'date_reception', order: 'desc' }])

const statutOptions = [
  { title: 'Brouillon', value: 'draft' },
  { title: 'Soumis', value: 'submitted' },
  { title: 'Approuvé', value: 'approved' },
  { title: 'Rejeté', value: 'rejected' },
]

const typeLabels: Record<string, string> = {
  provisoire: 'Provisoire',
  partielle: 'Partielle',
  definitive: 'Définitive',
}

const conformiteLabels: Record<string, string> = {
  conforme: 'Conforme',
  non_conforme: 'Non conforme',
  conforme_avec_reserves: 'Conforme avec réserves',
}

const statutColor = (s: string) =>
  ({ draft: 'default', submitted: 'info', approved: 'success', rejected: 'error' }[s] || 'default')

const statutLabel = (s: string) =>
  ({ draft: 'Brouillon', submitted: 'Soumis', approved: 'Approuvé', rejected: 'Rejeté' }[s] || s)

const conformiteColor = (s: string) =>
  ({ conforme: 'success', non_conforme: 'error', conforme_avec_reserves: 'warning' }[s] || 'default')

const headers = [
  { title: 'Numéro', key: 'numero', sortable: true },
  { title: 'Contrat', key: 'contrat', sortable: false },
  { title: 'Type', key: 'type_reception', sortable: true },
  { title: 'Date', key: 'date_reception', sortable: true },
  { title: 'Conformité', key: 'statut_conformite', sortable: false },
  { title: 'Statut', key: 'statut', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, width: '200px' },
]

const { data: contratsData } = await useApi<any>('/contrats?itemsPerPage=-1').json()
const contratsList = computed(() =>
  contratsData.value?.data?.map((c: any) => ({
    title: `${c.reference} — ${(c.objet || '').substring(0, 40)}...`,
    value: c.id,
  })) ?? [],
)

const loadData = async () => {
  await store.fetchReceptions({
    contrat_id: filterContratId.value ?? '',
    type_reception: filterTypeReception.value,
    statut: filterStatut.value,
    date_from: filterDateFrom.value,
    date_to: filterDateTo.value,
    itemsPerPage: itemsPerPage.value,
    page: page.value,
    sortBy: sortBy.value[0]?.key,
    sortDesc: sortBy.value[0]?.order === 'desc',
  })
}

watch(
  [filterStatut, filterTypeReception, filterContratId, filterDateFrom, filterDateTo, itemsPerPage, page, sortBy],
  loadData,
  { deep: true },
)
onMounted(loadData)

const formatDate = (d: string) => (d ? new Date(d).toLocaleDateString('fr-FR') : '-')

const viewReception = (item: any) => router.push(`/apps/contrats/receptions/${item.id}`)

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

const hasProvisoireApproved = computed(() => {
  const c = contratIdForCreate.value ?? selectedItem.value?.contrat_id
  if (!c || !contratsData.value?.data) return false
  const receptions = store.receptions.filter((r: any) => r.contrat_id === c)
  return receptions.some((r: any) => r.type_reception === 'provisoire' && r.statut === 'approved')
})

const onFormSubmit = async (payload: Record<string, any>) => {
  try {
    const cid = contratIdForCreate.value ?? selectedItem.value?.contrat_id
    if (!cid) {
      snackbar.value = { show: true, text: 'Veuillez sélectionner un contrat', color: 'error' }
      return
    }
    if (selectedItem.value) {
      await store.updateReception(selectedItem.value.id, payload)
      snackbar.value = { show: true, text: 'Réception modifiée avec succès', color: 'success' }
    }
    else {
      await store.createReception(cid, payload)
      snackbar.value = { show: true, text: 'Réception créée avec succès', color: 'success' }
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
    await store.deleteReception(selectedItem.value.id)
    deleteDialog.value = false
    snackbar.value = { show: true, text: 'Réception supprimée avec succès', color: 'success' }
    await loadData()
  }
  catch {
    snackbar.value = { show: true, text: 'Impossible de supprimer cette réception', color: 'error' }
  }
}

const doAction = async (action: 'submitReception' | 'approveReception', item: any) => {
  try {
    await store[action](item.id)
    snackbar.value = {
      show: true,
      text: action === 'submitReception' ? 'Réception soumise' : 'Réception approuvée',
      color: 'success',
    }
    await loadData()
  }
  catch (e: any) {
    snackbar.value = {
      show: true,
      text: e?.data?.message || 'Erreur lors de l\'action',
      color: 'error',
    }
  }
}

const confirmReject = async () => {
  if (!selectedItem.value) return
  try {
    await store.rejectReception(selectedItem.value.id, commentaireRejet.value)
    rejectDialog.value = false
    snackbar.value = { show: true, text: 'Réception rejetée', color: 'warning' }
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
          <VIcon icon="tabler-package-import" class="me-2" />
          Réceptions (PV de réception)
          <VSpacer />
          <VBtn prepend-icon="tabler-plus" color="primary" @click="openCreate()">
            Nouvelle réception
          </VBtn>
        </VCardTitle>

        <VDivider />

        <VCardText>
          <VRow class="mb-3">
            <VCol cols="12" md="2">
              <VSelect
                v-model="filterTypeReception"
                :items="[{ title: 'Tous types', value: '' }, { title: 'Provisoire', value: 'provisoire' }, { title: 'Partielle', value: 'partielle' }, { title: 'Définitive', value: 'definitive' }]"
                label="Type"
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
            <VCol cols="12" md="4">
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
            :items="store.receptions"
            :items-length="store.total"
            :loading="store.isLoading"
            class="elevation-0"
          >
            <template #item.contrat="{ item }">
              <span class="text-caption">
                {{ item.contrat?.reference ?? '-' }}
              </span>
            </template>
            <template #item.type_reception="{ item }">
              {{ typeLabels[item.type_reception] ?? item.type_reception }}
            </template>
            <template #item.date_reception="{ item }">
              {{ formatDate(item.date_reception) }}
            </template>
            <template #item.statut_conformite="{ item }">
              <VChip :color="conformiteColor(item.statut_conformite)" size="small">
                {{ conformiteLabels[item.statut_conformite] ?? item.statut_conformite }}
              </VChip>
            </template>
            <template #item.statut="{ item }">
              <VChip :color="statutColor(item.statut)" size="small">
                {{ statutLabel(item.statut) }}
              </VChip>
            </template>
            <template #item.actions="{ item }">
              <VBtn icon variant="text" size="small" color="secondary" @click="viewReception(item)">
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
              <VBtn v-if="item.statut === 'draft'" icon variant="text" size="small" color="info" @click="doAction('submitReception', item)">
                <VIcon icon="tabler-send" />
                <VTooltip activator="parent">Soumettre</VTooltip>
              </VBtn>
              <VBtn v-if="item.statut === 'submitted'" icon variant="text" size="small" color="success" @click="doAction('approveReception', item)">
                <VIcon icon="tabler-check" />
                <VTooltip activator="parent">Approuver</VTooltip>
              </VBtn>
              <VBtn v-if="item.statut === 'submitted'" icon variant="text" size="small" color="error" @click="openReject(item)">
                <VIcon icon="tabler-x" />
                <VTooltip activator="parent">Rejeter</VTooltip>
              </VBtn>
              <VBtn v-if="item.statut === 'draft'" icon variant="text" size="small" color="error" @click="openDelete(item)">
                <VIcon icon="tabler-trash" />
                <VTooltip activator="parent">Supprimer</VTooltip>
              </VBtn>
              <VBtn
                icon
                variant="text"
                size="small"
                @click="router.push(`/apps/contrats/receptions/${item.id}`)"
              >
                <VIcon icon="tabler-folder" />
                <VTooltip activator="parent">Documents</VTooltip>
              </VBtn>
            </template>
          </VDataTableServer>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <!-- Dialog formulaire -->
  <VDialog v-model="formDialog" max-width="900" scrollable persistent>
    <VCard :title="selectedItem ? 'Modifier la réception' : 'Nouvelle réception'">
      <VCardText>
        <VSelect
          v-if="!selectedItem"
          v-model="contratIdForCreate"
          :items="contratsList"
          label="Contrat *"
          class="mb-4"
        />
        <ReceptionForm
          v-if="contratForForm || selectedItem"
          :model-value="selectedItem ?? {}"
          :contrat="contratForForm"
          :has-provisoire-approved="hasProvisoireApproved"
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
        Rejeter la réception
      </VCardTitle>
      <VCardText>
        <p class="mb-3">
          Réception <strong>{{ selectedItem?.numero }}</strong> — {{ selectedItem?.contrat?.reference }}
        </p>
        <VTextarea v-model="commentaireRejet" label="Commentaire de validation *" rows="3" required />
      </VCardText>
      <VCardActions class="justify-end pa-4">
        <VBtn variant="tonal" @click="rejectDialog = false">Annuler</VBtn>
        <VBtn color="error" :disabled="!commentaireRejet.trim()" @click="confirmReject">Confirmer</VBtn>
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
        Voulez-vous vraiment supprimer la réception <strong>{{ selectedItem?.numero }}</strong> ?
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
