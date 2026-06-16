<script setup lang="ts">
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

import type { VForm } from 'vuetify/components/VForm'

interface Emit {
  (e: 'update:isDrawerOpen', value: boolean): void
  (e: 'userData', value: any): void
}

interface Props {
  isDrawerOpen: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()

const isFormValid = ref(false)
const refForm = ref<VForm>()
const nom = ref('')
const prenom = ref('')
const email = ref('')
const telephone = ref('')
const fonction = ref('')
const uniteService = ref('')
const region = ref('')
const password = ref('')
const typeCompte = ref()
const statut = ref()
const selectedRoles = ref([])

// Charger les rôles depuis l'API
const { data: rolesData } = await useApi<any>('/roles?itemsPerPage=-1')
const rolesOptions = computed(() => {
  if (!rolesData.value?.roles) return []
  return rolesData.value.roles.map((role: any) => ({
    title: role.libelle,
    value: role.id,
  }))
})

// 👉 drawer close
const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)

  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
  })
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      // Préparer les données au format IAM
      const userData: any = {
        nom: nom.value,
        prenom: prenom.value,
        email: email.value,
        telephone: telephone.value || null,
        password: password.value || undefined,
        statut: statut.value || 'ACTIF',
        type_compte: typeCompte.value || 'CANAM',
      }

      // Ajouter les rôles si sélectionnés
      if (selectedRoles.value && selectedRoles.value.length > 0) {
        userData.roles = selectedRoles.value
      }

      emit('userData', userData)
      emit('update:isDrawerOpen', false)
      nextTick(() => {
        refForm.value?.reset()
        refForm.value?.resetValidation()
      })
    }
  })
}

const handleDrawerModelValueUpdate = (val: boolean) => {
  emit('update:isDrawerOpen', val)
}
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="400"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- 👉 Title -->
    <AppDrawerHeaderSection
      title="Ajouter un utilisateur"
      @cancel="closeNavigationDrawer"
    />

    <VDivider />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <!-- 👉 Form -->
          <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <!-- 👉 Nom -->
              <VCol cols="12">
                <AppTextField
                  v-model="nom"
                  :rules="[requiredValidator]"
                  label="Nom"
                  placeholder="DIARRA"
                />
              </VCol>

              <!-- 👉 Prénom -->
              <VCol cols="12">
                <AppTextField
                  v-model="prenom"
                  :rules="[requiredValidator]"
                  label="Prénom"
                  placeholder="MAMADOU"
                />
              </VCol>

              <!-- 👉 Email -->
              <VCol cols="12">
                <AppTextField
                  v-model="email"
                  :rules="[requiredValidator, emailValidator]"
                  label="Email"
                  placeholder="mamadou.diarra@example.com"
                />
              </VCol>

              <!-- 👉 Téléphone -->
              <VCol cols="12">
                <AppTextField
                  v-model="telephone"
                  label="Téléphone"
                  placeholder="+223 76767676"
                />
              </VCol>

              <!-- 👉 Fonction -->
              <VCol cols="12">
                <AppTextField
                  v-model="fonction"
                  label="Fonction"
                  placeholder="Agent, Superviseur, Directeur..."
                />
              </VCol>

              <!-- 👉 Unité/Service -->
              <VCol cols="12">
                <AppTextField
                  v-model="uniteService"
                  label="Unité/Service"
                  placeholder="Service de supervision..."
                />
              </VCol>

              <!-- 👉 Région -->
              <VCol cols="12">
                <AppTextField
                  v-model="region"
                  label="Région"
                  placeholder="Bamako, Sikasso, Kayes..."
                />
              </VCol>

              <!-- 👉 Mot de passe -->
              <VCol cols="12">
                <AppTextField
                  v-model="password"
                  type="password"
                  label="Mot de passe"
                  placeholder="Laissez vide pour mot de passe par défaut"
                  hint="Laissez vide pour utiliser le mot de passe par défaut (Canam@2026)"
                  persistent-hint
                />
              </VCol>

              <!-- 👉 Type de compte -->
              <VCol cols="12">
                <AppSelect
                  v-model="typeCompte"
                  label="Type de compte"
                  placeholder="Sélectionner type de compte"
                  :rules="[requiredValidator]"
                  :items="[
                    { title: 'CANAM', value: 'CANAM' },
                    { title: 'Contrat CANAM', value: 'CONTRAT' },
                    { title: 'Système', value: 'SYSTEME' },
                  ]"
                />
              </VCol>

              <!-- 👉 Statut -->
              <VCol cols="12">
                <AppSelect
                  v-model="statut"
                  label="Statut"
                  placeholder="Sélectionner statut"
                  :rules="[requiredValidator]"
                  :items="[
                    { title: 'Actif', value: 'ACTIF' },
                    { title: 'Suspendu', value: 'SUSPENDU' },
                    { title: 'Désactivé', value: 'DESACTIVE' },
                    { title: 'En attente d\'activation', value: 'EN_ATTENTE_ACTIVATION' },
                  ]"
                />
              </VCol>

              <!-- 👉 Rôles -->
              <VCol cols="12">
                <AppSelect
                  v-model="selectedRoles"
                  label="Rôles"
                  placeholder="Sélectionner les rôles"
                  :items="rolesOptions"
                  multiple
                  chips
                  closable-chips
                />
              </VCol>

              <!-- 👉 Submit and Cancel -->
              <VCol cols="12">
                <VBtn
                  type="submit"
                  class="me-3"
                >
                  Créer
                </VBtn>
                <VBtn
                  type="reset"
                  variant="tonal"
                  color="error"
                  @click="closeNavigationDrawer"
                >
                  Annuler
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>
