<script setup lang="ts">
import { VForm } from 'vuetify/components/VForm'
import { emailValidator, requiredValidator } from '@validators'

const email = ref('')
const isLoading = ref(false)
const successMessage = ref<string | undefined>(undefined)
const errorMessage = ref<string | undefined>(undefined)
const emailError = ref<string | undefined>(undefined)

const router = useRouter()
const refVForm = ref<VForm>()

definePage({
  meta: {
    layout: 'blank',
    unauthenticatedOnly: true,
  },
})

const sendResetLink = async () => {
  try {
    isLoading.value = true
    successMessage.value = undefined
    errorMessage.value = undefined
    emailError.value = undefined

    await $api('/auth/forgot-password', {
      method: 'POST',
      body: {
        email: email.value,
      },
      onResponseError({ response }) {
        if (response._data?.errors) {
          emailError.value = response._data.errors.email?.[0] || response._data.errors.email || undefined
        } else {
          errorMessage.value = response._data?.message || 'Une erreur est survenue'
        }
      },
    })

    // Succès : afficher le message de succès
    successMessage.value = 'Si cet email existe dans notre système, un lien de réinitialisation vous a été envoyé par email.'
    
    // Réinitialiser le formulaire après 3 secondes
    setTimeout(() => {
      email.value = ''
      refVForm.value?.reset()
    }, 3000)
  }
  catch (err: any) {
    console.error('Erreur lors de la demande de réinitialisation:', err)
    if (!emailError.value && !errorMessage.value) {
      errorMessage.value = err.data?.message || 'Une erreur est survenue lors de la demande de réinitialisation'
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
        sendResetLink()
    })
}
</script>

<template>
  <VContainer
    fluid
    class="forgot-password-container fill-height"
  >
    <VRow
      class="fill-height"
      align="center"
      justify="center"
    >
      <VCol
        cols="12"
        sm="10"
        md="8"
        lg="6"
        xl="5"
      >
        <VCard
          flat
          class="forgot-password-card"
          :max-width="480"
        >
          <VCardText class="pa-6 pa-md-8">
            <!-- En-tête -->
            <div class="text-center mb-6">
              <h1 class="text-h4 mb-3 font-weight-bold">
                Mot de passe oublié ? 🔒
              </h1>
              <p class="text-body-1 text-medium-emphasis mb-0">
                Entrez votre adresse email et nous vous enverrons les instructions pour réinitialiser votre mot de passe
              </p>
            </div>

            <!-- Messages -->
            <VAlert
              v-if="successMessage"
              color="success"
              variant="tonal"
              class="mb-4"
              closable
              density="compact"
            >
              {{ successMessage }}
            </VAlert>
            
            <VAlert
              v-if="errorMessage"
              color="error"
              variant="tonal"
              class="mb-4"
              closable
              density="compact"
              @click:close="errorMessage = undefined"
            >
              {{ errorMessage }}
            </VAlert>

            <!-- Formulaire -->
            <VForm
              ref="refVForm"
              @submit.prevent="onSubmit"
            >
              <VRow>
                <!-- Email -->
                <VCol cols="12">
                  <AppTextField
                    v-model="email"
                    autofocus
                    label="Adresse email"
                    type="email"
                    placeholder="votre.email@exemple.com"
                    density="comfortable"
                    variant="outlined"
                    hide-details="auto"
                    :rules="[requiredValidator, emailValidator]"
                    :error-messages="emailError ? [emailError] : []"
                    prepend-inner-icon="tabler-mail"
                    clearable
                  />
                </VCol>

                <!-- Bouton d'envoi -->
                <VCol cols="12">
                  <VBtn
                    block
                    type="submit"
                    color="primary"
                    size="large"
                    :loading="isLoading"
                    :disabled="isLoading"
                    class="me-1 flip-in-rtl"
                  >
                    Envoyer le lien de réinitialisation
                  </VBtn>
                </VCol>

                <!-- Retour à la connexion -->
                <VCol cols="12">
                  <div class="text-center mt-4">
                    <RouterLink
                      class="d-inline-flex align-center text-primary text-decoration-none"
                      to="/login"
                    >
                      <VIcon
                        icon="tabler-chevron-left"
                        size="20"
                        class="me-1 flip-in-rtl"
                      />
                      <span>Retour à la connexion</span>
                    </RouterLink>
                  </div>
                </VCol>
              </VRow>
            </VForm>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
</template>

<style lang="scss" scoped>
.forgot-password-container {
  background-color: rgb(var(--v-theme-surface));
  min-height: 100vh;
  padding: 2rem 1rem;
}

.forgot-password-card {
  margin: 0 auto;
  box-shadow: 0 2px 8px rgba(var(--v-shadow-key-umbra-color), 0.08);
}

@media (max-width: 959px) {
  .forgot-password-container {
    padding: 1.5rem 1rem;
  }
  
  .forgot-password-card {
    box-shadow: none;
  }
}
</style>
