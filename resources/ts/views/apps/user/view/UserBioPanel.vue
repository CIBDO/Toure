<script setup lang="ts">
interface Props {
  userData: {
    id: number
    nom: string
    prenom: string
    fullName: string
    email: string
    telephone?: string
    fonction?: string
    unite_service?: string
    region?: string
    avatar?: string
    statut: string
    type_compte: string
    last_login_at?: string
    created_at: string
    roles?: Array<{
      id: number
      code: string
      libelle: string
    }>
  }
}

const props = defineProps<Props>()

const router = useRouter()

// 👉 Statut variant resolver
const resolveStatusVariant = (statut: string) => {
  if (statut === 'ACTIF')
    return { color: 'success', icon: 'tabler-circle-check' }
  if (statut === 'SUSPENDU')
    return { color: 'warning', icon: 'tabler-alert-circle' }
  if (statut === 'DESACTIVE')
    return { color: 'error', icon: 'tabler-x' }
  if (statut === 'EN_ATTENTE_ACTIVATION')
    return { color: 'info', icon: 'tabler-clock' }

  return { color: 'secondary', icon: 'tabler-help' }
}

// 👉 Type compte variant resolver
const resolveTypeCompteVariant = (type: string) => {
  if (type === 'CANAM')
    return { color: 'primary', icon: 'tabler-user' }
  if (type === 'CONTRAT')
    return { color: 'info', icon: 'tabler-file-certificate' }
  if (type === 'SYSTEME')
    return { color: 'secondary', icon: 'tabler-server' }

  return { color: 'primary', icon: 'tabler-user' }
}

const formatDate = (dateString?: string) => {
  if (!dateString) return 'Jamais'
  return new Date(dateString).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const handleEdit = () => {
  router.push({ name: 'apps-user-list' })
  // TODO: Ouvrir le dialogue d'édition
}

const handleSuspend = async () => {
  if (!confirm(`Êtes-vous sûr de vouloir suspendre le compte de ${props.userData.fullName} ?`))
    return

  try {
    await $api(`/users/${props.userData.id}/suspend`, {
      method: 'POST',
    })
    // Recharger les données
    window.location.reload()
  } catch (error) {
    console.error('Erreur lors de la suspension:', error)
    alert('Une erreur est survenue lors de la suspension du compte.')
  }
}

const handleActivate = async () => {
  if (!confirm(`Êtes-vous sûr de vouloir activer le compte de ${props.userData.fullName} ?`))
    return

  try {
    await $api(`/users/${props.userData.id}/activate`, {
      method: 'POST',
    })
    // Recharger les données
    window.location.reload()
  } catch (error) {
    console.error('Erreur lors de l\'activation:', error)
    alert('Une erreur est survenue lors de l\'activation du compte.')
  }
}
</script>

<template>
  <VRow>
    <!-- SECTION User Details -->
    <VCol cols="12">
      <VCard v-if="props.userData">
        <VCardText class="text-center pt-12">
          <!-- 👉 Avatar -->
          <VAvatar
            rounded
            :size="100"
            :color="!props.userData.avatar ? 'primary' : undefined"
            :variant="!props.userData.avatar ? 'tonal' : undefined"
          >
            <VImg
              v-if="props.userData.avatar"
              :src="props.userData.avatar"
            />
            <span
              v-else
              class="text-5xl font-weight-medium"
            >
              {{ avatarText(props.userData.fullName) }}
            </span>
          </VAvatar>

          <!-- 👉 User fullName -->
          <h5 class="text-h5 mt-4">
            {{ props.userData.fullName }}
          </h5>

          <!-- 👉 Statut chip -->
          <VChip
            label
            :color="resolveStatusVariant(props.userData.statut).color"
            size="small"
            class="mt-4"
          >
            <VIcon
              :icon="resolveStatusVariant(props.userData.statut).icon"
              size="16"
              class="me-1"
            />
            {{ props.userData.statut }}
          </VChip>
        </VCardText>

        <VCardText>
          <div class="d-flex justify-space-around gap-x-6 gap-y-2 flex-wrap mb-6">
            <!-- 👉 Rôles -->
            <div class="d-flex align-center me-8">
              <VAvatar
                :size="40"
                rounded
                color="primary"
                variant="tonal"
                class="me-4"
              >
                <VIcon
                  icon="tabler-shield"
                  size="24"
                />
              </VAvatar>
              <div>
                <h5 class="text-h5">
                  {{ props.userData.roles?.length || 0 }}
                </h5>
                <span class="text-sm">Rôle(s)</span>
              </div>
            </div>

            <!-- 👉 Type de compte -->
            <div class="d-flex align-center me-4">
              <VAvatar
                :size="38"
                rounded
                :color="resolveTypeCompteVariant(props.userData.type_compte).color"
                variant="tonal"
                class="me-4"
              >
                <VIcon
                  :icon="resolveTypeCompteVariant(props.userData.type_compte).icon"
                  size="24"
                />
              </VAvatar>
              <div>
                <h5 class="text-h5">
                  {{ props.userData.type_compte }}
                </h5>
                <span class="text-sm">Type de compte</span>
              </div>
            </div>
          </div>

          <!-- 👉 Details -->
          <h5 class="text-h5">
            Details
          </h5>

          <VDivider class="my-4" />

          <!-- 👉 User Details list -->
          <VList class="card-list mt-2">
            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  Nom complet:
                  <div class="d-inline-block text-body-1">
                    {{ props.userData.fullName }}
                  </div>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <span class="text-h6">
                  Email:
                </span>
                <span class="text-body-1">
                  {{ props.userData.email }}
                </span>
              </VListItemTitle>
            </VListItem>

            <VListItem v-if="props.userData.telephone">
              <VListItemTitle>
                <h6 class="text-h6">
                  Téléphone:
                  <div class="d-inline-block text-body-1">
                    {{ props.userData.telephone }}
                  </div>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  Statut:
                  <VChip
                    label
                    :color="resolveStatusVariant(props.userData.statut).color"
                    size="small"
                    class="ms-2"
                  >
                    {{ props.userData.statut }}
                  </VChip>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem v-if="props.userData.fonction">
              <VListItemTitle>
                <h6 class="text-h6">
                  Fonction:
                  <div class="d-inline-block text-body-1">
                    {{ props.userData.fonction }}
                  </div>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem v-if="props.userData.unite_service">
              <VListItemTitle>
                <h6 class="text-h6">
                  Unité/Service:
                  <div class="d-inline-block text-body-1">
                    {{ props.userData.unite_service }}
                  </div>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem v-if="props.userData.region">
              <VListItemTitle>
                <h6 class="text-h6">
                  Région:
                  <div class="d-inline-block text-body-1">
                    {{ props.userData.region }}
                  </div>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  Type de compte:
                  <VChip
                    label
                    :color="resolveTypeCompteVariant(props.userData.type_compte).color"
                    size="small"
                    class="ms-2"
                  >
                    {{ props.userData.type_compte }}
                  </VChip>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  Dernière connexion:
                  <div class="d-inline-block text-body-1">
                    {{ formatDate(props.userData.last_login_at) }}
                  </div>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  Compte créé le:
                  <div class="d-inline-block text-body-1">
                    {{ formatDate(props.userData.created_at) }}
                  </div>
                </h6>
              </VListItemTitle>
            </VListItem>
          </VList>
        </VCardText>

        <!-- 👉 Edit and Suspend/Activate button -->
        <VCardText class="d-flex justify-center gap-x-4">
          <VBtn
            variant="elevated"
            @click="handleEdit"
          >
            Modifier
          </VBtn>

          <VBtn
            v-if="props.userData.statut === 'ACTIF'"
            variant="tonal"
            color="warning"
            @click="handleSuspend"
          >
            Suspendre
          </VBtn>

          <VBtn
            v-else
            variant="tonal"
            color="success"
            @click="handleActivate"
          >
            Activer
          </VBtn>
        </VCardText>
      </VCard>
    </VCol>
    <!-- !SECTION -->

    <!-- SECTION Rôles -->
    <VCol cols="12">
      <VCard>
        <VCardText>
          <h5 class="text-h5 mb-4">
            Rôles assignés
          </h5>
          <div
            v-if="props.userData.roles && props.userData.roles.length > 0"
            class="d-flex flex-wrap gap-2"
          >
            <VChip
              v-for="role in props.userData.roles"
              :key="role.id"
              label
              color="primary"
              variant="tonal"
            >
              <VIcon
                icon="tabler-shield"
                size="16"
                class="me-1"
              />
              {{ role.libelle }} ({{ role.code }})
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
    <!-- !SECTION -->
  </VRow>
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 0.5rem;
}

.text-capitalize {
  text-transform: capitalize !important;
}
</style>
