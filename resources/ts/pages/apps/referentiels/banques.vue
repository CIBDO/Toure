<script setup lang="ts">
import { useBanquesStore } from '@/stores/banques'

definePage({ meta: { title: 'Banques' } })

const store = useBanquesStore()
const snackbar = ref({ show: false, text: '', color: 'success' })
const dialog = ref(false)
const deleteDialog = ref(false)
const isEditing = ref(false)
const selectedItem = ref<any>(null)
const searchQuery = ref('')
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref([{ key: 'libelle', order: 'asc' }])

const form = ref({
  code: '', libelle: '', sigle: '', adresse: '', telephone: '', email: '', actif: true,
})

const headers = [
  { title: 'Code', key: 'code', sortable: true },
  { title: 'Libellé', key: 'libelle', sortable: true },
  { title: 'Sigle', key: 'sigle', sortable: false },
  { title: 'Téléphone', key: 'telephone', sortable: false },
  { title: 'Email', key: 'email', sortable: false },
  { title: 'Statut', key: 'actif', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false },
]

const loadData = async () => {
  await store.fetchBanques({
    q: searchQuery.value,
    itemsPerPage: itemsPerPage.value,
    page: page.value,
    sortBy: sortBy.value[0]?.key,
    sortDesc: sortBy.value[0]?.order === 'desc',
  })
}

watch([searchQuery, itemsPerPage, page, sortBy], loadData, { deep: true })
onMounted(loadData)

const openCreate = () => {
  isEditing.value = false
  form.value = { code: '', libelle: '', sigle: '', adresse: '', telephone: '', email: '', actif: true }
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
      await store.updateBanque(selectedItem.value.id, form.value)
    else
      await store.createBanque(form.value)
    dialog.value = false
    snackbar.value = { show: true, text: `Banque ${isEditing.value ? 'modifiée' : 'créée'} avec succès`, color: 'success' }
    await loadData()
  }
  catch {
    snackbar.value = { show: true, text: 'Une erreur est survenue', color: 'error' }
  }
}

const confirmDelete = async () => {
  try {
    await store.deleteBanque(selectedItem.value.id)
    deleteDialog.value = false
    snackbar.value = { show: true, text: 'Banque supprimée avec succès', color: 'success' }
    await loadData()
  }
  catch {
    snackbar.value = { show: true, text: 'Impossible de supprimer cette banque', color: 'error' }
  }
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardTitle class="d-flex align-center pa-4">
          <VIcon icon="tabler-building-bank" class="me-2" />
          Gestion des Banques
          <VSpacer />
          <VBtn prepend-icon="tabler-plus" color="primary" @click="openCreate">
            Nouvelle Banque
          </VBtn>
        </VCardTitle>

        <VDivider />

        <VCardText>
          <VRow class="mb-4">
            <VCol cols="12" md="4">
              <VTextField
                v-model="searchQuery"
                placeholder="Rechercher..."
                prepend-inner-icon="tabler-search"
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
            :items="store.banques"
            :items-length="store.total"
            :loading="store.isLoading"
            class="elevation-0"
          >
            <template #item.actif="{ item }">
              <VChip :color="item.actif ? 'success' : 'default'" size="small">
                {{ item.actif ? 'Actif' : 'Inactif' }}
              </VChip>
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

  <!-- Dialog Création/Édition -->
  <VDialog v-model="dialog" max-width="600">
    <VCard :title="isEditing ? 'Modifier la banque' : 'Nouvelle banque'">
      <VCardText>
        <VRow>
          <VCol cols="12" md="6">
            <VTextField v-model="form.code" label="Code *" :disabled="isEditing" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField v-model="form.sigle" label="Sigle" />
          </VCol>
          <VCol cols="12">
            <VTextField v-model="form.libelle" label="Libellé *" />
          </VCol>
          <VCol cols="12">
            <VTextField v-model="form.adresse" label="Adresse" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField v-model="form.telephone" label="Téléphone" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField v-model="form.email" label="Email" type="email" />
          </VCol>
          <VCol cols="12">
            <VSwitch v-model="form.actif" label="Banque active" color="primary" />
          </VCol>
        </VRow>
      </VCardText>
      <VCardActions class="justify-end">
        <VBtn variant="tonal" @click="dialog = false">Annuler</VBtn>
        <VBtn color="primary" @click="save">{{ isEditing ? 'Modifier' : 'Créer' }}</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Dialog Suppression -->
  <VDialog v-model="deleteDialog" max-width="400">
    <VCard title="Confirmer la suppression">
      <VCardText>
        Voulez-vous vraiment supprimer la banque <strong>{{ selectedItem?.libelle }}</strong> ?
      </VCardText>
      <VCardActions class="justify-end">
        <VBtn variant="tonal" @click="deleteDialog = false">Annuler</VBtn>
        <VBtn color="error" @click="confirmDelete">Supprimer</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <VSnackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">
    {{ snackbar.text }}
  </VSnackbar>
</template>
