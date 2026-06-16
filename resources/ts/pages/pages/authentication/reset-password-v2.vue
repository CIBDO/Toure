<script setup lang="ts">
import { VForm } from 'vuetify/components/VForm'
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'

import authV2ResetPasswordIllustrationDark from '@images/pages/auth-v2-reset-password-illustration-dark.png'
import authV2ResetPasswordIllustrationLight from '@images/pages/auth-v2-reset-password-illustration-light.png'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'

const route = useRoute()
const router = useRouter()

definePage({
  meta: {
    layout: 'blank',
    public: true,
  },
})

const form = ref({
  newPassword: '',
  confirmPassword: '',
})

const authThemeImg = useGenerateImageVariant(authV2ResetPasswordIllustrationLight,
  authV2ResetPasswordIllustrationDark,
)

const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)

const isPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)
const isLoading = ref(false)
const successMessage = ref<string | undefined>(undefined)
const errorMessage = ref<string | undefined>(undefined)

const errors = ref<Record<string, string | undefined>>({
  password: undefined,
  password_confirmation: undefined,
  token: undefined,
})

const refVForm = ref<VForm>()

// Récupérer le token et l'email depuis les query parameters
const token = computed(() => route.query.token as string | undefined)
const email = computed(() => route.query.email as string | undefined)

// Vérifier que le token et l'email sont présents
const isValidLink = computed(() => !!(token.value && email.value))

const resetPassword = async () => {
  if (!token.value || !email.value) {
    errorMessage.value = 'Lien de réinitialisation invalide. Veuillez demander un nouveau lien.'
    return
  }

  try {
    isLoading.value = true
    successMessage.value = undefined
    errorMessage.value = undefined
    errors.value = {
      password: undefined,
      password_confirmation: undefined,
      token: undefined,
    }

    await $api('/auth/reset-password', {
      method: 'POST',
      body: {
        email: email.value,
        token: token.value,
        password: form.value.newPassword,
        password_confirmation: form.value.confirmPassword,
      },
      onResponseError({ response }) {
        if (response._data?.errors) {
          errors.value = {
            password: response._data.errors.password?.[0] || response._data.errors.password || undefined,
            password_confirmation: response._data.errors.password_confirmation?.[0] || response._data.errors.password_confirmation || undefined,
            token: response._data.errors.token?.[0] || response._data.errors.token || undefined,
          }
        } else {
          errorMessage.value = response._data?.message || 'Une erreur est survenue'
        }
      },
    })

    // Succès : rediriger vers la page de connexion
    successMessage.value = 'Votre mot de passe a été réinitialisé avec succès. Redirection vers la page de connexion...'
    
    await nextTick(() => {
      setTimeout(() => {
        router.replace({
          name: 'login',
          query: {
            message: 'password_reset',
          },
        })
      }, 2000)
    })
  }
  catch (err: any) {
    console.error('Erreur lors de la réinitialisation du mot de passe:', err)
    if (!errors.value.password && !errors.value.password_confirmation && !errors.value.token && !errorMessage.value) {
      if (err.data?.errors) {
        errors.value = {
          password: err.data.errors.password?.[0] || err.data.errors.password || undefined,
          password_confirmation: err.data.errors.password_confirmation?.[0] || err.data.errors.password_confirmation || undefined,
          token: err.data.errors.token?.[0] || err.data.errors.token || undefined,
        }
      } else {
        errorMessage.value = err.data?.message || 'Une erreur est survenue lors de la réinitialisation du mot de passe'
      }
    }
  }
  finally {
    isLoading.value = false
  }
}

const onSubmit = () => {
  refVForm.value?.validate()
    .then(({ valid: isValid }) => {
      if (isValid)
        resetPassword()
    })
}

// Règle de validation personnalisée pour la confirmation du mot de passe
const passwordConfirmationRule = (value: string) => {
  if (!value) return 'La confirmation du mot de passe est requise'
  if (value !== form.value.newPassword) return 'Les mots de passe ne correspondent pas'
  return true
}
</script>

<template>
  <RouterLink to="/">
    <div class="auth-logo d-flex align-center gap-x-3">
      <VNodeRenderer :nodes="themeConfig.app.logo" />
      <h1 class="auth-title">
        {{ themeConfig.app.title }}
      </h1>
    </div>
  </RouterLink>

  <VRow
    no-gutters
    class="auth-wrapper bg-surface"
  >
    <VCol
      md="8"
      class="d-none d-md-flex"
    >
      <div class="position-relative bg-background w-100 me-0">
        <div
          class="d-flex align-center justify-center w-100 h-100"
          style="padding-inline: 150px;"
        >
          <VImg
            max-width="451"
            :src="authThemeImg"
            class="auth-illustration mt-16 mb-2"
          />
        </div>

        <img
          class="auth-footer-mask flip-in-rtl"
          :src="authThemeMask"
          alt="auth-footer-mask"
          height="280"
          width="100"
        >
      </div>
    </VCol>

    <VCol
      cols="12"
      md="4"
      class="auth-card-v2 d-flex align-center justify-center"
    >
      <VCard
        flat
        :max-width="500"
        class="mt-12 mt-sm-0 pa-6"
      >
        <VCardText>
          <h4 class="text-h4 mb-1">
            Réinitialiser le mot de passe 🔒
          </h4>
          <p class="mb-0">
            Votre nouveau mot de passe doit être différent des mots de passe précédemment utilisés
          </p>
        </VCardText>

        <VCardText>
          <VAlert
            v-if="!isValidLink"
            color="error"
            variant="tonal"
            class="mb-4"
          >
            <p class="text-sm mb-0">
              ❌ Lien de réinitialisation invalide ou manquant. Veuillez demander un nouveau lien de réinitialisation.
            </p>
          </VAlert>
          <VAlert
            v-if="successMessage"
            color="success"
            variant="tonal"
            class="mb-4"
          >
            <p class="text-sm mb-0">
              {{ successMessage }}
            </p>
          </VAlert>
          <VAlert
            v-if="errorMessage"
            color="error"
            variant="tonal"
            class="mb-4"
          >
            <p class="text-sm mb-0">
              {{ errorMessage }}
            </p>
          </VAlert>
          <VForm
            v-if="isValidLink"
            ref="refVForm"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <!-- password -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.newPassword"
                  autofocus
                  label="Nouveau mot de passe"
                  placeholder="············"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  :rules="[requiredValidator, (v: string) => v.length >= 8 || 'Le mot de passe doit contenir au moins 8 caractères']"
                  :error-messages="errors.password"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                />
              </VCol>

              <!-- Confirm Password -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.confirmPassword"
                  label="Confirmer le mot de passe"
                  placeholder="············"
                  :type="isConfirmPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isConfirmPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  :rules="[requiredValidator, passwordConfirmationRule]"
                  :error-messages="errors.password_confirmation"
                  @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
                />
              </VCol>

              <!-- Set password -->
              <VCol cols="12">
                <VBtn
                  block
                  type="submit"
                  :loading="isLoading"
                  :disabled="isLoading || !isValidLink"
                >
                  Définir le nouveau mot de passe
                </VBtn>
              </VCol>

              <!-- back to login -->
              <VCol cols="12">
                <RouterLink
                  class="d-flex align-center justify-center"
                  :to="{ name: 'login' }"
                >
                  <VIcon
                    icon="tabler-chevron-left"
                    size="20"
                    class="me-1 flip-in-rtl"
                  />
                  <span>Retour à la connexion</span>
                </RouterLink>
              </VCol>
            </VRow>
          </VForm>
          <VRow v-else>
            <VCol cols="12">
              <RouterLink
                class="d-flex align-center justify-center"
                :to="{ name: 'forgot-password' }"
              >
                <VIcon
                  icon="tabler-chevron-left"
                  size="20"
                  class="me-1 flip-in-rtl"
                />
                <span>Demander un nouveau lien</span>
              </RouterLink>
            </VCol>
          </VRow>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth.scss";
</style>
