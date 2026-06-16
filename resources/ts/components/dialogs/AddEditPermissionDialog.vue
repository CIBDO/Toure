<script setup lang="ts">
import type { VForm } from 'vuetify/components/VForm'

interface PermissionIAM {
  id?: number
  code: string
  libelle: string
}

interface Props {
  isDialogVisible: boolean
  permission?: PermissionIAM | null
}

interface Emit {
  (e: 'update:isDialogVisible', value: boolean): void
  (e: 'submit', value: { code: string; libelle: string }): void
}

const props = withDefaults(defineProps<Props>(), {
  permission: null,
})

const emit = defineEmits<Emit>()

const isFormValid = ref(false)
const refForm = ref<VForm>()
const code = ref('')
const libelle = ref('')

// Réinitialiser les valeurs quand le dialog s'ouvre
watch(() => props.isDialogVisible, (isVisible) => {
  if (isVisible) {
    if (props.permission) {
      // Mode édition
      code.value = props.permission.code || ''
      libelle.value = props.permission.libelle || ''
    } else {
      // Mode ajout
      code.value = ''
      libelle.value = ''
    }
  }
}, { immediate: true })

const onReset = () => {
  emit('update:isDialogVisible', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
    code.value = ''
    libelle.value = ''
  })
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      emit('submit', {
        code: code.value.toUpperCase(),
        libelle: libelle.value,
      })
      emit('update:isDialogVisible', false)
      nextTick(() => {
        refForm.value?.reset()
        refForm.value?.resetValidation()
        code.value = ''
        libelle.value = ''
      })
    }
  })
}
</script>

<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 600"
    :model-value="props.isDialogVisible"
    @update:model-value="onReset"
  >
    <!-- 👉 dialog close btn -->
    <DialogCloseBtn @click="onReset" />

    <VCard class="pa-2 pa-sm-10">
      <VCardText>
        <!-- 👉 Title -->
        <h4 class="text-h4 text-center mb-2">
          {{ props.permission ? 'Modifier' : 'Ajouter' }} une permission
        </h4>
        <p class="text-body-1 text-center mb-6">
          {{ props.permission ? 'Modifier' : 'Ajouter' }} une permission selon vos besoins.
        </p>

        <!-- 👉 Form -->
        <VForm
          ref="refForm"
          v-model="isFormValid"
          @submit.prevent="onSubmit"
        >
          <VRow>
            <!-- 👉 Code -->
            <VCol cols="12">
              <AppTextField
                v-model="code"
                :rules="[requiredValidator]"
                label="Code"
                placeholder="PERMISSION_CODE"
                hint="Le code sera automatiquement converti en majuscules"
                persistent-hint
              />
            </VCol>

            <!-- 👉 Libellé -->
            <VCol cols="12">
              <AppTextField
                v-model="libelle"
                :rules="[requiredValidator]"
                label="Libellé"
                placeholder="Description de la permission"
              />
            </VCol>

            <!-- 👉 Actions -->
            <VCol cols="12">
              <div class="d-flex justify-end gap-4">
                <VBtn
                  variant="tonal"
                  color="secondary"
                  @click="onReset"
                >
                  Annuler
                </VBtn>
                <VBtn @click="onSubmit">
                  {{ props.permission ? 'Modifier' : 'Ajouter' }}
                </VBtn>
              </div>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>
