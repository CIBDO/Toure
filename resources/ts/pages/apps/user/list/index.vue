<script setup lang="ts">
import AddNewUserDrawer from '@/views/apps/user/list/AddNewUserDrawer.vue'
import EditUserDrawer from '@/views/apps/user/list/EditUserDrawer.vue'

// Interface pour les données utilisateur IAM
interface UserIAM {
  id: number
  nom: string
  prenom: string
  fullName: string
  email: string
  telephone: string | null
  fonction: string | null
  unite_service: string | null
  region: string | null
  avatar: string | null
  statut: 'ACTIF' | 'SUSPENDU' | 'DESACTIVE' | 'EN_ATTENTE_ACTIVATION'
  type_compte: 'CANAM' | 'CONTRAT' | 'SYSTEME'
  last_login_at: string | null
  roles: Array<{ id: number; code: string; libelle: string }>
}

// 👉 Store
const searchQuery = ref('')
const selectedRole = ref()
const selectedTypeCompte = ref()
const selectedStatus = ref()

// Data table options
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const selectedRows = ref([])

// Update data table options
const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}

// Headers adaptés pour IAM
const headers = [
  { title: 'User', key: 'user' },
  { title: 'Rôles', key: 'roles' },
  { title: 'Type Compte', key: 'type_compte' },
  { title: 'Statut', key: 'statut' },
  { title: 'Actions', key: 'actions', sortable: false },
]

// 👉 Charger les rôles depuis l'API pour le filtre
const { data: rolesData } = await useApi<any>('/roles?itemsPerPage=-1')
const availableRoles = computed(() => {
  if (!rolesData.value?.roles) return []
  return rolesData.value.roles.map((role: any) => ({
    title: role.libelle,
    value: role.code,
  }))
})

// 👉 Fetching users depuis l'API Laravel IAM
const { data: usersData, execute: fetchUsers } = await useApi<any>(createUrl('/users', {
  query: {
    q: searchQuery,
    statut: selectedStatus,
    type_compte: selectedTypeCompte,
    role: selectedRole,
    itemsPerPage,
    page,
    sortBy,
    orderBy,
  },
}))

const users = computed((): UserIAM[] => usersData.value?.users || [])
const totalUsers = computed(() => usersData.value?.totalUsers || 0)

// 👉 Filtres adaptés pour IAM
const statusOptions = [
  { title: 'Actif', value: 'ACTIF' },
  { title: 'Suspendu', value: 'SUSPENDU' },
  { title: 'Désactivé', value: 'DESACTIVE' },
  { title: 'En attente d\'activation', value: 'EN_ATTENTE_ACTIVATION' },
]

const typeCompteOptions = [
  { title: 'CANAM', value: 'CANAM' },
  { title: 'Contrat CANAM', value: 'CONTRAT' },
  { title: 'Système', value: 'SYSTEME' },
]

// Fonction pour obtenir la couleur d'un rôle
const resolveRoleVariant = (roleCode: string) => {
  const code = roleCode.toUpperCase()
  if (code.includes('ADMIN'))
    return { color: 'primary', icon: 'tabler-crown' }
  if (code.includes('SUP'))
    return { color: 'info', icon: 'tabler-user-check' }
  if (code.includes('AGENT'))
    return { color: 'success', icon: 'tabler-user' }
  if (code.includes('CONTRAT'))
    return { color: 'warning', icon: 'tabler-file-certificate' }
  if (code.includes('AUDIT'))
    return { color: 'secondary', icon: 'tabler-eye' }
  
  return { color: 'primary', icon: 'tabler-user' }
}

// Fonction pour obtenir la couleur du statut
const resolveStatusVariant = (statut: string) => {
  if (statut === 'ACTIF') return 'success'
  if (statut === 'SUSPENDU') return 'warning'
  if (statut === 'DESACTIVE') return 'secondary'
  if (statut === 'EN_ATTENTE_ACTIVATION') return 'info'
  return 'primary'
}

// Fonction pour obtenir la couleur du type de compte
const resolveTypeCompteVariant = (type: string) => {
  if (type === 'CANAM') return 'primary'
  if (type === 'CONTRAT') return 'warning'
  if (type === 'SYSTEME') return 'info'
  return 'secondary'
}

const isAddNewUserDrawerVisible = ref(false)
const isEditUserDrawerVisible = ref(false)
const selectedUser = ref<UserIAM | null>(null)

// 👉 Add new user
const addNewUser = async (userData: any) => {
  try {
    await $api('/users', {
      method: 'POST',
      body: userData,
    })

    // Refetch User
    fetchUsers()
  } catch (error: any) {
    console.error('Erreurs de validation:', error.data?.errors)
    throw error
  }
}

// 👉 Edit user
const editUser = async (userData: any) => {
  if (!selectedUser.value) return

  try {
    await $api(`/users/${selectedUser.value.id}`, {
      method: 'PUT',
      body: userData,
    })

    // Refetch User
    fetchUsers()
    selectedUser.value = null
  } catch (error: any) {
    console.error('Erreurs de validation:', error.data?.errors)
    throw error
  }
}

// 👉 Open edit drawer
const openEditDrawer = (user: UserIAM) => {
  selectedUser.value = user
  isEditUserDrawerVisible.value = true
}

// 👉 Delete user with confirmation
const deleteUser = async (id: number) => {
  // Trouver l'utilisateur pour afficher son nom
  const user = users.value.find(u => u.id === id)
  const userName = user ? `${user.prenom} ${user.nom}` : `l'utilisateur #${id}`

  // Confirmation
  const confirmed = confirm(`Êtes-vous sûr de vouloir supprimer ${userName} ? Cette action est irréversible.`)

  if (!confirmed) return

  try {
    await $api(`/users/${id}`, {
      method: 'DELETE',
    })

    // Delete from selectedRows
    const index = selectedRows.value.findIndex(row => row === id)
    if (index !== -1)
      selectedRows.value.splice(index, 1)

    // Refetch User
    fetchUsers()
  } catch (error: any) {
    console.error('Erreur lors de la suppression:', error)
    alert('Une erreur est survenue lors de la suppression de l\'utilisateur.')
  }
}

// 👉 Activate user
const activateUser = async (id: number) => {
  const user = users.value.find(u => u.id === id)
  const userName = user ? `${user.prenom} ${user.nom}` : `l'utilisateur #${id}`

  const confirmed = confirm(`Êtes-vous sûr de vouloir activer le compte de ${userName} ?`)

  if (!confirmed) return

  try {
    await $api(`/users/${id}/activate`, {
      method: 'POST',
    })

    // Refetch users
    fetchUsers()
  } catch (error: any) {
    console.error('Erreur lors de l\'activation:', error)
    alert('Une erreur est survenue lors de l\'activation du compte.')
  }
}

// 👉 Deactivate user
const deactivateUser = async (id: number) => {
  const user = users.value.find(u => u.id === id)
  const userName = user ? `${user.prenom} ${user.nom}` : `l'utilisateur #${id}`

  const confirmed = confirm(`Êtes-vous sûr de vouloir désactiver le compte de ${userName} ? L'utilisateur ne pourra plus se connecter.`)

  if (!confirmed) return

  try {
    await $api(`/users/${id}/deactivate`, {
      method: 'POST',
    })

    // Refetch users
    fetchUsers()
  } catch (error: any) {
    console.error('Erreur lors de la désactivation:', error)
    alert('Une erreur est survenue lors de la désactivation du compte.')
  }
}

// 👉 Suspend user
const suspendUser = async (id: number) => {
  const user = users.value.find(u => u.id === id)
  const userName = user ? `${user.prenom} ${user.nom}` : `l'utilisateur #${id}`

  const confirmed = confirm(`Êtes-vous sûr de vouloir suspendre le compte de ${userName} ? L'utilisateur ne pourra plus se connecter.`)

  if (!confirmed) return

  try {
    await $api(`/users/${id}/suspend`, {
      method: 'POST',
    })

    // Refetch users
    fetchUsers()
  } catch (error: any) {
    console.error('Erreur lors de la suspension:', error)
    alert('Une erreur est survenue lors de la suspension du compte.')
  }
}

// Widgets adaptés - Utiliser computed pour réactivité
const activeUsersCount = computed(() => users.value.filter(u => u.statut === 'ACTIF').length)
const suspendedUsersCount = computed(() => users.value.filter(u => u.statut === 'SUSPENDU').length)
const dmUsersCount = computed(() => users.value.filter(u => u.type_compte === 'CANAM').length)

const widgetData = computed(() => [
  { title: 'Total Utilisateurs', value: totalUsers.value.toString(), change: 0, desc: 'Tous les utilisateurs', icon: 'tabler-users', iconColor: 'primary' },
  { title: 'Utilisateurs Actifs', value: activeUsersCount.value.toString(), change: 0, desc: 'Statut actif', icon: 'tabler-user-check', iconColor: 'success' },
  { title: 'Utilisateurs Suspendus', value: suspendedUsersCount.value.toString(), change: 0, desc: 'Statut suspendu', icon: 'tabler-user-x', iconColor: 'warning' },
  { title: 'Utilisateurs CANAM', value: dmUsersCount.value.toString(), change: 0, desc: 'Type compte CANAM', icon: 'tabler-building', iconColor: 'info' },
])
</script>

<template>
  <section>
    <!-- 👉 Widgets -->
    <div class="d-flex mb-6">
      <VRow>
        <template
          v-for="(data, id) in widgetData"
          :key="id"
        >
          <VCol
            cols="12"
            md="3"
            sm="6"
          >
            <VCard>
              <VCardText>
                <div class="d-flex justify-space-between">
                  <div class="d-flex flex-column gap-y-1">
                    <div class="text-body-1 text-high-emphasis">
                      {{ data.title }}
                    </div>
                    <div class="d-flex gap-x-2 align-center">
                      <h4 class="text-h4">
                        {{ data.value }}
                      </h4>
                    </div>
                    <div class="text-sm">
                      {{ data.desc }}
                    </div>
                  </div>
                  <VAvatar
                    :color="data.iconColor"
                    variant="tonal"
                    rounded
                    size="42"
                  >
                    <VIcon
                      :icon="data.icon"
                      size="26"
                    />
                  </VAvatar>
                </div>
              </VCardText>
            </VCard>
          </VCol>
        </template>
      </VRow>
    </div>

    <VCard class="mb-6">
      <VCardItem class="pb-4">
        <VCardTitle>Filtres</VCardTitle>
      </VCardItem>

      <VCardText>
        <VRow>
          <!-- 👉 Select Role -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppSelect
              v-model="selectedRole"
              placeholder="Sélectionner un rôle"
              :items="availableRoles"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>
          <!-- 👉 Select Type Compte -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppSelect
              v-model="selectedTypeCompte"
              placeholder="Sélectionner type compte"
              :items="typeCompteOptions"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>
          <!-- 👉 Select Status -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppSelect
              v-model="selectedStatus"
              placeholder="Sélectionner statut"
              :items="statusOptions"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <VCardText class="d-flex flex-wrap gap-4">
        <div class="me-3 d-flex gap-3">
          <AppSelect
            :model-value="itemsPerPage"
            :items="[
              { value: 10, title: '10' },
              { value: 25, title: '25' },
              { value: 50, title: '50' },
              { value: 100, title: '100' },
              { value: -1, title: 'Tous' },
            ]"
            style="inline-size: 6.25rem;"
            @update:model-value="itemsPerPage = parseInt($event, 10)"
          />
        </div>
        <VSpacer />

        <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
          <!-- 👉 Search  -->
          <div style="inline-size: 15.625rem;">
            <AppTextField
              v-model="searchQuery"
              placeholder="Rechercher un utilisateur"
            />
          </div>

          <!-- 👉 Export button -->
          <VBtn
            variant="tonal"
            color="secondary"
            prepend-icon="tabler-upload"
          >
            Exporter
          </VBtn>

          <!-- 👉 Add user button -->
          <VBtn
            prepend-icon="tabler-plus"
            @click="isAddNewUserDrawerVisible = true"
          >
            Ajouter un utilisateur
          </VBtn>
        </div>
      </VCardText>

      <VDivider />

      <!-- SECTION datatable -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:model-value="selectedRows"
        v-model:page="page"
        :items="users"
        item-value="id"
        :items-length="totalUsers"
        :headers="headers"
        class="text-no-wrap"
        show-select
        @update:options="updateOptions"
      >
        <!-- User -->
        <template #item.user="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar
              size="34"
              variant="tonal"
              :color="item.roles && item.roles.length > 0 ? resolveRoleVariant(item.roles[0].code).color : 'primary'"
            >
              <span>{{ avatarText(item.fullName) }}</span>
            </VAvatar>
            <div class="d-flex flex-column">
              <h6 class="text-base">
                <RouterLink
                  :to="{ name: 'apps-user-view-id', params: { id: item.id } }"
                  class="font-weight-medium text-link"
                >
                  {{ item.fullName }}
                </RouterLink>
              </h6>
              <div class="text-sm">
                {{ item.email }}
              </div>
              <div
                v-if="item.telephone"
                class="text-xs text-disabled"
              >
                {{ item.telephone }}
              </div>
            </div>
          </div>
        </template>

        <!-- 👉 Roles -->
        <template #item.roles="{ item }">
          <div class="d-flex flex-wrap gap-1">
            <VChip
              v-for="role in item.roles"
              :key="role.id"
              :color="resolveRoleVariant(role.code).color"
              size="small"
              variant="tonal"
            >
              {{ role.libelle }}
            </VChip>
            <span
              v-if="!item.roles || item.roles.length === 0"
              class="text-sm text-disabled"
            >
              Aucun rôle
            </span>
          </div>
        </template>

        <!-- Type Compte -->
        <template #item.type_compte="{ item }">
          <VChip
            :color="resolveTypeCompteVariant(item.type_compte)"
            size="small"
            variant="tonal"
          >
            {{ item.type_compte }}
          </VChip>
        </template>

        <!-- Status -->
        <template #item.statut="{ item }">
          <VChip
            :color="resolveStatusVariant(item.statut)"
            size="small"
            label
          >
            {{ item.statut }}
          </VChip>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <!-- <IconBtn @click="openEditDrawer(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>

          <IconBtn :to="{ name: 'apps-user-view-id', params: { id: item.id } }">
            <VIcon icon="tabler-eye" />
          </IconBtn>

          <IconBtn @click="deleteUser(item.id)">
            <VIcon icon="tabler-trash" />
          </IconBtn> -->

          <VBtn
            icon
            variant="text"
            color="medium-emphasis"
          >
            <VIcon icon="tabler-dots-vertical" />
            <VMenu activator="parent">
              <VList>
                <VListItem :to="{ name: 'apps-user-view-id', params: { id: item.id } }">
                  <template #prepend>
                    <VIcon icon="tabler-eye" />
                  </template>

                  <VListItemTitle>Voir</VListItemTitle>
                </VListItem>

                <VListItem @click="openEditDrawer(item)">
                  <template #prepend>
                    <VIcon icon="tabler-pencil" />
                  </template>
                  <VListItemTitle>Modifier</VListItemTitle>
                </VListItem>

                <VDivider />

                <!-- Actions sur le statut du compte -->
                <VListItem
                  v-if="item.statut !== 'ACTIF' && item.statut !== 'EN_ATTENTE_ACTIVATION'"
                  @click="activateUser(item.id)"
                >
                  <template #prepend>
                    <VIcon icon="tabler-user-check" />
                  </template>
                  <VListItemTitle>Activer</VListItemTitle>
                </VListItem>

                <VListItem
                  v-if="item.statut === 'ACTIF' || item.statut === 'EN_ATTENTE_ACTIVATION'"
                  @click="deactivateUser(item.id)"
                >
                  <template #prepend>
                    <VIcon icon="tabler-user-x" />
                  </template>
                  <VListItemTitle>Désactiver</VListItemTitle>
                </VListItem>

                <VListItem
                  v-if="item.statut === 'ACTIF'"
                  @click="suspendUser(item.id)"
                >
                  <template #prepend>
                    <VIcon icon="tabler-user-off" />
                  </template>
                  <VListItemTitle>Suspendre</VListItemTitle>
                </VListItem>

                <VDivider />

                <VListItem @click="deleteUser(item.id)">
                  <template #prepend>
                    <VIcon icon="tabler-trash" />
                  </template>
                  <VListItemTitle>Supprimer</VListItemTitle>
                </VListItem>
              </VList>
            </VMenu>
          </VBtn>
        </template>

        <!-- pagination -->
        <template #bottom>
          <TablePagination
            v-model:page="page"
            :items-per-page="itemsPerPage"
            :total-items="totalUsers"
          />
        </template>
      </VDataTableServer>
      <!-- SECTION -->
    </VCard>
    <!-- 👉 Add New User -->
    <AddNewUserDrawer
      v-model:isDrawerOpen="isAddNewUserDrawerVisible"
      @user-data="addNewUser"
    />

    <!-- 👉 Edit User -->
    <EditUserDrawer
      v-model:isDrawerOpen="isEditUserDrawerVisible"
      :user="selectedUser"
      @user-data="editUser"
    />
  </section>
</template>
