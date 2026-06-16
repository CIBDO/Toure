<script setup lang="ts">
import AddEditRoleDialog from '@/components/dialogs/AddEditRoleDialog.vue'

definePage({ meta: { title: 'Rôles & Permissions' } })

interface RoleIAM {
  id: number
  code: string
  libelle: string
  created_at: string
  permissions?: Array<{ id: number; code: string; libelle: string }>
}

const groupColors: Record<string, string> = {
  AVIS: 'primary', CONTRATS: 'success', DEPOUILLEMENTS: 'info', PVS: 'teal',
  FOURNISSEURS: 'orange', REFERENTIELS: 'purple', USERS: 'blue', ROLES: 'indigo',
  PERMISSIONS: 'deep-purple', AUDIT: 'grey', DASHBOARD: 'cyan', SYSTEM: 'red',
  GED: 'brown', FINANCES: 'green', RAPPORTS: 'pink',
}
const groupColor = (code: string) => groupColors[code.split('_')[0]] ?? 'secondary'

const headers = [
  { title: 'Code', key: 'code', width: '160px' },
  { title: 'Libellé', key: 'libelle' },
  { title: 'Permissions', key: 'permissions', sortable: false, width: '260px' },
  { title: 'Créé le', key: 'created_at', width: '130px' },
  { title: 'Actions', key: 'actions', sortable: false, width: '90px', align: 'end' },
]

const searchQuery = ref('')
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const snackbar = ref({ show: false, text: '', color: 'success' })
const deleteDialog = ref(false)
const roleToDelete = ref<RoleIAM | null>(null)

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}

const isRoleDialogVisible = ref(false)
const isAddRoleDialogVisible = ref(false)
const selectedRole = ref<RoleIAM | null>(null)

const { data: rolesData, execute: fetchRoles } = await useApi<any>(createUrl('/roles', {
  query: { q: searchQuery, itemsPerPage, page, sortBy, orderBy },
}))

const roles = computed((): RoleIAM[] => rolesData.value?.roles || [])
const totalRoles = computed(() => rolesData.value?.totalRoles || 0)

const addNewRole = async (roleData: any) => {
  try {
    await $api('/roles', { method: 'POST', body: roleData })
    fetchRoles()
    snackbar.value = { show: true, text: 'Rôle créé avec succès', color: 'success' }
  }
  catch (error: any) {
    snackbar.value = {
      show: true,
      text: error.data?.errors?.code?.[0] ?? error.data?.message ?? 'Erreur lors de la création',
      color: 'error',
    }
    throw error
  }
}

const editRole = async (roleData: any) => {
  if (!selectedRole.value)
    return
  try {
    await $api(`/roles/${selectedRole.value.id}`, { method: 'PUT', body: roleData })
    fetchRoles()
    selectedRole.value = null
    snackbar.value = { show: true, text: 'Rôle modifié avec succès', color: 'success' }
  }
  catch (error: any) {
    snackbar.value = {
      show: true,
      text: error.data?.message ?? 'Erreur lors de la modification',
      color: 'error',
    }
    throw error
  }
}

const openDeleteDialog = (role: RoleIAM) => {
  roleToDelete.value = role
  deleteDialog.value = true
}

const confirmDelete = async () => {
  if (!roleToDelete.value)
    return
  try {
    await $api(`/roles/${roleToDelete.value.id}`, { method: 'DELETE' })
    fetchRoles()
    deleteDialog.value = false
    snackbar.value = { show: true, text: 'Rôle supprimé avec succès', color: 'success' }
  }
  catch {
    snackbar.value = { show: true, text: 'Impossible de supprimer ce rôle', color: 'error' }
  }
}

const openEditDialog = (role: RoleIAM) => {
  selectedRole.value = role
  isRoleDialogVisible.value = true
}

const openAddDialog = () => {
  selectedRole.value = null
  isAddRoleDialogVisible.value = true
}

const handleRoleSubmit = async (roleData: { code: string; libelle: string; permissions?: number[] }) => {
  if (selectedRole.value)
    await editRole({ ...roleData, id: selectedRole.value.id })
  else
    await addNewRole(roleData)
}

// Résumé des permissions par groupe pour l'affichage compact
const permissionsSummary = (perms: Array<{ code: string }>) => {
  const groups: Record<string, number> = {}
  perms.forEach(p => {
    const g = p.code.split('_')[0]
    groups[g] = (groups[g] ?? 0) + 1
  })
  return Object.entries(groups).sort(([a], [b]) => a.localeCompare(b))
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <div class="mb-4">
        <h4 class="text-h4 mb-1">
          Rôles & Permissions
        </h4>
        <p class="text-body-1 mb-0 text-medium-emphasis">
          Gérez les rôles du système et leurs permissions associées. Chaque rôle définit les accès accordés aux utilisateurs.
        </p>
      </div>
    </VCol>

    <VCol cols="12">
      <VCard>
        <VCardText class="d-flex align-center justify-space-between flex-wrap gap-4 pa-4">
          <div class="d-flex gap-2 align-center">
            <span class="text-body-2 text-medium-emphasis">Afficher</span>
            <AppSelect
              :model-value="itemsPerPage"
              :items="[
                { value: 5, title: '5' },
                { value: 10, title: '10' },
                { value: 25, title: '25' },
                { value: 50, title: '50' },
                { value: -1, title: 'Tous' },
              ]"
              style="inline-size: 5.5rem;"
              @update:model-value="itemsPerPage = parseInt($event, 10)"
            />
          </div>

          <div class="d-flex align-center gap-3 flex-wrap">
            <AppTextField
              v-model="searchQuery"
              placeholder="Rechercher un rôle..."
              prepend-inner-icon="tabler-search"
              style="inline-size: 15rem;"
              clearable
            />
            <VBtn prepend-icon="tabler-plus" color="primary" @click="openAddDialog">
              Nouveau rôle
            </VBtn>
          </div>
        </VCardText>

        <VDivider />

        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          v-model:page="page"
          :items-length="totalRoles"
          :items-per-page-options="[
            { value: 5, title: '5' },
            { value: 10, title: '10' },
            { value: 25, title: '25' },
            { value: 50, title: '50' },
            { value: -1, title: '$vuetify.dataFooter.itemsPerPageAll' },
          ]"
          :headers="headers"
          :items="roles"
          item-value="id"
          @update:options="updateOptions"
        >
          <!-- Code -->
          <template #item.code="{ item }">
            <VChip size="small" color="primary" variant="tonal" label class="font-weight-bold">
              {{ item.code }}
            </VChip>
          </template>

          <!-- Libellé -->
          <template #item.libelle="{ item }">
            <span class="text-body-2 font-weight-medium">{{ item.libelle }}</span>
          </template>

          <!-- Permissions — affichage compact par groupe -->
          <template #item.permissions="{ item }">
            <div v-if="item.permissions && item.permissions.length > 0" class="d-flex align-center gap-1 flex-wrap py-1">
              <VTooltip
                v-for="[groupe, count] in permissionsSummary(item.permissions)"
                :key="groupe"
                location="top"
              >
                <template #activator="{ props: tooltipProps }">
                  <VChip
                    v-bind="tooltipProps"
                    :color="groupColor(groupe + '_X')"
                    size="x-small"
                    variant="tonal"
                    label
                    class="cursor-pointer"
                  >
                    {{ groupe }}
                    <span class="ms-1 font-weight-bold">{{ count }}</span>
                  </VChip>
                </template>
                <div>
                  <div class="font-weight-bold mb-1">
                    {{ groupe }} ({{ count }} permission{{ count > 1 ? 's' : '' }})
                  </div>
                  <div
                    v-for="p in item.permissions.filter((p: any) => p.code.startsWith(groupe + '_'))"
                    :key="p.id"
                    class="text-caption"
                  >
                    • {{ p.libelle }}
                  </div>
                </div>
              </VTooltip>
              <VChip size="x-small" variant="outlined" color="secondary" label>
                {{ item.permissions.length }} total
              </VChip>
            </div>
            <span v-else class="text-caption text-disabled">Aucune permission</span>
          </template>

          <!-- Date de création -->
          <template #item.created_at="{ item }">
            <span class="text-caption text-medium-emphasis">
              {{ formatDate(item.created_at, { year: 'numeric', month: 'short', day: 'numeric' }) }}
            </span>
          </template>

          <!-- Actions -->
          <template #item.actions="{ item }">
            <div class="d-flex justify-end">
              <VTooltip location="top" text="Modifier">
                <template #activator="{ props: tp }">
                  <IconBtn v-bind="tp" color="primary" @click="openEditDialog(item)">
                    <VIcon size="18" icon="tabler-edit" />
                  </IconBtn>
                </template>
              </VTooltip>
              <VTooltip location="top" text="Supprimer">
                <template #activator="{ props: tp }">
                  <IconBtn v-bind="tp" color="error" @click="openDeleteDialog(item)">
                    <VIcon size="18" icon="tabler-trash" />
                  </IconBtn>
                </template>
              </VTooltip>
            </div>
          </template>

          <template #bottom>
            <TablePagination
              v-model:page="page"
              :items-per-page="itemsPerPage"
              :total-items="totalRoles"
            />
          </template>
        </VDataTableServer>
      </VCard>
    </VCol>
  </VRow>

  <!-- Dialog Ajout -->
  <AddEditRoleDialog
    v-model:isDialogVisible="isAddRoleDialogVisible"
    :role="null"
    @submit="handleRoleSubmit"
  />

  <!-- Dialog Édition -->
  <AddEditRoleDialog
    v-model:isDialogVisible="isRoleDialogVisible"
    :role="selectedRole"
    @submit="handleRoleSubmit"
  />

  <!-- Dialog Suppression -->
  <VDialog v-model="deleteDialog" max-width="420">
    <VCard>
      <VCardTitle class="d-flex align-center gap-2 pa-4">
        <VIcon icon="tabler-alert-triangle" color="error" />
        Confirmer la suppression
      </VCardTitle>
      <VCardText>
        Voulez-vous vraiment supprimer le rôle
        <VChip size="small" color="error" class="mx-1">
          {{ roleToDelete?.code }}
        </VChip> ?
        <br>
        <span class="text-caption text-medium-emphasis">
          Les utilisateurs qui possèdent ce rôle perdront les permissions associées.
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
