<script setup lang="ts">
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

const router = useRouter()
const ability = useAbility()

// TODO: Get type from backend
const userData = useCookie<any>('userData')

// Fonction pour obtenir le nom complet de l'utilisateur (IAM ou format classique)
const getFullName = (data: any) => {
  if (!data) return 'Utilisateur'
  // Format IAM
  if (data.prenom && data.nom) {
    return `${data.prenom} ${data.nom}`
  }
  // Format classique
  if (data.fullName) return data.fullName
  if (data.full_name) return data.full_name
  if (data.username) return data.username
  return 'Utilisateur'
}

// Fonction pour obtenir le rôle de l'utilisateur
const getUserRole = (data: any) => {
  if (!data) return 'User'
  // Format IAM - prendre le premier rôle ou afficher le type de compte
  if (data.roles && Array.isArray(data.roles) && data.roles.length > 0) {
    return data.roles[0].libelle || data.roles[0].code || 'User'
  }
  if (data.role) return data.role
  if (data.type_compte) return data.type_compte
  return 'User'
}

// Obtenir l'ID utilisateur pour la route du profil
const getUserId = computed(() => {
  return userData.value?.id || null
})

const logout = async () => {
  // Remove "accessToken" from cookie
  useCookie('accessToken').value = null

  // Remove "userData" from cookie
  userData.value = null

  // Redirect to login page
  await router.push('/login')

  // ℹ️ We had to remove abilities in then block because if we don't nav menu items mutation is visible while redirecting user to login page
  // Remove "userAbilities" from cookie
  useCookie('userAbilityRules').value = null

  // Reset ability to initial ability
  ability.update([])
}

const userProfileList = computed(() => {
  const userId = getUserId.value
  const list: Array<{ type: string; icon?: string; title?: string; to?: any; badgeProps?: any }> = []
  
  // Ajouter Profile seulement si on a un ID utilisateur
  if (userId) {
    list.push({ 
      type: 'navItem', 
      icon: 'tabler-user', 
      title: 'Profil', 
      to: { name: 'apps-user-view-id', params: { id: userId } } 
    })
  }
  
  // Ajouter Paramètres
 /*  list.push({ 
    type: 'navItem', 
    icon: 'tabler-settings', 
    title: 'Paramètres', 
    to: { name: 'pages-account-settings-tab', params: { tab: 'account' } } 
  }) */
  
  // Ajouter Plan de facturation
  /* list.push({ 
    type: 'navItem', 
    icon: 'tabler-file-dollar', 
    title: 'Plan de facturation', 
    to: { name: 'pages-account-settings-tab', params: { tab: 'billing-plans' } }, 
    badgeProps: { color: 'error', content: '4' } 
  }) */
  
  // Ajouter un divider
  list.push({ type: 'divider' })
  
  // Ajouter Tarification
 /*  list.push({ 
    type: 'navItem', 
    icon: 'tabler-currency-dollar', 
    title: 'Tarification', 
    to: { name: 'pages-pricing' } 
  }) */
  
  // Ajouter FAQ
 /*  list.push({ 
    type: 'navItem', 
    icon: 'tabler-question-mark', 
    title: 'FAQ', 
    to: { name: 'pages-faq' } 
  }) */
  
  return list
})
</script>

<template>
  <VBadge
    v-if="userData"
    dot
    bordered
    location="bottom right"
    offset-x="1"
    offset-y="2"
    color="success"
  >
    <VAvatar
      size="38"
      class="cursor-pointer"
      :color="!(userData && userData.avatar) ? 'primary' : undefined"
      :variant="!(userData && userData.avatar) ? 'tonal' : undefined"
    >
      <VImg
        v-if="userData && userData.avatar"
        :src="userData.avatar"
      />
      <VIcon
        v-else
        icon="tabler-user"
      />

      <!-- SECTION Menu -->
      <VMenu
        activator="parent"
        width="240"
        location="bottom end"
        offset="12px"
      >
        <VList>
          <VListItem>
            <div class="d-flex gap-2 align-center">
              <VListItemAction>
                <VBadge
                  dot
                  location="bottom right"
                  offset-x="3"
                  offset-y="3"
                  color="success"
                  bordered
                >
                  <VAvatar
                    :color="!(userData && userData.avatar) ? 'primary' : undefined"
                    :variant="!(userData && userData.avatar) ? 'tonal' : undefined"
                  >
                    <VImg
                      v-if="userData && userData.avatar"
                      :src="userData.avatar"
                    />
                    <VIcon
                      v-else
                      icon="tabler-user"
                    />
                  </VAvatar>
                </VBadge>
              </VListItemAction>

              <div>
                <h6 class="text-h6 font-weight-medium">
                  {{ getFullName(userData) }}
                </h6>
                <VListItemSubtitle class="text-capitalize text-disabled">
                  {{ getUserRole(userData) }}
                </VListItemSubtitle>
              </div>
            </div>
          </VListItem>

          <VDivider class="my-2" />

          <!-- Profil -->
          <VListItem
            v-if="getUserId"
            :to="{ name: 'apps-user-view-id', params: { id: getUserId } }"
          >
            <template #prepend>
              <VIcon
                icon="tabler-user"
                size="22"
              />
            </template>
            <VListItemTitle>Profil</VListItemTitle>
          </VListItem>

          <!-- Paramètres -->
         <!--  <VListItem
            :to="{ name: 'pages-account-settings-tab', params: { tab: 'account' } }"
          >
            <template #prepend>
              <VIcon
                icon="tabler-settings"
                size="22"
              />
            </template>
            <VListItemTitle>Paramètres</VListItemTitle>
          </VListItem> -->

          <!-- Plan de facturation -->
         <!--  <VListItem
            :to="{ name: 'pages-account-settings-tab', params: { tab: 'billing-plans' } }"
          >
            <template #prepend>
              <VIcon
                icon="tabler-file-dollar"
                size="22"
              />
            </template>
            <VListItemTitle>Plan de facturation</VListItemTitle>
            <template #append>
              <VBadge
                rounded="sm"
                class="me-3"
                color="error"
              >
                4
              </VBadge>
            </template>
          </VListItem> -->

          <VDivider class="my-2" />

          <!-- Tarification -->
         <!--  <VListItem :to="{ name: 'pages-pricing' }">
            <template #prepend>
              <VIcon
                icon="tabler-currency-dollar"
                size="22"
              />
            </template>
            <VListItemTitle>Tarification</VListItemTitle>
          </VListItem> -->

          <!-- FAQ -->
         <!--  <VListItem :to="{ name: 'pages-faq' }">
            <template #prepend>
              <VIcon
                icon="tabler-question-mark"
                size="22"
              />
            </template>
            <VListItemTitle>FAQ</VListItemTitle>
          </VListItem> -->

          <div class="px-4 py-2">
            <VBtn
              block
              size="small"
              color="error"
              append-icon="tabler-logout"
              @click="logout"
            >
              Déconnexion
            </VBtn>
          </div>
        </VList>
      </VMenu>
      <!-- !SECTION -->
    </VAvatar>
  </VBadge>
</template>
