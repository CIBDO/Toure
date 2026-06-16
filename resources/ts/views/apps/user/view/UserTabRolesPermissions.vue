<script setup lang="ts">
interface Props {
  userData: {
    id: number
    roles?: Array<{
      id: number
      code: string
      libelle: string
      permissions?: Array<{
        id: number
        code: string
        libelle: string
      }>
    }>
    permissions?: Array<{
      id: number
      code: string
      libelle: string
    }>
  }
}

const props = defineProps<Props>()

const isLoading = ref(false)

const roles = computed(() => props.userData.roles || [])
const allPermissions = computed(() => props.userData.permissions || [])

// Table headers pour les permissions
const permissionTableHeaders = [
  { title: 'CODE', key: 'code' },
  { title: 'LIBELLÉ', key: 'libelle' },
  { title: 'SOURCE', key: 'source' },
]

// Créer une liste de toutes les permissions avec leur source (rôle)
const permissionsWithSource = computed(() => {
  const permissionsMap = new Map()
  
  // Parcourir tous les rôles et leurs permissions
  roles.value.forEach((role: any) => {
    if (role.permissions && Array.isArray(role.permissions)) {
      role.permissions.forEach((permission: any) => {
        const key = permission.id || permission.code
        if (!permissionsMap.has(key)) {
          permissionsMap.set(key, {
            ...permission,
            source: role.libelle,
          })
        }
      })
    }
  })
  
  // Ajouter les permissions directes (si elles existent)
  if (allPermissions.value && allPermissions.value.length > 0) {
    allPermissions.value.forEach((permission: any) => {
      const key = permission.id || permission.code
      if (!permissionsMap.has(key)) {
        permissionsMap.set(key, {
          ...permission,
          source: 'Direct',
        })
      }
    })
  }
  
  return Array.from(permissionsMap.values())
})
</script>

<template>
  <VRow>
    <!-- 👉 Rôles assignés -->
    <VCol cols="12">
      <VCard title="Rôles assignés">
        <VCardText>
          <div
            v-if="roles.length > 0"
            class="d-flex flex-wrap gap-2"
          >
            <VChip
              v-for="role in roles"
              :key="role.id"
              label
              color="primary"
              variant="tonal"
              size="large"
            >
              <VIcon
                icon="tabler-shield"
                size="18"
                class="me-2"
              />
              <div>
                <div class="font-weight-medium">
                  {{ role.libelle }}
                </div>
                <div class="text-caption">
                  {{ role.code }}
                </div>
              </div>
            </VChip>
          </div>
          <VAlert
            v-else
            variant="tonal"
            color="info"
          >
            Aucun rôle assigné à cet utilisateur
          </VAlert>
        </VCardText>
      </VCard>
    </VCol>

    <!-- 👉 Permissions -->
    <VCol cols="12">
      <VCard title="Permissions">
        <VCardText>
          <div
            v-if="permissionsWithSource.length > 0"
            class="mb-4"
          >
            <div class="text-body-1 mb-2">
              <strong>Total :</strong> {{ permissionsWithSource.length }} permission(s)
            </div>
          </div>

          <VDataTable
            :headers="permissionTableHeaders"
            :items="permissionsWithSource"
            hide-default-footer
            class="text-no-wrap"
          >
            <template #item.code="{ item }">
              <VChip
                label
                color="secondary"
                variant="tonal"
                size="small"
              >
                {{ item.code }}
              </VChip>
            </template>

            <template #item.libelle="{ item }">
              <span class="text-body-1">
                {{ item.libelle }}
              </span>
            </template>

            <template #item.source="{ item }">
              <VChip
                label
                color="info"
                variant="tonal"
                size="small"
              >
                {{ item.source }}
              </VChip>
            </template>

            <template #bottom>
              <div
                v-if="permissionsWithSource.length === 0"
                class="text-center py-4"
              >
                <VAlert
                  variant="tonal"
                  color="info"
                >
                  Aucune permission assignée. Les permissions sont héritées des rôles assignés.
                </VAlert>
              </div>
            </template>
          </VDataTable>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>
