<script setup lang="ts">
import { useApi } from '@/composables/useApi'

definePage({ meta: { title: 'Permissions' } })

const snackbar = ref({ show: false, text: '', color: 'success' })
const dialog = ref(false)
const deleteDialog = ref(false)
const isEditing = ref(false)
const selectedItem = ref<any>(null)
const searchQuery = ref('')
const filterGroupe = ref('')
const itemsPerPage = ref(25)
const page = ref(1)
const sortBy = ref([{ key: 'code', order: 'asc' }])
const formRef = ref<any>(null)
const isSaving = ref(false)

const permissions = ref<any[]>([])
const total = ref(0)
const isLoading = ref(false)

const form = ref({ code: '', libelle: '', groupe: '' })

// Groupes métier CANAM prédéfinis
const GROUPES_PREDEFINIS = [
  { title: 'DASHBOARD — Tableau de bord', value: 'DASHBOARD' },
  { title: 'AVIS — Avis de passation', value: 'AVIS' },
  { title: 'DEPOUILLEMENTS — Dépouillements', value: 'DEPOUILLEMENTS' },
  { title: 'PVS — Procès-verbaux', value: 'PVS' },
  { title: 'CONTRATS — Contrats', value: 'CONTRATS' },
  { title: 'FOURNISSEURS — Fournisseurs', value: 'FOURNISSEURS' },
  { title: 'REFERENTIELS — Référentiels', value: 'REFERENTIELS' },
  { title: 'GED — Documents', value: 'GED' },
  { title: 'FINANCES — Finances', value: 'FINANCES' },
  { title: 'RAPPORTS — Rapports', value: 'RAPPORTS' },
  { title: 'USERS — Utilisateurs', value: 'USERS' },
  { title: 'ROLES — Rôles', value: 'ROLES' },
  { title: 'PERMISSIONS — Permissions', value: 'PERMISSIONS' },
  { title: 'AUDIT — Audit', value: 'AUDIT' },
  { title: 'SYSTEM — Système', value: 'SYSTEM' },
]

// Actions suggérées selon le groupe
const ACTIONS_SUGGEREES: Record<string, string[]> = {
  DASHBOARD: ['READ'],
  AVIS: ['READ', 'CREATE', 'EDIT', 'DELETE', 'SUBMIT', 'APPROVE', 'REJECT', 'PUBLISH', 'CLOSE'],
  DEPOUILLEMENTS: ['READ', 'CREATE', 'EDIT', 'DELETE', 'SUBMIT', 'APPROVE', 'REJECT'],
  PVS: ['READ', 'CREATE', 'EDIT', 'DELETE', 'SUBMIT', 'APPROVE', 'REJECT', 'GENERATE_PDF', 'UPLOAD_SIGNE'],
  CONTRATS: ['READ', 'CREATE', 'EDIT', 'DELETE', 'SUBMIT', 'APPROVE', 'REJECT', 'ARCHIVE', 'ETAPES'],
  FOURNISSEURS: ['READ', 'CREATE', 'EDIT', 'DELETE'],
  REFERENTIELS: ['READ', 'CREATE', 'EDIT', 'DELETE'],
  GED: ['READ', 'UPLOAD', 'DOWNLOAD', 'DELETE'],
  FINANCES: ['READ', 'CREATE', 'EDIT', 'APPROVE'],
  RAPPORTS: ['READ', 'EXPORT'],
  USERS: ['READ', 'CREATE', 'EDIT', 'DELETE', 'MANAGE_ROLES', 'MANAGE_STATUS'],
  ROLES: ['READ', 'CREATE', 'EDIT', 'DELETE'],
  PERMISSIONS: ['READ', 'CREATE', 'EDIT', 'DELETE'],
  AUDIT: ['READ', 'EXPORT'],
  SYSTEM: ['CONFIG', 'SECURITY'],
}

const actionsSuggerees = computed(() =>
  (ACTIONS_SUGGEREES[form.value.groupe] ?? []).map(a => ({
    title: a,
    value: `${form.value.groupe}_${a}`,
  })),
)

// Groupes déduits du préfixe du code (ex: CONTRATS_READ → CONTRATS)
const groupeFromCode = (code: string) => code.split('_')[0] ?? ''

const groupes = computed(() => {
  const set = new Set(permissions.value.map(p => groupeFromCode(p.code)))
  return Array.from(set).sort().map(g => ({ title: g, value: g }))
})

const loadData = async () => {
  isLoading.value = true
  try {
    const params = new URLSearchParams({
      q: searchQuery.value,
      itemsPerPage: itemsPerPage.value.toString(),
      page: page.value.toString(),
      sortBy: sortBy.value[0]?.key ?? 'code',
      sortDesc: (sortBy.value[0]?.order === 'desc').toString(),
    })
    const { data } = await useApi(`/permissions?${params}`).json()
    if (data.value?.permissions) {
      permissions.value = data.value.permissions
      total.value = data.value.totalPermissions ?? data.value.permissions.length
    }
    else {
      permissions.value = data.value?.data ?? []
      total.value = data.value?.total ?? 0
    }
  }
  finally {
    isLoading.value = false
  }
}

watch([searchQuery, filterGroupe, itemsPerPage, page, sortBy], loadData, { deep: true })
onMounted(loadData)

const filteredPermissions = computed(() => {
  if (!filterGroupe.value)
    return permissions.value
  return permissions.value.filter(p => groupeFromCode(p.code) === filterGroupe.value)
})

const openCreate = () => {
  isEditing.value = false
  form.value = { code: '', libelle: '', groupe: '' }
  dialog.value = true
}

const openEdit = (item: any) => {
  isEditing.value = true
  selectedItem.value = item
  form.value = { code: item.code, libelle: item.libelle, groupe: groupeFromCode(item.code) }
  dialog.value = true
}

const openDelete = (item: any) => {
  selectedItem.value = item
  deleteDialog.value = true
}

// Quand le groupe change, pré-remplir le code si vide
watch(() => form.value.groupe, (newGroupe) => {
  if (!isEditing.value && newGroupe && !form.value.code.startsWith(newGroupe))
    form.value.code = `${newGroupe}_`
})

// Quand le code change, synchroniser le groupe
watch(() => form.value.code, (newCode) => {
  if (!isEditing.value) {
    const prefix = newCode.split('_')[0]
    if (prefix && GROUPES_PREDEFINIS.some(g => g.value === prefix))
      form.value.groupe = prefix
  }
})

const save = async () => {
  const { valid } = await formRef.value?.validate()
  if (!valid)
    return

  isSaving.value = true
  try {
    const payload = { code: form.value.code.toUpperCase(), libelle: form.value.libelle }
    if (isEditing.value) {
      await useApi(`/permissions/${selectedItem.value.id}`).put(payload).json()
      dialog.value = false
      snackbar.value = { show: true, text: 'Permission modifiée avec succès', color: 'success' }
      await loadData()
    }
    else {
      await useApi('/permissions').post(payload).json()
      dialog.value = false
      snackbar.value = { show: true, text: 'Permission créée avec succès', color: 'success' }
      // Afficher la nouvelle permission : recherche sur son code, page 1, pas de filtre groupe (le watch rappellera loadData)
      searchQuery.value = payload.code
      filterGroupe.value = ''
      page.value = 1
    }
  }
  catch (e: any) {
    snackbar.value = {
      show: true,
      text: e?.data?.errors?.code?.[0] ?? e?.data?.message ?? 'Une erreur est survenue',
      color: 'error',
    }
  }
  finally {
    isSaving.value = false
  }
}

const confirmDelete = async () => {
  try {
    await useApi(`/permissions/${selectedItem.value.id}`).delete().json()
    deleteDialog.value = false
    snackbar.value = { show: true, text: 'Permission supprimée avec succès', color: 'success' }
    await loadData()
  }
  catch {
    snackbar.value = { show: true, text: 'Impossible de supprimer (utilisée par des rôles ?)', color: 'error' }
  }
}

// Grouper les permissions par préfixe pour l'affichage
const permissionsGrouped = computed(() => {
  const map: Record<string, any[]> = {}
  filteredPermissions.value.forEach(p => {
    const g = groupeFromCode(p.code)
    if (!map[g])
      map[g] = []
    map[g].push(p)
  })
  return Object.entries(map).sort(([a], [b]) => a.localeCompare(b))
})

const groupColors: Record<string, string> = {
  AVIS: 'primary',
  CONTRATS: 'success',
  DEPOUILLEMENTS: 'info',
  PVS: 'teal',
  FOURNISSEURS: 'orange',
  REFERENTIELS: 'purple',
  USERS: 'blue',
  ROLES: 'indigo',
  PERMISSIONS: 'deep-purple',
  AUDIT: 'grey',
  DASHBOARD: 'cyan',
  SYSTEM: 'red',
  GED: 'brown',
  FINANCES: 'green',
  RAPPORTS: 'pink',
}
const groupColor = (g: string) => groupColors[g] ?? 'default'

// Règles de validation
const requiredRule = (v: string) => !!v?.trim() || 'Ce champ est obligatoire'
const codeFormatRule = (v: string) => /^[A-Z][A-Z0-9_]*$/.test(v) || 'Le code doit être en MAJUSCULES (lettres, chiffres, _)'
const codeUnderscoreRule = (v: string) => v.includes('_') || 'Le code doit contenir un séparateur _ (ex: CONTRATS_READ)'
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardTitle class="d-flex align-center pa-4">
          <VIcon icon="tabler-shield-lock" class="me-2" />
          Gestion des Permissions
          <VChip class="ms-2" size="small" color="primary">
            {{ total }}
          </VChip>
          <VSpacer />
          <VBtn prepend-icon="tabler-plus" color="primary" @click="openCreate">
            Nouvelle Permission
          </VBtn>
        </VCardTitle>

        <VDivider />

        <VCardText>
          <VRow class="mb-4">
            <VCol cols="12" md="4">
              <VTextField
                v-model="searchQuery"
                placeholder="Rechercher par code ou libellé..."
                prepend-inner-icon="tabler-search"
                density="compact"
                clearable
              />
            </VCol>
            <VCol cols="12" md="3">
              <VSelect
                v-model="filterGroupe"
                :items="[{ title: 'Tous les groupes', value: '' }, ...groupes]"
                label="Filtrer par groupe"
                density="compact"
                clearable
              />
            </VCol>
            <VCol cols="12" md="5" class="d-flex align-center justify-end gap-2">
              <VChip
                v-for="[g, perms] in permissionsGrouped"
                :key="g"
                :color="groupColor(g)"
                size="x-small"
                variant="tonal"
                class="cursor-pointer"
                @click="filterGroupe = filterGroupe === g ? '' : g"
              >
                {{ g }} ({{ perms.length }})
              </VChip>
            </VCol>
          </VRow>

          <!-- Vue groupée -->
          <div v-if="!isLoading && permissionsGrouped.length > 0">
            <VExpansionPanels multiple variant="accordion">
              <VExpansionPanel
                v-for="[groupe, perms] in permissionsGrouped"
                :key="groupe"
              >
                <VExpansionPanelTitle>
                  <div class="d-flex align-center gap-3">
                    <VChip :color="groupColor(groupe)" size="small" variant="tonal">
                      {{ groupe }}
                    </VChip>
                    <span class="text-body-2 text-medium-emphasis">{{ perms.length }} permission(s)</span>
                  </div>
                </VExpansionPanelTitle>
                <VExpansionPanelText>
                  <VTable density="compact">
                    <thead>
                      <tr>
                        <th>Code</th>
                        <th>Libellé</th>
                        <th class="text-end">
                          Actions
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="perm in perms" :key="perm.id">
                        <td>
                          <VChip :color="groupColor(groupe)" size="x-small" variant="tonal" label>
                            {{ perm.code }}
                          </VChip>
                        </td>
                        <td class="text-body-2">
                          {{ perm.libelle }}
                        </td>
                        <td class="text-end">
                          <VBtn icon variant="text" size="small" color="primary" @click="openEdit(perm)">
                            <VIcon icon="tabler-edit" size="16" />
                          </VBtn>
                          <VBtn icon variant="text" size="small" color="error" @click="openDelete(perm)">
                            <VIcon icon="tabler-trash" size="16" />
                          </VBtn>
                        </td>
                      </tr>
                    </tbody>
                  </VTable>
                </VExpansionPanelText>
              </VExpansionPanel>
            </VExpansionPanels>
          </div>

          <div v-else-if="isLoading" class="d-flex justify-center py-8">
            <VProgressCircular indeterminate color="primary" />
          </div>

          <div v-else class="text-center py-8 text-medium-emphasis">
            <VIcon icon="tabler-shield-off" size="48" class="mb-2 opacity-30" />
            <p>Aucune permission trouvée</p>
          </div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <!-- Dialog Création / Édition -->
  <VDialog v-model="dialog" max-width="580" persistent>
    <VCard :title="isEditing ? 'Modifier la permission' : 'Nouvelle permission'">
      <VCardText>
        <VForm ref="formRef">
          <VRow>
            <!-- Groupe (sélecteur) -->
            <VCol cols="12" md="5">
              <VAutocomplete
                v-model="form.groupe"
                :items="GROUPES_PREDEFINIS"
                label="Groupe *"
                placeholder="Ex: CONTRATS"
                :disabled="isEditing"
                :rules="[requiredRule]"
                hint="Catégorie fonctionnelle"
                persistent-hint
              />
            </VCol>

            <!-- Code complet -->
            <VCol cols="12" md="7">
              <VAutocomplete
                v-model="form.code"
                :items="actionsSuggerees"
                label="Code complet *"
                placeholder="Ex: CONTRATS_READ"
                :disabled="isEditing"
                :rules="[requiredRule, codeFormatRule, codeUnderscoreRule]"
                hint="Format : GROUPE_ACTION"
                persistent-hint
                @update:model-value="form.code = (form.code ?? '').toUpperCase()"
              />
            </VCol>

            <!-- Libellé -->
            <VCol cols="12">
              <VTextField
                v-model="form.libelle"
                label="Libellé *"
                placeholder="Ex: Consulter les contrats"
                :rules="[requiredRule]"
              />
            </VCol>
          </VRow>

          <!-- Alerte cohérence groupe/code -->
          <VAlert
            v-if="!isEditing && form.groupe && form.code && !form.code.startsWith(form.groupe)"
            type="warning"
            variant="tonal"
            class="mt-3"
            density="compact"
            icon="tabler-alert-triangle"
          >
            Le code <strong>{{ form.code }}</strong> ne commence pas par le groupe
            <strong>{{ form.groupe }}</strong>. Vérifiez la cohérence.
          </VAlert>

          <!-- Aperçu -->
          <VAlert
            v-if="form.code && form.libelle"
            type="info"
            variant="tonal"
            class="mt-3"
            density="compact"
            icon="tabler-eye"
          >
            Aperçu :
            <VChip :color="groupColor(form.groupe)" size="x-small" variant="tonal" label class="mx-1">
              {{ form.code.toUpperCase() }}
            </VChip>
            — {{ form.libelle }}
          </VAlert>
        </VForm>
      </VCardText>

      <VCardActions class="justify-end pa-4 gap-2">
        <VBtn variant="tonal" :disabled="isSaving" @click="dialog = false">
          Annuler
        </VBtn>
        <VBtn color="primary" :loading="isSaving" @click="save">
          {{ isEditing ? 'Modifier' : 'Créer' }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Dialog Suppression -->
  <VDialog v-model="deleteDialog" max-width="420">
    <VCard>
      <VCardTitle class="d-flex align-center gap-2 pa-4">
        <VIcon icon="tabler-alert-triangle" color="error" />
        Confirmer la suppression
      </VCardTitle>
      <VCardText>
        Voulez-vous vraiment supprimer la permission
        <VChip size="small" color="error" class="mx-1">
          {{ selectedItem?.code }}
        </VChip> ?
        <br>
        <span class="text-caption text-medium-emphasis">
          Les rôles qui possèdent cette permission la perdront automatiquement.
        </span>
      </VCardText>
      <VCardActions class="justify-end pa-4">
        <VBtn variant="tonal" @click="deleteDialog = false">
          Annuler
        </VBtn>
        <VBtn color="error" @click="confirmDelete">
          Supprimer
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <VSnackbar v-model="snackbar.show" :color="snackbar.color" timeout="4000" location="top right">
    {{ snackbar.text }}
  </VSnackbar>
</template>
