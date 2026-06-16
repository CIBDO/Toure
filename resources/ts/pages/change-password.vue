<script setup lang="ts">
import { VForm } from 'vuetify/components/VForm'
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'

definePage({
  meta: {
    layout: 'blank',
  },
})

const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)

const route = useRoute()
const router = useRouter()

const isPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)

const errors = ref<Record<string, string | undefined>>({
  new_password: undefined,
  new_password_confirmation: undefined,
})

const refVForm = ref<VForm>()

const form = ref({
  newPassword: '',
  confirmPassword: '',
})

// Récupérer l'ID utilisateur depuis les cookies (après connexion) ou depuis les query params
const userData = useCookie('userData').value
const userId = computed(() => {
  // Priorité : query params > cookies
  if (route.query.user_id) {
    return Number(route.query.user_id)
  }
  if (userData && typeof userData === 'object' && 'id' in userData) {
    return Number((userData as { id: number }).id)
  }
  return null
})

const isLoading = ref(false)

const changePassword = async () => {
  if (!userId.value) {
    errors.value.new_password = 'ID utilisateur manquant. Veuillez vous reconnecter.'
    return
  }

  try {
    isLoading.value = true
    errors.value = {
      new_password: undefined,
      new_password_confirmation: undefined,
    }

    await $api('/auth/change-password', {
      method: 'POST',
      body: {
        user_id: userId.value,
        new_password: form.value.newPassword,
        new_password_confirmation: form.value.confirmPassword,
      },
      onResponseError({ response }) {
        console.error('Erreur API (onResponseError):', response._data)
        if (response._data?.errors) {
          errors.value = {
            new_password: response._data.errors.new_password?.[0] || response._data.errors.new_password || undefined,
            new_password_confirmation: response._data.errors.new_password_confirmation?.[0] || response._data.errors.new_password_confirmation || undefined,
          }
        } else {
          errors.value = {
            new_password: response._data?.message || 'Une erreur est survenue',
            new_password_confirmation: undefined,
          }
        }
      },
    })

    // Succès : nettoyer les cookies d'authentification et rediriger vers la page de connexion
    // Supprimer les cookies et le stockage des règles CASL
    useCookie('accessToken').value = null
    useCookie('userData').value = null
    useCookie('userAbilityRules').value = null
    try {
      if (typeof window !== 'undefined')
        window.localStorage.removeItem('CANAM-userAbilityRules')
    } catch {}
    
    await nextTick(() => {
      router.replace({
        name: 'login',
        query: {
          message: 'password_changed',
        },
      })
    })
  }
  catch (err: any) {
    console.error('Erreur lors du changement de mot de passe:', err)
    // Si les erreurs n'ont pas déjà été définies par onResponseError
    if (!errors.value.new_password && !errors.value.new_password_confirmation) {
      // Tenter de récupérer les erreurs depuis l'objet d'erreur
      if (err.data?.errors) {
        errors.value = {
          new_password: err.data.errors.new_password?.[0] || err.data.errors.new_password || undefined,
          new_password_confirmation: err.data.errors.new_password_confirmation?.[0] || err.data.errors.new_password_confirmation || undefined,
        }
      } else if (err.response?._data?.errors) {
        errors.value = {
          new_password: err.response._data.errors.new_password?.[0] || err.response._data.errors.new_password || undefined,
          new_password_confirmation: err.response._data.errors.new_password_confirmation?.[0] || err.response._data.errors.new_password_confirmation || undefined,
        }
      } else {
        errors.value = {
          new_password: err.data?.message || err.response?._data?.message || 'Une erreur est survenue lors du changement de mot de passe',
          new_password_confirmation: undefined,
        }
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
        changePassword()
    })
}
</script>

<template>
  <!-- <RouterLink to="/">
    <div class="auth-logo d-flex align-center gap-x-3">
      <VNodeRenderer :nodes="themeConfig.app.logo" />
      <h1 class="auth-title">
        {{ themeConfig.app.title }}
      </h1>
    </div>
  </RouterLink> -->

  <VRow
    no-gutters
    class="auth-wrapper bg-surface"
  >
    <VCol
      cols="12"
      md="8"
      class="d-none d-md-flex"
    >
      <div class="position-relative bg-background w-100 me-0">
        <div
          class="d-flex align-center justify-center w-100 h-100"
          style="padding-inline: 6.25rem;"
        >
          <div class="text-center">
            <h2 class="text-h2 mb-4">
              Changement de mot de passe
            </h2>
            <p class="text-body-1">
              Pour des raisons de sécurité, vous devez changer votre mot de passe lors de votre première connexion.
            </p>
          </div>
        </div>

        <img
          class="auth-footer-mask"
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
        class="mt-12 mt-sm-0 pa-4"
      >
        <VCardText>
          <h4 class="text-h4 mb-1">
            Changement de mot de passe obligatoire 🔒
          </h4>
          <p class="mb-0">
            Vous devez définir un nouveau mot de passe pour continuer
          </p>
        </VCardText>

        <VCardText>
          <VAlert
            color="warning"
            variant="tonal"
            class="mb-4"
          >
            <p class="text-sm mb-0">
              ⚠️ Pour des raisons de sécurité, vous devez changer votre mot de passe temporaire.
            </p>
          </VAlert>
        </VCardText>

        <VCardText>
          <VForm
            ref="refVForm"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <!-- Nouveau mot de passe -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.newPassword"
                  label="Nouveau mot de passe"
                  placeholder="············"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  :rules="[requiredValidator]"
                  :error-messages="errors.new_password"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  autofocus
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                />
              </VCol>

              <!-- Confirmation du mot de passe -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.confirmPassword"
                  label="Confirmer le mot de passe"
                  placeholder="············"
                  :type="isConfirmPasswordVisible ? 'text' : 'password'"
                  :rules="[requiredValidator, (value: string) => {
                    if (value !== form.newPassword) {
                      return 'Les mots de passe ne correspondent pas'
                    }
                    return true
                  }]"
                  :error-messages="errors.new_password_confirmation"
                  :append-inner-icon="isConfirmPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
                />
              </VCol>

              <!-- Bouton de soumission -->
              <VCol cols="12">
                <VBtn
                  block
                  type="submit"
                  :loading="isLoading"
                >
                  Changer le mot de passe
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth.scss";
</style>
