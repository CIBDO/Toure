<script setup lang="ts">
interface Props {
  userData?: {
    id: number
    email: string
    statut: string
    must_change_password?: boolean
  }
}

const props = defineProps<Props>()

const isNewPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)
const isLoading = ref(false)
const successMessage = ref<string | undefined>(undefined)
const errorMessage = ref<string | undefined>(undefined)

const form = ref({
  newPassword: '',
  confirmPassword: '',
})

const changePassword = async () => {
  if (!props.userData?.id) return

  try {
    isLoading.value = true
    successMessage.value = undefined
    errorMessage.value = undefined

    await $api(`/users/${props.userData.id}`, {
      method: 'PUT',
      body: {
        password: form.value.newPassword,
      },
    })

    successMessage.value = 'Mot de passe modifié avec succès'
    form.value.newPassword = ''
    form.value.confirmPassword = ''
  } catch (error: any) {
    console.error('Erreur lors du changement de mot de passe:', error)
    errorMessage.value = error.data?.message || 'Une erreur est survenue lors du changement de mot de passe'
  } finally {
    isLoading.value = false
  }
}

// Recent devices Headers
const recentDeviceHeader = [
  { title: 'BROWSER', key: 'browser' },
  { title: 'DEVICE', key: 'device' },
  { title: 'LOCATION', key: 'location' },
  { title: 'RECENT ACTIVITY', key: 'activity' },
]

const recentDevices = [
  {
    browser: ' Chrome on Windows',
    icon: 'tabler-brand-windows',
    color: 'info',
    device: 'HP Spectre 360',
    location: 'Switzerland',
    activity: '10, July 2021 20:07',
  },
  {
    browser: 'Chrome on Android',
    icon: 'tabler-brand-android',
    color: 'success',
    device: 'Oneplus 9 Pro',
    location: 'Dubai',
    activity: '14, July 2021 15:15',
  },
  {
    browser: 'Chrome on macOS',
    icon: 'tabler-brand-apple',
    color: 'secondary',
    device: 'Apple iMac',
    location: 'India',
    activity: '16, July 2021 16:17',
  },
  {
    browser: 'Chrome on iPhone',
    icon: 'tabler-device-mobile',
    color: 'error',
    device: 'iPhone 12x',
    location: 'Australia',
    activity: '13, July 2021 10:10',
  },

]
</script>

<template>
  <VRow>
    <VCol cols="12">
      <!-- 👉 Change password -->
      <VCard title="Change Password">
        <VCardText>
          <VAlert
            closable
            variant="tonal"
            color="warning"
            class="mb-4"
            title="Ensure that these requirements are met"
            text="Minimum 8 characters long, uppercase & symbol"
          />

          <VAlert
            v-if="successMessage"
            color="success"
            variant="tonal"
            class="mb-4"
          >
            {{ successMessage }}
          </VAlert>
          <VAlert
            v-if="errorMessage"
            color="error"
            variant="tonal"
            class="mb-4"
          >
            {{ errorMessage }}
          </VAlert>
          <VForm @submit.prevent="changePassword">
            <VRow>
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="form.newPassword"
                  label="Nouveau mot de passe"
                  placeholder="············"
                  :type="isNewPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isNewPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  :rules="[requiredValidator, (v: string) => v.length >= 8 || 'Le mot de passe doit contenir au moins 8 caractères']"
                  @click:append-inner="isNewPasswordVisible = !isNewPasswordVisible"
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="form.confirmPassword"
                  label="Confirmer le mot de passe"
                  placeholder="············"
                  :type="isConfirmPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isConfirmPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  :rules="[requiredValidator, (v: string) => v === form.newPassword || 'Les mots de passe ne correspondent pas']"
                  @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
                />
              </VCol>

              <VCol cols="12">
                <VBtn
                  type="submit"
                  :loading="isLoading"
                  :disabled="isLoading"
                >
                  Changer le mot de passe
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12">
      <!-- 👉 Informations de sécurité -->
      <VCard title="Informations de sécurité">
        <VCardText>
          <VList class="card-list">
            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  Statut du compte:
                </h6>
              </VListItemTitle>
              <template #append>
                <VChip
                  label
                  :color="props.userData?.statut === 'ACTIF' ? 'success' : 'warning'"
                  size="small"
                >
                  {{ props.userData?.statut || 'N/A' }}
                </VChip>
              </template>
            </VListItem>
            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  Changement de mot de passe obligatoire:
                </h6>
              </VListItemTitle>
              <template #append>
                <VChip
                  label
                  :color="props.userData?.must_change_password ? 'warning' : 'success'"
                  size="small"
                >
                  {{ props.userData?.must_change_password ? 'Oui' : 'Non' }}
                </VChip>
              </template>
            </VListItem>
          </VList>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>
