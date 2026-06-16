<script setup lang="ts">
import type { VForm } from 'vuetify/components/VForm'

interface PermissionItem {
  id: number
  code: string
  libelle: string
}

interface RoleIAM {
  id?: number
  code: string
  libelle: string
  permissions?: PermissionItem[]
}

interface Props {
  isDialogVisible: boolean
  role?: RoleIAM | null
}

interface Emit {
  (e: 'update:isDialogVisible', value: boolean): void
  (e: 'submit', value: { code: string; libelle: string; permissions?: number[] }): void
}

const props = withDefaults(defineProps<Props>(), { role: null })
const emit = defineEmits<Emit>()

const isFormValid = ref(false)
const refForm = ref<VForm>()
const code = ref('')
const libelle = ref('')
const selectedPermissions = ref<number[]>([])
const searchPerm = ref('')

// Couleurs par groupe
const groupColors: Record<string, string> = {
  AVIS: 'primary', CONTRATS: 'success', DEPOUILLEMENTS: 'info', PVS: 'teal',
  FOURNISSEURS: 'orange', REFERENTIELS: 'purple', USERS: 'blue', ROLES: 'indigo',
  PERMISSIONS: 'deep-purple', AUDIT: 'grey', DASHBOARD: 'cyan', SYSTEM: 'red',
  GED: 'brown', FINANCES: 'green', RAPPORTS: 'pink',
}
const groupColor = (g: string) => groupColors[g] ?? 'secondary'

// Charger les permissions depuis l'API
const { data: permissionsData } = await useApi<any>('/permissions?itemsPerPage=-1')

const allPermissions = computed<PermissionItem[]>(() =>
  permissionsData.value?.permissions ?? [],
)

// Grouper les permissions par préfixe
const groupeFromCode = (code: string) => code.split('_')[0] ?? ''

const permissionsGrouped = computed(() => {
  const filtered = searchPerm.value
    ? allPermissions.value.filter(p =>
      p.code.toLowerCase().includes(searchPerm.value.toLowerCase())
      || p.libelle.toLowerCase().includes(searchPerm.value.toLowerCase()),
    )
    : allPermissions.value

  const map: Record<string, PermissionItem[]> = {}
  filtered.forEach(p => {
    const g = groupeFromCode(p.code)
    if (!map[g])
      map[g] = []
    map[g].push(p)
  })
  return Object.entries(map).sort(([a], [b]) => a.localeCompare(b))
})

// Sélection par groupe
const isGroupFullySelected = (perms: PermissionItem[]) =>
  perms.every(p => selectedPermissions.value.includes(p.id))

const isGroupPartiallySelected = (perms: PermissionItem[]) =>
  !isGroupFullySelected(perms) && perms.some(p => selectedPermissions.value.includes(p.id))

const toggleGroup = (perms: PermissionItem[]) => {
  if (isGroupFullySelected(perms)) {
    selectedPermissions.value = selectedPermissions.value.filter(
      id => !perms.map(p => p.id).includes(id),
    )
  }
  else {
    const toAdd = perms.map(p => p.id).filter(id => !selectedPermissions.value.includes(id))
    selectedPermissions.value = [...selectedPermissions.value, ...toAdd]
  }
}

const toggleAll = () => {
  if (selectedPermissions.value.length === allPermissions.value.length)
    selectedPermissions.value = []
  else
    selectedPermissions.value = allPermissions.value.map(p => p.id)
}

const isAllSelected = computed(() =>
  allPermissions.value.length > 0
  && selectedPermissions.value.length === allPermissions.value.length,
)
const isPartiallySelected = computed(() =>
  selectedPermissions.value.length > 0
  && selectedPermissions.value.length < allPermissions.value.length,
)

// Réinitialiser quand le dialog s'ouvre
watch(() => props.isDialogVisible, (isVisible) => {
  if (isVisible) {
    searchPerm.value = ''
    if (props.role) {
      code.value = props.role.code || ''
      libelle.value = props.role.libelle || ''
      selectedPermissions.value = props.role.permissions?.map(p => p.id) || []
    }
    else {
      code.value = ''
      libelle.value = ''
      selectedPermissions.value = []
    }
  }
}, { immediate: true })

const onReset = () => {
  emit('update:isDialogVisible', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
    code.value = ''
    libelle.value = ''
    selectedPermissions.value = []
    searchPerm.value = ''
  })
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      emit('submit', {
        code: code.value.toUpperCase(),
        libelle: libelle.value,
        permissions: selectedPermissions.value,
      })
      emit('update:isDialogVisible', false)
      nextTick(() => {
        refForm.value?.reset()
        refForm.value?.resetValidation()
        code.value = ''
        libelle.value = ''
        selectedPermissions.value = []
        searchPerm.value = ''
      })
    }
  })
}
</script>

<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 860"
    :model-value="props.isDialogVisible"
    scrollable
    @update:model-value="onReset"
  >
    <DialogCloseBtn @click="onReset" />

    <VCard>
      <!-- En-tête -->
      <VCardTitle class="d-flex align-center gap-2 pa-5 pb-3">
        <VIcon :icon="props.role ? 'tabler-shield-check' : 'tabler-shield-plus'" color="primary" />
        <span>{{ props.role ? 'Modifier le rôle' : 'Nouveau rôle' }}</span>
      </VCardTitle>
      <VDivider />

      <VCardText class="pa-5" style="max-height: 75vh; overflow-y: auto;">
        <VForm ref="refForm" v-model="isFormValid" @submit.prevent="onSubmit">
          <VRow>
            <!-- Code -->
            <VCol cols="12" md="4">
              <AppTextField
                v-model="code"
                :rules="[requiredValidator]"
                label="Code *"
                placeholder="Ex: SUPERVISEUR"
                hint="Sera converti en MAJUSCULES"
                persistent-hint
                :disabled="!!props.role"
                @input="code = code.toUpperCase()"
              />
            </VCol>

            <!-- Libellé -->
            <VCol cols="12" md="8">
              <AppTextField
                v-model="libelle"
                :rules="[requiredValidator]"
                label="Libellé *"
                placeholder="Ex: Superviseur Passation & Contrats"
              />
            </VCol>

            <!-- Séparateur permissions -->
            <VCol cols="12">
              <div class="d-flex align-center justify-space-between mb-2">
                <div class="d-flex align-center gap-2">
                  <VIcon icon="tabler-shield-lock" size="18" color="primary" />
                  <span class="text-subtitle-2 font-weight-bold">Permissions</span>
                  <VChip size="x-small" color="primary" variant="tonal">
                    {{ selectedPermissions.length }} / {{ allPermissions.length }}
                  </VChip>
                </div>
                <div class="d-flex align-center gap-3">
                  <!-- Recherche permissions -->
                  <VTextField
                    v-model="searchPerm"
                    placeholder="Filtrer les permissions..."
                    prepend-inner-icon="tabler-search"
                    density="compact"
                    hide-details
                    clearable
                    style="min-width: 220px;"
                  />
                  <!-- Tout sélectionner -->
                  <VBtn
                    size="small"
                    variant="tonal"
                    :color="isAllSelected ? 'error' : 'primary'"
                    @click="toggleAll"
                  >
                    {{ isAllSelected ? 'Tout désélectionner' : 'Tout sélectionner' }}
                  </VBtn>
                </div>
              </div>

              <VDivider class="mb-3" />

              <!-- Groupes de permissions -->
              <div v-if="permissionsGrouped.length > 0">
                <VExpansionPanels multiple variant="accordion" class="permissions-panels">
                  <VExpansionPanel
                    v-for="[groupe, perms] in permissionsGrouped"
                    :key="groupe"
                    elevation="0"
                  >
                    <VExpansionPanelTitle class="py-2 px-3">
                      <div class="d-flex align-center gap-3 w-100">
                        <!-- Checkbox groupe -->
                        <VCheckbox
                          :model-value="isGroupFullySelected(perms)"
                          :indeterminate="isGroupPartiallySelected(perms)"
                          density="compact"
                          hide-details
                          :color="groupColor(groupe)"
                          @click.stop="toggleGroup(perms)"
                        />
                        <VChip :color="groupColor(groupe)" size="small" variant="tonal" label>
                          {{ groupe }}
                        </VChip>
                        <span class="text-caption text-medium-emphasis">
                          {{ perms.filter(p => selectedPermissions.includes(p.id)).length }}
                          / {{ perms.length }} sélectionnée(s)
                        </span>
                      </div>
                    </VExpansionPanelTitle>

                    <VExpansionPanelText class="px-2 pb-2">
                      <VRow dense>
                        <VCol
                          v-for="perm in perms"
                          :key="perm.id"
                          cols="12"
                          sm="6"
                          md="4"
                        >
                          <VCheckbox
                            v-model="selectedPermissions"
                            :value="perm.id"
                            :label="perm.libelle"
                            density="compact"
                            hide-details
                            :color="groupColor(groupe)"
                          >
                            <template #label>
                              <div class="d-flex flex-column">
                                <span class="text-body-2">{{ perm.libelle }}</span>
                                <span class="text-caption text-medium-emphasis font-weight-medium">
                                  {{ perm.code }}
                                </span>
                              </div>
                            </template>
                          </VCheckbox>
                        </VCol>
                      </VRow>
                    </VExpansionPanelText>
                  </VExpansionPanel>
                </VExpansionPanels>
              </div>

              <div v-else class="text-center py-6 text-medium-emphasis">
                <VIcon icon="tabler-search-off" size="32" class="mb-1 opacity-40" />
                <p class="text-caption">
                  Aucune permission ne correspond à la recherche
                </p>
              </div>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <VDivider />

      <!-- Actions -->
      <VCardActions class="pa-4 justify-end gap-3">
        <VBtn variant="tonal" color="secondary" @click="onReset">
          Annuler
        </VBtn>
        <VBtn color="primary" prepend-icon="tabler-device-floppy" @click="onSubmit">
          {{ props.role ? 'Enregistrer les modifications' : 'Créer le rôle' }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.permissions-panels :deep(.v-expansion-panel) {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 8px !important;
  margin-block-end: 6px;
}

.permissions-panels :deep(.v-expansion-panel-title) {
  min-block-size: 48px;
}

.permissions-panels :deep(.v-expansion-panel--active > .v-expansion-panel-title) {
  min-block-size: 48px;
}
</style>
