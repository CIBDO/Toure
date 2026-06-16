<script setup lang="ts">
import { useFournisseursStore } from '@/stores/fournisseurs'
import { useDomainesStore } from '@/stores/domaines'
import { useBanquesStore } from '@/stores/banques'

definePage({ meta: { title: 'Fournisseurs' } })

const store = useFournisseursStore()
const domainesStore = useDomainesStore()
const banquesStore = useBanquesStore()

const snackbar = ref({ show: false, text: '', color: 'success' })
const dialog = ref(false)
const deleteDialog = ref(false)
const isEditing = ref(false)
const selectedItem = ref<any>(null)
const searchQuery = ref('')
const filterStatut = ref('')
const filterDomaine = ref('')
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref([{ key: 'raison_sociale', order: 'asc' }])
const activeTab = ref('info')

const civiliteOptions = [
  { title: 'M.', value: 'M.' },
  { title: 'Mme', value: 'Mme' },
  { title: 'Dr', value: 'Dr' },
  { title: 'Pr', value: 'Pr' },
]

const qualiteFonctionOptions = [
  { title: 'PDG', value: 'PDG' },
  { title: 'DG', value: 'DG' },
  { title: 'Gérant', value: 'Gérant' },
  { title: 'Directeur', value: 'Directeur' },
  { title: 'Représentant légal', value: 'Représentant légal' },
  { title: 'Mandataire', value: 'Mandataire' },
  { title: 'Autre', value: 'Autre' },
]

const regionOptions = [
  'Bamako', 'Kayes', 'Koulikoro', 'Sikasso', 'Ségou', 'Mopti', 'Tombouctou', 'Gao', 'Kidal',
]

const emptyForm = () => ({
  code: '',
  civilite: '',
  qualite_fonction: '',
  raison_sociale: '',
  sigle: '',
  nif: '',
  rc: '',
  telephone: '',
  fax: '',
  email: '',
  adresse: '',
  ville: '',
  region: '',
  pays: 'Mali',
  representant: '',
  fonction_representant: '',
  domaine_activite_id: null as number | null,
  statut: 'actif',
  observations: '',
  banques: [] as any[],
})

const form = ref(emptyForm())

const headers = [
  { title: 'Code', key: 'code', sortable: true },
  { title: 'Raison Sociale', key: 'raison_sociale', sortable: true },
  { title: 'NIF', key: 'nif', sortable: false },
  { title: 'RC', key: 'rc', sortable: false },
  { title: 'Domaine', key: 'domaine_activite', sortable: false },
  { title: 'Téléphone', key: 'telephone', sortable: false },
  { title: 'Ville', key: 'ville', sortable: true },
  { title: 'Statut', key: 'statut', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false },
]

const statutOptions = [
  { title: 'Actif', value: 'actif' },
  { title: 'Suspendu', value: 'suspendu' },
  { title: 'Blacklisté', value: 'blackliste' },
]

const statutColor = (s: string) => ({ actif: 'success', suspendu: 'warning', blackliste: 'error' }[s] || 'default')

const loadData = async () => {
  await store.fetchFournisseurs({
    q: searchQuery.value,
    statut: filterStatut.value,
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
    banquesStore.fetchBanques({ itemsPerPage: -1, actif: true }),
  ])
})

watch([searchQuery, filterStatut, filterDomaine, itemsPerPage, page, sortBy], loadData, { deep: true })

const openCreate = () => {
  isEditing.value = false
  form.value = emptyForm()
  activeTab.value = 'info'
  dialog.value = true
}

const openEdit = async (item: any) => {
  isEditing.value = true
  selectedItem.value = item
  activeTab.value = 'info'
  const full = await store.fetchFournisseur(item.id)
  form.value = {
    ...emptyForm(),
    ...full,
    banques: full.banques?.map((b: any) => ({
      banque_id: b.banque_id,
      numero_compte: b.numero_compte,
      rib: b.rib ?? '',
      swift: b.swift ?? '',
      iban: b.iban ?? '',
      intitule_compte: b.intitule_compte ?? '',
      principal: b.principal ?? false,
    })) ?? [],
  }
  dialog.value = true
}

const openDelete = (item: any) => {
  selectedItem.value = item
  deleteDialog.value = true
}

const addBanque = () => {
  form.value.banques.push({
    banque_id: null, numero_compte: '', rib: '', swift: '', iban: '',
    intitule_compte: '', principal: false,
  })
}

const removeBanque = (index: number) => {
  form.value.banques.splice(index, 1)
}

const save = async () => {
  try {
    if (isEditing.value)
      await store.updateFournisseur(selectedItem.value.id, form.value)
    else
      await store.createFournisseur(form.value)
    dialog.value = false
    snackbar.value = { show: true, text: `Fournisseur ${isEditing.value ? 'modifié' : 'créé'} avec succès`, color: 'success' }
    await loadData()
  }
  catch (e: any) {
    const msg = e?.data?.message || e?.message || 'Une erreur est survenue'
    snackbar.value = { show: true, text: msg, color: 'error' }
  }
}

const confirmDelete = async () => {
  try {
    await store.deleteFournisseur(selectedItem.value.id)
    deleteDialog.value = false
    snackbar.value = { show: true, text: 'Fournisseur supprimé avec succès', color: 'success' }
    await loadData()
  }
  catch {
    snackbar.value = { show: true, text: 'Impossible de supprimer ce fournisseur (contrats liés ?)', color: 'error' }
  }
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardTitle class="d-flex align-center pa-4">
          <VIcon icon="tabler-building-store" class="me-2" />
          Gestion des Fournisseurs
          <VSpacer />
          <VBtn prepend-icon="tabler-plus" color="primary" @click="openCreate">
            Nouveau Fournisseur
          </VBtn>
        </VCardTitle>

        <VDivider />

        <VCardText>
          <!-- Filtres -->
          <VRow class="mb-4">
            <VCol cols="12" md="4">
              <VTextField
                v-model="searchQuery"
                placeholder="Rechercher (nom, code, NIF, RC)..."
                prepend-inner-icon="tabler-search"
                density="compact"
                clearable
              />
            </VCol>
            <VCol cols="12" md="3">
              <VSelect
                v-model="filterStatut"
                :items="[{ title: 'Tous les statuts', value: '' }, ...statutOptions]"
                label="Statut"
                density="compact"
                clearable
              />
            </VCol>
            <VCol cols="12" md="3">
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
            :items="store.fournisseurs"
            :items-length="store.total"
            :loading="store.isLoading"
            class="elevation-0"
          >
            <template #item.domaine_activite="{ item }">
              <span class="text-caption">{{ item.domaine_activite?.libelle ?? '-' }}</span>
            </template>
            <template #item.statut="{ item }">
              <VChip :color="statutColor(item.statut)" size="small" class="text-capitalize">
                {{ item.statut }}
              </VChip>
            </template>
            <template #item.actions="{ item }">
              <VBtn icon variant="text" size="small" color="primary" @click="openEdit(item)">
                <VIcon icon="tabler-edit" />
                <VTooltip activator="parent">Modifier</VTooltip>
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
  <VDialog v-model="dialog" max-width="1000" scrollable>
    <VCard :title="isEditing ? 'Modifier le fournisseur' : 'Nouveau fournisseur'">
      <VCardText class="pa-0">
        <VTabs v-model="activeTab" bg-color="surface">
          <VTab value="info">
            <VIcon icon="tabler-building" class="me-1" size="18" />
            Informations générales
          </VTab>
          <VTab value="representant">
            <VIcon icon="tabler-user-check" class="me-1" size="18" />
            Représentant
          </VTab>
          <VTab value="banques">
            <VIcon icon="tabler-building-bank" class="me-1" size="18" />
            Informations bancaires
            <VChip v-if="form.banques.length" size="x-small" color="primary" class="ms-1">
              {{ form.banques.length }}
            </VChip>
          </VTab>
        </VTabs>

        <VDivider />

        <div class="pa-4">
          <VTabsWindow v-model="activeTab">

            <!-- ── Onglet Informations générales ── -->
            <VTabsWindowItem value="info">
              <VRow class="mt-1">
                <VCol cols="12" md="3">
                  <VTextField v-model="form.code" label="Code *" :disabled="isEditing" hint="Ex: F-001" persistent-hint />
                </VCol>
                <VCol cols="12" md="9">
                  <VTextField v-model="form.raison_sociale" label="Raison sociale *" />
                </VCol>
                <VCol cols="12" md="4">
                  <VTextField v-model="form.sigle" label="Sigle / Abréviation" />
                </VCol>
                <VCol cols="12" md="4">
                  <VTextField v-model="form.nif" label="NIF (Numéro d'Identification Fiscale)" />
                </VCol>
                <VCol cols="12" md="4">
                  <VTextField v-model="form.rc" label="RC (Registre du Commerce)" />
                </VCol>
                <VCol cols="12" md="4">
                  <VTextField v-model="form.telephone" label="Téléphone" prepend-inner-icon="tabler-phone" />
                </VCol>
                <VCol cols="12" md="4">
                  <VTextField v-model="form.fax" label="Fax" prepend-inner-icon="tabler-device-floppy" />
                </VCol>
                <VCol cols="12" md="4">
                  <VTextField v-model="form.email" label="Email" type="email" prepend-inner-icon="tabler-mail" />
                </VCol>
                <VCol cols="12">
                  <VTextField v-model="form.adresse" label="Adresse" prepend-inner-icon="tabler-map-pin" />
                </VCol>
                <VCol cols="12" md="4">
                  <VTextField v-model="form.ville" label="Ville" />
                </VCol>
                <VCol cols="12" md="4">
                  <VAutocomplete
                    v-model="form.region"
                    :items="regionOptions"
                    label="Région"
                    clearable
                  />
                </VCol>
                <VCol cols="12" md="4">
                  <VTextField v-model="form.pays" label="Pays" />
                </VCol>
                <VCol cols="12" md="6">
                  <VSelect
                    v-model="form.domaine_activite_id"
                    :items="domainesStore.domaines.map(d => ({ title: d.libelle, value: d.id }))"
                    label="Domaine d'activité"
                    clearable
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <VSelect
                    v-model="form.statut"
                    :items="statutOptions"
                    label="Statut"
                  />
                </VCol>
                <VCol cols="12">
                  <VTextarea v-model="form.observations" label="Observations / Notes" rows="3" />
                </VCol>
              </VRow>
            </VTabsWindowItem>

            <!-- ── Onglet Représentant ── -->
            <VTabsWindowItem value="representant">
              <VRow class="mt-1">
                <VCol cols="12">
                  <p class="text-subtitle-2 text-medium-emphasis mb-3">
                    Informations sur le représentant légal ou mandataire
                  </p>
                </VCol>
                <VCol cols="12" md="3">
                  <VSelect
                    v-model="form.civilite"
                    :items="civiliteOptions"
                    label="Civilité"
                    clearable
                  />
                </VCol>
                <VCol cols="12" md="4">
                  <VTextField v-model="form.representant" label="Nom complet du représentant" />
                </VCol>
                <VCol cols="12" md="5">
                  <VAutocomplete
                    v-model="form.qualite_fonction"
                    :items="qualiteFonctionOptions"
                    label="Qualité / Fonction"
                    clearable
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField
                    v-model="form.fonction_representant"
                    label="Intitulé de poste (libre)"
                    hint="Ex: Directeur Général Adjoint"
                    persistent-hint
                  />
                </VCol>
              </VRow>
            </VTabsWindowItem>

            <!-- ── Onglet Informations bancaires ── -->
            <VTabsWindowItem value="banques">
              <div class="mt-1">
                <div v-if="form.banques.length === 0" class="text-center py-8 text-medium-emphasis">
                  <VIcon icon="tabler-building-bank" size="48" class="mb-3 opacity-30" />
                  <p class="text-body-2">Aucun compte bancaire enregistré</p>
                </div>

                <div
                  v-for="(banque, index) in form.banques"
                  :key="index"
                  class="mb-4 pa-4 border rounded"
                  :class="banque.principal ? 'border-primary' : ''"
                >
                  <div class="d-flex align-center mb-3">
                    <VChip v-if="banque.principal" color="primary" size="small" prepend-icon="tabler-star">
                      Compte principal
                    </VChip>
                    <span v-else class="text-caption text-medium-emphasis">Compte {{ index + 1 }}</span>
                    <VSpacer />
                    <VBtn icon variant="text" size="small" color="error" @click="removeBanque(index)">
                      <VIcon icon="tabler-trash" />
                    </VBtn>
                  </div>

                  <VRow>
                    <VCol cols="12" md="6">
                      <VSelect
                        v-model="banque.banque_id"
                        :items="banquesStore.banques.map(b => ({ title: `${b.libelle}${b.sigle ? ' (' + b.sigle + ')' : ''}`, value: b.id }))"
                        label="Banque *"
                      />
                    </VCol>
                    <VCol cols="12" md="6">
                      <VTextField v-model="banque.intitule_compte" label="Intitulé du compte" />
                    </VCol>
                    <VCol cols="12" md="6">
                      <VTextField v-model="banque.numero_compte" label="Numéro de compte *" />
                    </VCol>
                    <VCol cols="12" md="6">
                      <VTextField v-model="banque.rib" label="RIB (Relevé d'Identité Bancaire)" />
                    </VCol>
                    <VCol cols="12" md="6">
                      <VTextField v-model="banque.iban" label="IBAN" />
                    </VCol>
                    <VCol cols="12" md="6">
                      <VTextField v-model="banque.swift" label="Code SWIFT / BIC" />
                    </VCol>
                    <VCol cols="12">
                      <VCheckbox
                        v-model="banque.principal"
                        label="Définir comme compte principal"
                        color="primary"
                        @change="banque.principal && form.banques.forEach((b, i) => { if (i !== index) b.principal = false })"
                      />
                    </VCol>
                  </VRow>
                </div>

                <VBtn
                  prepend-icon="tabler-plus"
                  variant="tonal"
                  color="primary"
                  class="mt-2"
                  @click="addBanque"
                >
                  Ajouter un compte bancaire
                </VBtn>
              </div>
            </VTabsWindowItem>

          </VTabsWindow>
        </div>
      </VCardText>

      <VDivider />
      <VCardActions class="justify-end pa-4">
        <VBtn variant="tonal" @click="dialog = false">Annuler</VBtn>
        <VBtn color="primary" prepend-icon="tabler-device-floppy" @click="save">
          {{ isEditing ? 'Enregistrer les modifications' : 'Créer le fournisseur' }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- ─── Dialog Suppression ─── -->
  <VDialog v-model="deleteDialog" max-width="450">
    <VCard>
      <VCardTitle class="d-flex align-center gap-2 pa-4">
        <VIcon icon="tabler-alert-triangle" color="error" />
        Confirmer la suppression
      </VCardTitle>
      <VCardText>
        Voulez-vous vraiment supprimer le fournisseur
        <strong>{{ selectedItem?.raison_sociale }}</strong> ?
        <br />
        <span class="text-caption text-medium-emphasis">
          Cette action est irréversible. Les contrats liés ne seront pas supprimés.
        </span>
      </VCardText>
      <VCardActions class="justify-end pa-4">
        <VBtn variant="tonal" @click="deleteDialog = false">Annuler</VBtn>
        <VBtn color="error" prepend-icon="tabler-trash" @click="confirmDelete">Supprimer</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <VSnackbar v-model="snackbar.show" :color="snackbar.color" timeout="4000" location="top right">
    {{ snackbar.text }}
  </VSnackbar>
</template>
