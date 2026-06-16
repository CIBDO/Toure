<script setup lang="ts">
import { useComptesBudgetStore } from '@/stores/comptesBudget'

definePage({ meta: { title: 'Comptes Budget' } })

const store = useComptesBudgetStore()
const snackbar = ref({ show: false, text: '', color: 'success' })
const dialog = ref(false)
const deleteDialog = ref(false)
const isEditing = ref(false)
const selectedItem = ref<any>(null)
const searchQuery = ref('')
const filterExercice = ref('')
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref([{ key: 'code', order: 'asc' }])

const form = ref({
  code: '', libelle: '', exercice: new Date().getFullYear().toString(),
  montant_alloue: 0, montant_engage: 0, montant_disponible: 0, description: '', actif: true,
})

const headers = [
  { title: 'Code', key: 'code', sortable: true },
  { title: 'Libellé', key: 'libelle', sortable: true },
  { title: 'Exercice', key: 'exercice', sortable: true },
  { title: 'Montant alloué', key: 'montant_alloue', sortable: true },
  { title: 'Montant engagé', key: 'montant_engage', sortable: true },
  { title: 'Disponible', key: 'montant_disponible', sortable: true },
  { title: 'Statut', key: 'actif', sortable: false },
  { title: 'Actions', key: 'actions', sortable: false },
]

const formatMontant = (v: number) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v)

const loadData = async () => {
  await store.fetchComptes({
    q: searchQuery.value,
    exercice: filterExercice.value,
    itemsPerPage: itemsPerPage.value,
    page: page.value,
    sortBy: sortBy.value[0]?.key,
    sortDesc: sortBy.value[0]?.order === 'desc',
  })
}

watch([searchQuery, filterExercice, itemsPerPage, page, sortBy], loadData, { deep: true })
onMounted(loadData)

const openCreate = () => {
  isEditing.value = false
  form.value = { code: '', libelle: '', exercice: new Date().getFullYear().toString(), montant_alloue: 0, montant_engage: 0, montant_disponible: 0, description: '', actif: true }
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
    const payload = { ...form.value, montant_disponible: form.value.montant_alloue - form.value.montant_engage }
    if (isEditing.value)
      await store.updateCompte(selectedItem.value.id, payload)
    else
      await store.createCompte(payload)
    dialog.value = false
    snackbar.value = { show: true, text: `Compte ${isEditing.value ? 'modifié' : 'créé'} avec succès`, color: 'success' }
    await loadData()
  }
  catch {
    snackbar.value = { show: true, text: 'Une erreur est survenue', color: 'error' }
  }
}

const confirmDelete = async () => {
  try {
    await store.deleteCompte(selectedItem.value.id)
    deleteDialog.value = false
    snackbar.value = { show: true, text: 'Compte supprimé avec succès', color: 'success' }
    await loadData()
  }
  catch {
    snackbar.value = { show: true, text: 'Impossible de supprimer ce compte', color: 'error' }
  }
}

const disponibleColor = (item: any) => {
  const ratio = item.montant_disponible / item.montant_alloue
  if (ratio > 0.5) return 'success'
  if (ratio > 0.2) return 'warning'
  return 'error'
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardTitle class="d-flex align-center pa-4">
          <VIcon icon="tabler-wallet" class="me-2" />
          Comptes Budget
          <VSpacer />
          <VBtn prepend-icon="tabler-plus" color="primary" @click="openCreate">Nouveau Compte</VBtn>
        </VCardTitle>
        <VDivider />
        <VCardText>
          <VRow class="mb-4">
            <VCol cols="12" md="4">
              <VTextField v-model="searchQuery" placeholder="Rechercher..." prepend-inner-icon="tabler-search" density="compact" clearable />
            </VCol>
            <VCol cols="12" md="3">
              <VTextField v-model="filterExercice" placeholder="Exercice (ex: 2026)" density="compact" clearable />
            </VCol>
          </VRow>

          <VDataTableServer
            v-model:items-per-page="itemsPerPage"
            v-model:page="page"
            v-model:sort-by="sortBy"
            :headers="headers"
            :items="store.comptes"
            :items-length="store.total"
            :loading="store.isLoading"
          >
            <template #item.montant_alloue="{ item }">{{ formatMontant(item.montant_alloue) }}</template>
            <template #item.montant_engage="{ item }">{{ formatMontant(item.montant_engage) }}</template>
            <template #item.montant_disponible="{ item }">
              <VChip :color="disponibleColor(item)" size="small">{{ formatMontant(item.montant_disponible) }}</VChip>
            </template>
            <template #item.actif="{ item }">
              <VChip :color="item.actif ? 'success' : 'default'" size="small">{{ item.actif ? 'Actif' : 'Inactif' }}</VChip>
            </template>
            <template #item.actions="{ item }">
              <VBtn icon variant="text" size="small" color="primary" @click="openEdit(item)"><VIcon icon="tabler-edit" /></VBtn>
              <VBtn icon variant="text" size="small" color="error" @click="openDelete(item)"><VIcon icon="tabler-trash" /></VBtn>
            </template>
          </VDataTableServer>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <VDialog v-model="dialog" max-width="700">
    <VCard :title="isEditing ? 'Modifier le compte budget' : 'Nouveau compte budget'">
      <VCardText>
        <VRow>
          <VCol cols="12" md="6">
            <VTextField v-model="form.code" label="Code *" :disabled="isEditing" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField v-model="form.exercice" label="Exercice *" placeholder="2026" />
          </VCol>
          <VCol cols="12">
            <VTextField v-model="form.libelle" label="Libellé *" />
          </VCol>
          <VCol cols="12" md="4">
            <VTextField v-model.number="form.montant_alloue" label="Montant alloué (CFA)" type="number" />
          </VCol>
          <VCol cols="12" md="4">
            <VTextField v-model.number="form.montant_engage" label="Montant engagé (CFA)" type="number" />
          </VCol>
          <VCol cols="12" md="4">
            <VTextField :model-value="form.montant_alloue - form.montant_engage" label="Disponible (CFA)" type="number" readonly />
          </VCol>
          <VCol cols="12">
            <VTextarea v-model="form.description" label="Description" rows="2" />
          </VCol>
          <VCol cols="12">
            <VSwitch v-model="form.actif" label="Compte actif" color="primary" />
          </VCol>
        </VRow>
      </VCardText>
      <VCardActions class="justify-end">
        <VBtn variant="tonal" @click="dialog = false">Annuler</VBtn>
        <VBtn color="primary" @click="save">{{ isEditing ? 'Modifier' : 'Créer' }}</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <VDialog v-model="deleteDialog" max-width="400">
    <VCard title="Confirmer la suppression">
      <VCardText>Voulez-vous vraiment supprimer le compte <strong>{{ selectedItem?.code }}</strong> ?</VCardText>
      <VCardActions class="justify-end">
        <VBtn variant="tonal" @click="deleteDialog = false">Annuler</VBtn>
        <VBtn color="error" @click="confirmDelete">Supprimer</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <VSnackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">{{ snackbar.text }}</VSnackbar>
</template>
