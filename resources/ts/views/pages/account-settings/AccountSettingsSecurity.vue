<script lang="ts" setup>
import { $api } from '@/utils/api'
import { confirmedValidator, requiredValidator } from '@validators'
import { VForm } from 'vuetify/components/VForm'
import laptopGirl from '@images/illustrations/laptop-girl.png'

const userData = useCookie<any>('userData')

const isCurrentPasswordVisible = ref(false)
const isNewPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)
const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')
const isLoading = ref(false)
const successMessage = ref<string | null>(null)
const errorMessage = ref<string | null>(null)
const errors = ref<Record<string, string | undefined>>({
  new_password: undefined,
  new_password_confirmation: undefined,
})

const refVForm = ref<VForm>()

const passwordRequirements = [
  'Minimum 8 caractères - plus c\'est long, mieux c\'est',
  'Au moins une lettre minuscule',
  'Au moins un chiffre, un symbole ou un caractère d\'espacement',
]

const changePassword = async () => {
  if (!userData.value?.id) {
    errorMessage.value = 'ID utilisateur manquant.'
    return
  }

  try {
    isLoading.value = true
    successMessage.value = null
    errorMessage.value = null
    errors.value = { new_password: undefined, new_password_confirmation: undefined }

    await $api('/auth/change-password', {
      method: 'POST',
      body: {
        user_id: userData.value.id,
        new_password: newPassword.value,
        new_password_confirmation: confirmPassword.value,
      },
      onResponseError({ response }) {
        if (response._data?.errors) {
          errors.value.new_password = response._data.errors.new_password?.[0] || response._data.errors.new_password || undefined
          errors.value.new_password_confirmation = response._data.errors.new_password_confirmation?.[0] || response._data.errors.new_password_confirmation || undefined
        } else {
          errorMessage.value = response._data?.message || 'Une erreur est survenue lors du changement de mot de passe.'
        }
      },
    })

    successMessage.value = 'Mot de passe changé avec succès.'
    newPassword.value = ''
    confirmPassword.value = ''
    currentPassword.value = ''
    refVForm.value?.resetValidation()
  } catch (err: any) {
    console.error('Erreur lors du changement de mot de passe:', err)
    if (!errors.value.new_password && !errors.value.new_password_confirmation) {
      errorMessage.value = err.data?.message || err.response?._data?.message || 'Une erreur est survenue lors du changement de mot de passe.'
    }
  } finally {
    isLoading.value = false
  }
}

const onSubmit = () => {
  refVForm.value?.validate()
    .then(({ valid: isValid }) => {
      if (isValid)
        changePassword()
    })
}

const serverKeys = [
  {
    name: 'Server Key 1',
    key: '23eaf7f0-f4f7-495e-8b86-fad3261282ac',
    createdOn: '28 Apr 2021, 18:20 GTM+4:10',
    permission: 'Full Access',
  },
  {
    name: 'Server Key 2',
    key: 'bb98e571-a2e2-4de8-90a9-2e231b5e99',
    createdOn: '12 Feb 2021, 10:30 GTM+2:30',
    permission: 'Read Only',
  },
  {
    name: 'Server Key 3',
    key: '2e915e59-3105-47f2-8838-6e46bf83b711',
    createdOn: '28 Dec 2020, 12:21 GTM+4:10',
    permission: 'Full Access',
  },
]

const recentDevicesHeaders = [
  { title: 'BROWSER', key: 'browser' },
  { title: 'DEVICE', key: 'device' },
  { title: 'LOCATION', key: 'location' },
  { title: 'RECENT ACTIVITY', key: 'recentActivity' },
]

const recentDevices = [
  {
    browser: 'Chrome on Windows',
    device: 'HP Spectre 360',
    location: 'New York, NY',
    recentActivity: '28 Apr 2022, 18:20',
    deviceIcon: { icon: 'tabler-brand-windows', color: 'primary' },
  },
  {
    browser: 'Chrome on iPhone',
    device: 'iPhone 12x',
    location: 'Los Angeles, CA',
    recentActivity: '20 Apr 2022, 10:20',
    deviceIcon: { icon: 'tabler-device-mobile', color: 'error' },
  },
  {
    browser: 'Chrome on Android',
    device: 'Oneplus 9 Pro',
    location: 'San Francisco, CA',
    recentActivity: '16 Apr 2022, 04:20',
    deviceIcon: { icon: 'tabler-brand-android', color: 'success' },
  },
  {
    browser: 'Chrome on macOS',
    device: 'Apple iMac',
    location: 'New York, NY',
    recentActivity: '28 Apr 2022, 18:20',
    deviceIcon: { icon: 'tabler-brand-apple', color: 'secondary' },
  },
  {
    browser: 'Chrome on Windows',
    device: 'HP Spectre 360',
    location: 'Los Angeles, CA',
    recentActivity: '20 Apr 2022, 10:20',
    deviceIcon: { icon: 'tabler-brand-windows', color: 'primary' },
  },
  {
    browser: 'Chrome on Android',
    device: 'Oneplus 9 Pro',
    location: 'San Francisco, CA',
    recentActivity: '16 Apr 2022, 04:20',
    deviceIcon: { icon: 'tabler-brand-android', color: 'success' },
  },
]

const isOneTimePasswordDialogVisible = ref(false)
</script>

<template>
  <VRow>
    <!-- SECTION: Change Password -->
    <VCol cols="12">
      <VCard title="Changer le mot de passe">
        <VForm
          ref="refVForm"
          @submit.prevent="onSubmit"
        >
          <VCardText class="pt-0">
            <!-- Messages -->
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
            <!-- 👉 New Password -->
            <VRow>
              <VCol
                cols="12"
                md="6"
              >
                <!-- 👉 new password -->
                <AppTextField
                  v-model="newPassword"
                  :type="isNewPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isNewPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  label="Nouveau mot de passe"
                  autocomplete="on"
                  placeholder="············"
                  :rules="[requiredValidator]"
                  :error-messages="errors.new_password"
                  @click:append-inner="isNewPasswordVisible = !isNewPasswordVisible"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <!-- 👉 confirm password -->
                <AppTextField
                  v-model="confirmPassword"
                  :type="isConfirmPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isConfirmPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  label="Confirmer le mot de passe"
                  autocomplete="on"
                  placeholder="············"
                  :rules="[requiredValidator, confirmedValidator(newPassword, confirmPassword)]"
                  :error-messages="errors.new_password_confirmation"
                  @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
                />
              </VCol>
            </VRow>
          </VCardText>

          <!-- 👉 Password Requirements -->
          <VCardText>
            <h6 class="text-h6 text-medium-emphasis mb-4">
              Exigences du mot de passe :
            </h6>

            <VList class="card-list">
              <VListItem
                v-for="item in passwordRequirements"
                :key="item"
                :title="item"
                class="text-medium-emphasis"
              >
                <template #prepend>
                  <VIcon
                    size="10"
                    icon="tabler-circle-filled"
                  />
                </template>
              </VListItem>
            </VList>
          </VCardText>

          <!-- 👉 Action Buttons -->
          <VCardText class="d-flex flex-wrap gap-4">
            <VBtn
              type="submit"
              :loading="isLoading"
            >
              Enregistrer les modifications
            </VBtn>

            <VBtn
              type="reset"
              color="secondary"
              variant="tonal"
              @click="() => { newPassword = ''; confirmPassword = ''; refVForm?.resetValidation() }"
            >
              Réinitialiser
            </VBtn>
          </VCardText>
        </VForm>
      </VCard>
    </VCol>
    <!-- !SECTION -->

    <!-- SECTION Two-steps verification -->
    <VCol cols="12">
      <VCard title="Two-steps verification">
        <VCardText>
          <h5 class="text-h5 text-medium-emphasis mb-4">
            Two factor authentication is not enabled yet.
          </h5>
          <p class="mb-6">
            Two-factor authentication adds an additional layer of security to your account by
            requiring more than just a password to log in.
            <a
              href="javascript:void(0)"
              class="text-decoration-none"
            >Learn more.</a>
          </p>

          <VBtn @click="isOneTimePasswordDialogVisible = true">
            Enable two-factor authentication
          </VBtn>
        </VCardText>
      </VCard>
    </VCol>
    <!-- !SECTION -->

    <VCol cols="12">
      <!-- SECTION: Create an API key -->
      <VCard title="Create an API key">
        <VRow no-gutters>
          <!-- 👉 Choose API Key -->
          <VCol
            cols="12"
            md="5"
            order-md="0"
            order="1"
          >
            <VCardText class="pt-1">
              <VForm @submit.prevent="() => { }">
                <VRow>
                  <!-- 👉 Choose API Key -->
                  <VCol cols="12">
                    <AppSelect
                      label="Choose the API key type you want to create"
                      placeholder="Select API key type"
                      :items="['Full Control', 'Modify', 'Read & Execute', 'List Folder Contents', 'Read Only', 'Read & Write']"
                    />
                  </VCol>

                  <!-- 👉 Name the API Key -->
                  <VCol cols="12">
                    <AppTextField
                      label="Name the API key"
                      placeholder="Name the API key"
                    />
                  </VCol>

                  <!-- 👉 Create Key Button -->
                  <VCol cols="12">
                    <VBtn
                      type="submit"
                      block
                    >
                      Create Key
                    </VBtn>
                  </VCol>
                </VRow>
              </VForm>
            </VCardText>
          </VCol>

          <!-- 👉 Lady image -->
          <VCol
            cols="12"
            md="7"
            order="0"
            order-md="1"
            class="d-flex flex-column justify-center align-center"
          >
            <VImg
              :src="laptopGirl"
              :width="$vuetify.display.smAndDown ? '150' : '200'"
              :style="$vuetify.display.smAndDown ? 'margin-block-end: 24px' : 'position: absolute; bottom: 0;'"
            />
          </VCol>
        </VRow>
      </VCard>
      <!-- !SECTION -->
    </VCol>

    <VCol cols="12">
      <!-- SECTION: API Keys List -->
      <VCard>
        <VCardItem class="pb-4">
          <VCardTitle>API Key List & Access</VCardTitle>
        </VCardItem>
        <VCardText>
          An API key is a simple encrypted string that identifies an application without any principal. They are useful
          for accessing public data anonymously, and are used to associate API requests with your project for quota and
          billing.
        </VCardText>

        <!-- 👉 Server Status -->
        <VCardText class="d-flex flex-column gap-y-6">
          <VCard
            v-for="serverKey in serverKeys"
            :key="serverKey.key"
            flat
            class="pa-4"
            color="rgba(var(--v-theme-on-surface),var(--v-hover-opacity))"
          >
            <div class="d-flex flex-column gap-y-2">
              <div class="d-flex align-center flex-wrap">
                <h5 class="text-h5 me-3">
                  {{ serverKey.name }}
                </h5>
                <VChip
                  label
                  color="primary"
                  size="small"
                >
                  {{ serverKey.permission }}
                </VChip>
              </div>
              <div class="d-flex align-center text-base font-weight-medium">
                <h6 class="text-h6 text-medium-emphasis me-3">
                  {{ serverKey.key }}
                </h6>
                <div class="cursor-pointer">
                  <VIcon
                    icon="tabler-copy"
                    size="20"
                  />
                </div>
              </div>
              <div class="text-disabled">
                Created on {{ serverKey.createdOn }}
              </div>
            </div>
          </VCard>
        </VCardText>
      </VCard>
      <!-- !SECTION -->
    </VCol>

    <!-- SECTION Recent Devices -->
    <VCol cols="12">
      <!-- 👉 Table -->
      <VCard title="Recent Devices">
        <VDivider />

        <VDataTable
          :headers="recentDevicesHeaders"
          :items="recentDevices"
          hide-default-footer
          class="text-no-wrap"
        >
          <template #item.browser="{ item }">
            <div class="d-flex">
              <VIcon
                start
                size="22"
                :icon="item.deviceIcon.icon"
                :color="item.deviceIcon.color"
              />
              <div class="text-high-emphasis text-body-1 font-weight-medium">
                {{ item.browser }}
              </div>
            </div>
          </template>
          <!-- TODO Refactor this after vuetify provides proper solution for removing default footer -->
          <template #bottom />
        </VDataTable>
      </VCard>
    </VCol>
    <!-- !SECTION -->
  </VRow>

  <!-- SECTION Enable One time password -->
  <TwoFactorAuthDialog v-model:isDialogVisible="isOneTimePasswordDialogVisible" />
  <!-- !SECTION -->
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 16px;
}

.server-close-btn {
  inset-inline-end: 0.5rem;
}
</style>
