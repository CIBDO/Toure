<script setup lang="ts">
import { $api } from '@/utils/api'
import { emailValidator, requiredValidator } from '@validators'
import { VForm } from 'vuetify/components/VForm'

const userData = useCookie<any>('userData')

const isLoading = ref(false)
const isInitialLoading = ref(true)
const successMessage = ref<string | null>(null)
const errorMessage = ref<string | null>(null)

// Fonction pour charger les données utilisateur fraîches
const loadUserData = async () => {
  if (!userData.value?.id) {
    isInitialLoading.value = false
    return
  }

  try {
    isInitialLoading.value = true
    const response = await $api(`/users/${userData.value.id}`)
    // Mettre à jour le cookie avec les données fraîches
    if (response) {
      userData.value = { ...userData.value, ...response }
      // Mettre à jour le formulaire avec les nouvelles données
      form.value = {
        nom: response.nom || '',
        prenom: response.prenom || '',
        email: response.email || '',
        telephone: response.telephone || '',
        fonction: response.fonction || '',
        unite_service: response.unite_service || '',
        region: response.region || '',
      }
    }
  } catch (err: any) {
    console.error('Erreur lors du chargement des données utilisateur:', err)
    errorMessage.value = 'Impossible de charger les données utilisateur.'
  } finally {
    isInitialLoading.value = false
  }
}

// Charger les données au montage
onMounted(() => {
  loadUserData()
})

// Formulaire avec données IAM - initialisé avec les données du cookie
const form = ref({
  nom: '',
  prenom: '',
  email: '',
  telephone: '',
  fonction: '',
  unite_service: '',
  region: '',
})

// Initialiser le formulaire avec les données du cookie au montage
watch(() => userData.value, (newData) => {
  if (newData) {
    form.value = {
      nom: newData.nom || '',
      prenom: newData.prenom || '',
      email: newData.email || '',
      telephone: newData.telephone || '',
      fonction: newData.fonction || '',
      unite_service: newData.unite_service || '',
      region: newData.region || '',
    }
  }
}, { immediate: true })

const errors = ref<Record<string, string | undefined>>({})

const refVForm = ref<VForm>()

const saveAccount = async () => {
  if (!userData.value?.id) {
    errorMessage.value = 'ID utilisateur manquant.'
    return
  }

  try {
    isLoading.value = true
    successMessage.value = null
    errorMessage.value = null
    errors.value = {}

    const response = await $api(`/users/${userData.value.id}`, {
      method: 'PUT',
      body: form.value,
      onResponseError({ response }) {
        if (response._data?.errors) {
          errors.value = response._data.errors
        } else {
          errorMessage.value = response._data?.message || 'Une erreur est survenue lors de la mise à jour.'
        }
      },
    })

    // Si la réponse contient les données utilisateur mises à jour
    if (response) {
      // Mettre à jour le cookie avec les nouvelles données
      userData.value = { ...userData.value, ...response }
      successMessage.value = 'Vos informations ont été mises à jour avec succès.'
    } else {
      // Si pas de données dans la réponse, recharger depuis l'API
      await loadUserData()
      successMessage.value = 'Vos informations ont été mises à jour avec succès.'
    }
  } catch (err: any) {
    console.error('Erreur lors de la mise à jour:', err)
    if (!errors.value || Object.keys(errors.value).length === 0) {
      errorMessage.value = err.data?.message || err.response?._data?.message || 'Une erreur est survenue lors de la mise à jour.'
    }
  } finally {
    isLoading.value = false
  }
}

const resetForm = async () => {
  // Recharger les données fraîches depuis l'API
  await loadUserData()
  errors.value = {}
  refVForm.value?.resetValidation()
}

const onSubmit = () => {
  refVForm.value?.validate()
    .then(({ valid: isValid }) => {
      if (isValid)
        saveAccount()
    })
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <!-- Loading state -->
        <VCardText v-if="isInitialLoading">
          <div class="d-flex justify-center align-center py-8">
            <VProgressCircular
              indeterminate
              color="primary"
            />
            <span class="ms-4">Chargement des données...</span>
          </div>
        </VCardText>

        <!-- Form content -->
        <template v-else>
          <VCardText class="d-flex">
          <!-- 👉 Avatar -->
          <VAvatar
            rounded
            size="100"
            class="me-6"
            :color="!userData?.avatar ? 'primary' : undefined"
            :variant="!userData?.avatar ? 'tonal' : undefined"
          >
            <VImg
              v-if="userData?.avatar"
              :src="userData.avatar"
            />
            <VIcon
              v-else
              icon="tabler-user"
              size="40"
            />
          </VAvatar>

          <!-- 👉 Upload Photo -->
          <form class="d-flex flex-column justify-center gap-4">
            <div class="d-flex flex-wrap gap-4">
              <VBtn
                color="primary"
                size="small"
                disabled
              >
                <VIcon
                  icon="tabler-cloud-upload"
                  class="d-sm-none"
                />
                <span class="d-none d-sm-block">Changer la photo</span>
              </VBtn>
              <p class="text-body-2 text-disabled mb-0">
                La modification de la photo de profil sera disponible prochainement
              </p>
            </div>
          </form>
        </VCardText>

        <VCardText class="pt-2">
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

          <!-- 👉 Form -->
          <VForm
            ref="refVForm"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <!-- 👉 Nom -->
              <VCol
                md="6"
                cols="12"
              >
                <AppTextField
                  v-model="form.nom"
                  placeholder="Nom"
                  label="Nom"
                  :rules="[requiredValidator]"
                  :error-messages="errors.nom"
                />
              </VCol>

              <!-- 👉 Prénom -->
              <VCol
                md="6"
                cols="12"
              >
                <AppTextField
                  v-model="form.prenom"
                  placeholder="Prénom"
                  label="Prénom"
                  :rules="[requiredValidator]"
                  :error-messages="errors.prenom"
                />
              </VCol>

              <!-- 👉 Email -->
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="form.email"
                  label="E-mail"
                  placeholder="email@example.com"
                  type="email"
                  :rules="[requiredValidator, emailValidator]"
                  :error-messages="errors.email"
                />
              </VCol>

              <!-- 👉 Téléphone -->
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="form.telephone"
                  label="Téléphone"
                  placeholder="+221 77 123 45 67"
                  :error-messages="errors.telephone"
                />
              </VCol>

              <!-- 👉 Fonction -->
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="form.fonction"
                  label="Fonction"
                  placeholder="Votre fonction"
                  :error-messages="errors.fonction"
                />
              </VCol>

              <!-- 👉 Unité/Service -->
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="form.unite_service"
                  label="Unité/Service"
                  placeholder="Votre unité ou service"
                  :error-messages="errors.unite_service"
                />
              </VCol>

              <!-- 👉 Région -->
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="form.region"
                  label="Région"
                  placeholder="Votre région"
                  :error-messages="errors.region"
                />
              </VCol>

              <!-- 👉 Form Actions -->
              <VCol
                cols="12"
                class="d-flex flex-wrap gap-4"
              >
                <VBtn
                  type="submit"
                  :loading="isLoading"
                >
                  Enregistrer les modifications
                </VBtn>

                <VBtn
                  color="secondary"
                  variant="tonal"
                  type="reset"
                  @click.prevent="resetForm"
                >
                  Annuler
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
          </VCardText>
        </template>
      </VCard>
    </VCol>
  </VRow>
</template>
