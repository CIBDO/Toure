<script setup lang="ts">
import { MODE_PASSATION_OPTIONS, modePassationLabel } from '@/constants/modesPassation'
import { useFournisseursStore } from '@/stores/fournisseurs'
import type { Fournisseur } from '@/stores/fournisseurs'
import { useDomainesStore } from '@/stores/domaines'
import { useBanquesStore } from '@/stores/banques'

definePage({ meta: { title: 'Fournisseurs' } })

const store = useFournisseursStore()
const domainesStore = useDomainesStore()
const banquesStore = useBanquesStore()

const snackbar = ref({ show: false, text: '', color: 'success' })
const dialog = ref(false)
const deleteDialog = ref(false)
const detailsDialog = ref(false)
const detailsItem = ref<Fournisseur | null>(null)
const isLoadingDetails = ref(false)
const isEditing = ref(false)
const selectedItem = ref<any>(null)
const searchQuery = ref('')
const filterStatut = ref('')
const filterDomaine = ref('')
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref([{ key: 'raison_sociale', order: 'asc' }])
const activeTab = ref('info')
const formRef = ref<any>(null)

const modeOptions = MODE_PASSATION_OPTIONS

const modesRule = (v: string[]) => (Array.isArray(v) && v.length > 0) || 'Sélectionnez au moins un mode de passation'
const dureeRangeRule = () => {
  const min = form.value.duree_min
  const max = form.value.duree_max
  if (min != null && max != null && max < min)
    return 'La durée max. doit être supérieure ou égale à la durée min.'
  return true
}

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
  modes_passation: ['AO_OUVERT'] as string[],
  duree_min: null as number | null,
  duree_max: null as number | null,
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
  { title: 'Modes passation', key: 'modes_passation', sortable: false },
  { title: 'Téléphone', key: 'telephone', sortable: false },
  { title: 'Ville', key: 'ville', sortable: true },
  { title: 'Statut', key: 'statut', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, width: '140px' },
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
    modes_passation: full.modes_passation?.length ? full.modes_passation : ['AO_OUVERT'],
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

const openDetails = async (item: any) => {
  isLoadingDetails.value = true
  detailsDialog.value = true
  detailsItem.value = null
  try {
    detailsItem.value = await store.fetchFournisseur(item.id)
  }
  catch {
    detailsDialog.value = false
    snackbar.value = { show: true, text: 'Impossible de charger les détails du fournisseur', color: 'error' }
  }
  finally {
    isLoadingDetails.value = false
  }
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
  const { valid } = await formRef.value?.validate()
  if (!valid) {
    activeTab.value = 'info'
    snackbar.value = { show: true, text: 'Veuillez corriger les champs obligatoires', color: 'error' }
    return
  }

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
            <template #item.modes_passation="{ item }">
              <div class="d-flex flex-wrap gap-1 py-1">
                <VChip
                  v-for="mode in (item.modes_passation ?? [])"
                  :key="mode"
                  size="x-small"
                  variant="tonal"
                  color="info"
                >
                  {{ modePassationLabel(mode) }}
                </VChip>
                <span v-if="!item.modes_passation?.length" class="text-caption">-</span>
              </div>
            </template>
            <template #item.statut="{ item }">
              <VChip :color="statutColor(item.statut)" size="small" class="text-capitalize">
                {{ item.statut }}
              </VChip>
            </template>
            <template #item.actions="{ item }">
              <div class="d-flex align-center justify-center gap-0">
                <VBtn icon variant="text" size="small" color="secondary" @click="openDetails(item)">
                  <VIcon icon="tabler-eye" />
                  <VTooltip activator="parent">Voir détails</VTooltip>
                </VBtn>
                <VBtn icon variant="text" size="small" color="primary" @click="openEdit(item)">
                  <VIcon icon="tabler-edit" />
                  <VTooltip activator="parent">Modifier</VTooltip>
                </VBtn>
                <VBtn icon variant="text" size="small" color="error" @click="openDelete(item)">
                  <VIcon icon="tabler-trash" />
                  <VTooltip activator="parent">Supprimer</VTooltip>
                </VBtn>
              </div>
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
          <VForm ref="formRef">
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
                  <VSelect
                    v-model="form.modes_passation"
                    :items="modeOptions"
                    label="Modes de passation autorisés *"
                    multiple
                    chips
                    closable-chips
                    :rules="[modesRule]"
                    hint="Modes pour lesquels ce fournisseur peut être invité à un avis"
                    persistent-hint
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField
                    v-model.number="form.duree_min"
                    label="Durée consultation min. (jours)"
                    type="number"
                    min="1"
                    hint="Durée minimale d'avis acceptée (optionnel)"
                    persistent-hint
                    :rules="[dureeRangeRule]"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField
                    v-model.number="form.duree_max"
                    label="Durée consultation max. (jours)"
                    type="number"
                    min="1"
                    hint="Durée maximale d'avis acceptée (optionnel)"
                    persistent-hint
                    :rules="[dureeRangeRule]"
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
          </VForm>
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

  <!-- ─── Dialog Détails ─── -->
  <VDialog v-model="detailsDialog" max-width="900" scrollable>
    <VCard>
      <VCardTitle class="d-flex align-center gap-2 pa-4">
        <VIcon icon="tabler-building-store" color="primary" />
        <template v-if="detailsItem">
          {{ detailsItem.raison_sociale }}
          <VChip v-if="detailsItem.sigle" size="x-small" variant="tonal" class="ms-1">{{ detailsItem.sigle }}</VChip>
        </template>
        <span v-else>Détails du fournisseur</span>
        <VSpacer />
        <VChip v-if="detailsItem" :color="statutColor(detailsItem.statut)" size="small" class="text-capitalize">
          {{ detailsItem.statut }}
        </VChip>
      </VCardTitle>
      <VDivider />

      <VCardText v-if="isLoadingDetails" class="d-flex justify-center pa-12">
        <VProgressCircular indeterminate color="primary" />
      </VCardText>

      <VCardText v-else-if="detailsItem" class="pa-4">
        <p class="text-subtitle-2 font-weight-bold mb-3">Informations générales</p>
        <VRow dense>
          <VCol cols="6" md="3">
            <p class="text-caption text-medium-emphasis">Code</p>
            <p class="text-body-2 mb-2">{{ detailsItem.code || '-' }}</p>
          </VCol>
          <VCol cols="6" md="3">
            <p class="text-caption text-medium-emphasis">NIF</p>
            <p class="text-body-2 mb-2">{{ detailsItem.nif || '-' }}</p>
          </VCol>
          <VCol cols="6" md="3">
            <p class="text-caption text-medium-emphasis">RC</p>
            <p class="text-body-2 mb-2">{{ detailsItem.rc || '-' }}</p>
          </VCol>
          <VCol cols="6" md="3">
            <p class="text-caption text-medium-emphasis">Domaine</p>
            <p class="text-body-2 mb-2">{{ detailsItem.domaine_activite?.libelle || '-' }}</p>
          </VCol>
          <VCol cols="6" md="4">
            <p class="text-caption text-medium-emphasis">Téléphone</p>
            <p class="text-body-2 mb-2">{{ detailsItem.telephone || '-' }}</p>
          </VCol>
          <VCol cols="6" md="4">
            <p class="text-caption text-medium-emphasis">Fax</p>
            <p class="text-body-2 mb-2">{{ detailsItem.fax || '-' }}</p>
          </VCol>
          <VCol cols="6" md="4">
            <p class="text-caption text-medium-emphasis">Email</p>
            <p class="text-body-2 mb-2">{{ detailsItem.email || '-' }}</p>
          </VCol>
          <VCol cols="12">
            <p class="text-caption text-medium-emphasis">Adresse</p>
            <p class="text-body-2 mb-2">
              {{ [detailsItem.adresse, detailsItem.ville, detailsItem.region, detailsItem.pays].filter(Boolean).join(', ') || '-' }}
            </p>
          </VCol>
          <VCol cols="12">
            <p class="text-caption text-medium-emphasis">Modes de passation</p>
            <div class="d-flex flex-wrap gap-1 mb-2">
              <VChip
                v-for="mode in (detailsItem.modes_passation ?? [])"
                :key="mode"
                size="x-small"
                variant="tonal"
                color="info"
              >
                {{ modePassationLabel(mode) }}
              </VChip>
              <span v-if="!detailsItem.modes_passation?.length" class="text-body-2">-</span>
            </div>
          </VCol>
          <VCol v-if="detailsItem.duree_min || detailsItem.duree_max" cols="12" md="6">
            <p class="text-caption text-medium-emphasis">Durée consultation</p>
            <p class="text-body-2 mb-2">
              {{ detailsItem.duree_min ?? '?' }} — {{ detailsItem.duree_max ?? '?' }} jours
            </p>
          </VCol>
        </VRow>

        <VDivider class="my-3" />
        <p class="text-subtitle-2 font-weight-bold mb-3">Représentant</p>
        <VRow dense>
          <VCol cols="12" md="4">
            <p class="text-caption text-medium-emphasis">Nom</p>
            <p class="text-body-2 mb-2">
              {{ [detailsItem.civilite, detailsItem.representant].filter(Boolean).join(' ') || '-' }}
            </p>
          </VCol>
          <VCol cols="12" md="4">
            <p class="text-caption text-medium-emphasis">Qualité / Fonction</p>
            <p class="text-body-2 mb-2">{{ detailsItem.qualite_fonction || detailsItem.fonction_representant || '-' }}</p>
          </VCol>
        </VRow>

        <template v-if="detailsItem.banques?.length">
          <VDivider class="my-3" />
          <p class="text-subtitle-2 font-weight-bold mb-3">
            Comptes bancaires
            <VChip size="x-small" color="primary" class="ms-1">{{ detailsItem.banques.length }}</VChip>
          </p>
          <VTable density="compact">
            <thead>
              <tr>
                <th>Banque</th>
                <th>N° compte</th>
                <th>RIB</th>
                <th>IBAN</th>
                <th>Principal</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="b in detailsItem.banques" :key="b.id ?? b.numero_compte">
                <td class="text-body-2">{{ b.banque?.libelle || '-' }}</td>
                <td class="text-body-2">{{ b.numero_compte || '-' }}</td>
                <td class="text-caption text-medium-emphasis">{{ b.rib || '-' }}</td>
                <td class="text-caption text-medium-emphasis">{{ b.iban || '-' }}</td>
                <td class="text-center">
                  <VIcon v-if="b.principal" icon="tabler-star-filled" color="primary" size="18" />
                  <VIcon v-else icon="tabler-minus" color="default" size="16" />
                </td>
              </tr>
            </tbody>
          </VTable>
        </template>

        <template v-if="detailsItem.observations">
          <VDivider class="my-3" />
          <p class="text-subtitle-2 font-weight-bold mb-2">Observations</p>
          <p class="text-body-2">{{ detailsItem.observations }}</p>
        </template>
      </VCardText>

      <VDivider />
      <VCardActions class="justify-end pa-3">
        <VBtn
          v-if="detailsItem"
          variant="tonal"
          color="primary"
          prepend-icon="tabler-edit"
          @click="detailsDialog = false; openEdit(detailsItem)"
        >
          Modifier
        </VBtn>
        <VBtn variant="tonal" @click="detailsDialog = false">Fermer</VBtn>
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
