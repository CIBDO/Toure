<script setup lang="ts">
import { useExpressionsBesoinStore } from '@/stores/expressionsBesoin'
import { useDomainesStore } from '@/stores/domaines'

definePage({ meta: { title: 'Expressions de besoin' } })

const store = useExpressionsBesoinStore()
const domainesStore = useDomainesStore()
const snackbar = ref({ show: false, text: '', color: 'success' })
const dialog = ref(false)
const deleteDialog = ref(false)
const isEditing = ref(false)
const selectedItem = ref<any>(null)
const searchQuery = ref('')
const filterDomaine = ref('')
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref([{ key: 'libelle', order: 'asc' }])

const form = ref({
  code: '',
  libelle: '',
  description: '',
  unite_defaut: '',
  domaine_activite_id: null as number | null,
  actif: true,
})

const headers = [
  { title: 'Code', key: 'code', sortable: true },
  { title: 'Libellé', key: 'libelle', sortable: true },
  { title: 'Unité', key: 'unite_defaut', sortable: false },
  { title: 'Domaine', key: 'domaine_activite', sortable: false },
  { title: 'Statut', key: 'actif', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false },
]

const loadData = async () => {
  await store.fetchExpressions({
    q: searchQuery.value,
    domaine_activite_id: filterDomaine.value,
    itemsPerPage: itemsPerPage.value,
    page: page.value,
    sortBy: sortBy.value[0]?.key,
    sortDesc: sortBy.value[0]?.order === 'desc',
  })
}

onMounted(async () => {
  await Promise.all([
    loadData(),
    domainesStore.fetchDomaines({ itemsPerPage: -1, actif: true }),
  ])
})

watch([searchQuery, filterDomaine, itemsPerPage, page, sortBy], loadData, { deep: true })

const openCreate = () => {
  isEditing.value = false
  form.value = {
    code: '', libelle: '', description: '', unite_defaut: '',
    domaine_activite_id: null, actif: true,
  }
  dialog.value = true
}

const openEdit = (item: any) => {
  isEditing.value = true
  selectedItem.value = item
  form.value = { ...item }
  dialog.value = true
}

const openDelete = (item: any) => {
  selectedItem.value = item
  deleteDialog.value = true
}

const save = async () => {
  try {
    if (isEditing.value)
      await store.updateExpression(selectedItem.value.id, form.value)
    else
      await store.createExpression(form.value)
    dialog.value = false
    snackbar.value = { show: true, text: `Expression ${isEditing.value ? 'modifiée' : 'créée'} avec succès`, color: 'success' }
    await loadData()
  }
  catch (e: any) {
    snackbar.value = { show: true, text: e?.data?.message || 'Une erreur est survenue', color: 'error' }
  }
}

const confirmDelete = async () => {
  try {
    await store.deleteExpression(selectedItem.value.id)
    deleteDialog.value = false
    snackbar.value = { show: true, text: 'Expression supprimée avec succès', color: 'success' }
    await loadData()
  }
  catch (e: any) {
    snackbar.value = { show: true, text: e?.data?.message || 'Impossible de supprimer cette expression', color: 'error' }
  }
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardTitle class="d-flex align-center pa-4">
          <VIcon icon="tabler-list-check" class="me-2" />
          Expressions de besoin
          <VSpacer />
          <VBtn prepend-icon="tabler-plus" color="primary" @click="openCreate">
            Nouvelle expression
          </VBtn>
        </VCardTitle>
        <VDivider />
        <VCardText>
          <VRow class="mb-4">
            <VCol cols="12" md="4">
              <VTextField
                v-model="searchQuery"
                placeholder="Rechercher (code, libellé)..."
                prepend-inner-icon="tabler-search"
                density="compact"
                clearable
              />
            </VCol>
            <VCol cols="12" md="4">
              <VSelect
                v-model="filterDomaine"
                :items="[{ title: 'Tous les domaines', value: '' }, ...domainesStore.domaines.map(d => ({ title: d.libelle, value: String(d.id) }))]"
                label="Domaine d'activité"
                density="compact"
                clearable
              />
            </VCol>
          </VRow>

          <VDataTableServer
            v-model:items-per-page="itemsPerPage"
            v-model:page="page"
            v-model:sort-by="sortBy"
            :headers="headers"
            :items="store.expressions"
            :items-length="store.total"
            :loading="store.isLoading"
          >
            <template #item.libelle="{ item }">
              <span class="text-truncate d-inline-block" style="max-width:280px" :title="item.libelle">{{ item.libelle }}</span>
            </template>
            <template #item.domaine_activite="{ item }">
              <span class="text-caption">{{ item.domaine_activite?.libelle ?? '-' }}</span>
            </template>
            <template #item.actif="{ item }">
              <VChip :color="item.actif ? 'success' : 'default'" size="small">{{ item.actif ? 'Actif' : 'Inactif' }}</VChip>
            </template>
            <template #item.actions="{ item }">
              <VBtn icon variant="text" size="small" color="primary" @click="openEdit(item)">
                <VIcon icon="tabler-edit" />
              </VBtn>
              <VBtn icon variant="text" size="small" color="error" @click="openDelete(item)">
                <VIcon icon="tabler-trash" />
              </VBtn>
            </template>
          </VDataTableServer>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <VDialog v-model="dialog" max-width="700">
    <VCard :title="isEditing ? 'Modifier l\'expression' : 'Nouvelle expression de besoin'">
      <VCardText>
        <VRow>
          <VCol cols="12" md="4">
            <VTextField v-model="form.code" label="Code *" :disabled="isEditing" hint="Ex: EB-001" persistent-hint />
          </VCol>
          <VCol cols="12" md="8">
            <VTextField v-model="form.libelle" label="Libellé *" />
          </VCol>
          <VCol cols="12">
            <VTextarea v-model="form.description" label="Description" rows="3" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField v-model="form.unite_defaut" label="Unité par défaut" placeholder="unité, forfait, lot..." />
          </VCol>
          <VCol cols="12" md="6">
            <VSelect
              v-model="form.domaine_activite_id"
              :items="domainesStore.domaines.map(d => ({ title: d.libelle, value: d.id }))"
              label="Domaine d'activité"
              clearable
            />
          </VCol>
          <VCol cols="12">
            <VSwitch v-model="form.actif" label="Expression active" color="primary" />
          </VCol>
        </VRow>
      </VCardText>
      <VCardActions class="justify-end">
        <VBtn variant="tonal" @click="dialog = false">Annuler</VBtn>
        <VBtn color="primary" @click="save">{{ isEditing ? 'Modifier' : 'Créer' }}</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <VDialog v-model="deleteDialog" max-width="420">
    <VCard title="Confirmer la suppression">
      <VCardText>
        Voulez-vous vraiment supprimer l'expression <strong>{{ selectedItem?.libelle }}</strong> ?
      </VCardText>
      <VCardActions class="justify-end">
        <VBtn variant="tonal" @click="deleteDialog = false">Annuler</VBtn>
        <VBtn color="error" @click="confirmDelete">Supprimer</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <VSnackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">{{ snackbar.text }}</VSnackbar>
</template>
