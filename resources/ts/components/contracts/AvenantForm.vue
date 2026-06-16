<script setup lang="ts">
import type { Avenant } from '@/stores/avenants'

const props = withDefaults(
  defineProps<{
    modelValue: Partial<Avenant> & { type_avenant?: string }
    contrat?: { montant_actuel?: number; montant_initial?: number; date_fin?: string; objet?: string }
    isEditing?: boolean
  }>(),
  { isEditing: false },
)

const emit = defineEmits<{
  submit: [payload: Record<string, any>]
  cancel: []
}>()

const typeOptions = [
  { title: 'Modification du montant', value: 'montant' },
  { title: 'Modification du délai', value: 'delai' },
  { title: "Modification de l'objet", value: 'objet' },
  { title: 'Mixte (montant + délai et/ou objet)', value: 'mixte' },
]

const form = ref({
  type_avenant: props.modelValue?.type_avenant ?? 'montant',
  montant_variation: props.modelValue?.montant_variation ?? 0,
  prolongation_jours: props.modelValue?.prolongation_jours ?? 0,
  nouvelle_description_objet: props.modelValue?.nouvelle_description_objet ?? props.contrat?.objet ?? '',
  justification: props.modelValue?.justification ?? '',
  date_signature: props.modelValue?.date_signature ?? new Date().toISOString().slice(0, 10),
})

watch(
  () => props.modelValue,
  (v) => {
    if (v) {
      form.value.type_avenant = v.type_avenant ?? 'montant'
      form.value.montant_variation = v.montant_variation ?? 0
      form.value.prolongation_jours = v.prolongation_jours ?? 0
      form.value.nouvelle_description_objet = v.nouvelle_description_objet ?? props.contrat?.objet ?? ''
      form.value.justification = v.justification ?? ''
      form.value.date_signature = v.date_signature ?? new Date().toISOString().slice(0, 10)
    }
  },
  { immediate: true },
)

const montantRef = computed(() => {
  const c = props.contrat
  return Number(c?.montant_actuel ?? c?.montant_initial ?? 0)
})

const dateFinRef = computed(() => props.contrat?.date_fin ?? null)

const showMontant = computed(() =>
  ['montant', 'mixte'].includes(form.value.type_avenant),
)
const showDelai = computed(() =>
  ['delai', 'mixte'].includes(form.value.type_avenant),
)
const showObjet = computed(() =>
  ['objet', 'mixte'].includes(form.value.type_avenant),
)

const nouveauMontant = computed(() => {
  if (!showMontant.value) return montantRef.value
  return montantRef.value + Number(form.value.montant_variation || 0)
})

const nouvelleDateFin = computed(() => {
  if (!dateFinRef.value || !showDelai.value) return null
  const d = new Date(dateFinRef.value)
  d.setDate(d.getDate() + Number(form.value.prolongation_jours || 0))
  return d.toISOString().slice(0, 10)
})

const formatMontant = (v: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v)

const formatDate = (d: string) => (d ? new Date(d).toLocaleDateString('fr-FR') : '-')

const onSubmit = () => {
  const payload: Record<string, any> = {
    type_avenant: form.value.type_avenant,
    justification: form.value.justification,
    date_signature: form.value.date_signature,
  }
  if (showMontant.value) payload.montant_variation = Number(form.value.montant_variation)
  if (showDelai.value) payload.prolongation_jours = Number(form.value.prolongation_jours)
  if (showObjet.value) payload.nouvelle_description_objet = form.value.nouvelle_description_objet
  emit('submit', payload)
}

const onCancel = () => emit('cancel')
</script>

<template>
  <form @submit.prevent="onSubmit">
    <VRow>
      <VCol cols="12">
        <VSelect
          v-model="form.type_avenant"
          :items="typeOptions"
          item-title="title"
          item-value="value"
          label="Type d'avenant *"
        />
      </VCol>

      <!-- Référence contrat (lecture) -->
      <VCol cols="12" md="6">
        <VCard variant="tonal" color="primary" class="pa-3">
          <p class="text-caption text-medium-emphasis mb-1">
            Ancien montant contrat
          </p>
          <p class="text-h6 font-weight-bold mb-0">
            {{ formatMontant(montantRef) }}
          </p>
        </VCard>
      </VCol>
      <VCol v-if="dateFinRef" cols="12" md="6">
        <VCard variant="tonal" color="secondary" class="pa-3">
          <p class="text-caption text-medium-emphasis mb-1">
            Ancienne date de fin
          </p>
          <p class="text-body-1 font-weight-medium mb-0">
            {{ formatDate(dateFinRef) }}
          </p>
        </VCard>
      </VCol>

      <VCol v-if="showMontant" cols="12" md="6">
        <VTextField
          v-model.number="form.montant_variation"
          label="Variation de montant (XOF)"
          type="number"
          hint="Positif = augmentation, négatif = diminution"
          persistent-hint
        />
      </VCol>
      <VCol v-if="showMontant" cols="12" md="6">
        <VCard variant="outlined" class="pa-3">
          <p class="text-caption text-medium-emphasis mb-1">
            Nouveau montant (simulation)
          </p>
          <p class="text-h6 font-weight-bold" :class="nouveauMontant < 0 ? 'text-error' : 'text-success'">
            {{ formatMontant(Math.max(0, nouveauMontant)) }}
          </p>
        </VCard>
      </VCol>

      <VCol v-if="showDelai" cols="12" md="6">
        <VTextField
          v-model.number="form.prolongation_jours"
          label="Prolongation (jours)"
          type="number"
          min="0"
        />
      </VCol>
      <VCol v-if="showDelai && nouvelleDateFin" cols="12" md="6">
        <VCard variant="outlined" class="pa-3">
          <p class="text-caption text-medium-emphasis mb-1">
            Nouvelle date de fin (simulation)
          </p>
          <p class="text-body-1 font-weight-bold">
            {{ formatDate(nouvelleDateFin) }}
          </p>
        </VCard>
      </VCol>

      <VCol v-if="showObjet" cols="12">
        <VTextarea
          v-model="form.nouvelle_description_objet"
          label="Nouvelle description de l'objet *"
          rows="4"
        />
      </VCol>

      <VCol cols="12">
        <VTextarea
          v-model="form.justification"
          label="Justification *"
          rows="3"
          required
        />
      </VCol>
      <VCol cols="12" md="6">
        <VTextField
          v-model="form.date_signature"
          label="Date de signature *"
          type="date"
          required
        />
      </VCol>
    </VRow>

    <VDivider class="my-4" />
    <div class="d-flex justify-end gap-2">
      <VBtn variant="tonal" @click="onCancel">
        Annuler
      </VBtn>
      <VBtn type="submit" color="primary" :disabled="!form.justification?.trim()">
        {{ isEditing ? 'Enregistrer' : 'Créer l\'avenant' }}
      </VBtn>
    </div>
  </form>
</template>
