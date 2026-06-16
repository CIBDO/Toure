<script setup lang="ts">
import type { OrdreService, TypeOS, ImpactDelai } from '@/stores/ordreServices'

const props = withDefaults(
  defineProps<{
    modelValue: Partial<OrdreService>
    contrat?: { date_fin?: string }
    isEditing?: boolean
  }>(),
  { isEditing: false },
)

const emit = defineEmits<{
  submit: [payload: Record<string, any>]
  cancel: []
}>()

const typeOptions = [
  { title: 'Démarrage', value: 'demarrage' },
  { title: 'Suspension', value: 'suspension' },
  { title: 'Reprise', value: 'reprise' },
  { title: 'Arrêt', value: 'arret' },
  { title: 'Modification (instructions complémentaires)', value: 'modification' },
  { title: 'Autre', value: 'autre' },
]

const impactOptions = [
  { title: 'Aucun', value: 'none' },
  { title: 'Prolonger', value: 'extend' },
  { title: 'Réduire', value: 'reduce' },
]

const form = ref({
  type_os: (props.modelValue?.type_os ?? 'demarrage') as TypeOS,
  objet: props.modelValue?.objet ?? '',
  description: props.modelValue?.description ?? '',
  date_emission: props.modelValue?.date_emission ?? new Date().toISOString().slice(0, 10),
  date_effet: props.modelValue?.date_effet ?? '',
  impact_delai: (props.modelValue?.impact_delai ?? 'none') as ImpactDelai,
  delai_jours: props.modelValue?.delai_jours ?? null as number | null,
})

watch(
  () => props.modelValue,
  (v) => {
    if (v) {
      form.value.type_os = (v.type_os ?? 'demarrage') as TypeOS
      form.value.objet = v.objet ?? ''
      form.value.description = v.description ?? ''
      form.value.date_emission = v.date_emission ?? new Date().toISOString().slice(0, 10)
      form.value.date_effet = v.date_effet ?? ''
      form.value.impact_delai = (v.impact_delai ?? 'none') as ImpactDelai
      form.value.delai_jours = v.delai_jours ?? null
    }
  },
  { immediate: true },
)

const dateFinRef = computed(() => props.contrat?.date_fin ?? null)
const showDelaiJours = computed(() =>
  form.value.impact_delai === 'extend' || form.value.impact_delai === 'reduce',
)

const nouvelleDateFin = computed(() => {
  if (!dateFinRef.value || form.value.impact_delai === 'none' || !form.value.delai_jours) return null
  const d = new Date(dateFinRef.value)
  if (form.value.impact_delai === 'extend') d.setDate(d.getDate() + form.value.delai_jours!)
  else d.setDate(d.getDate() - Math.abs(form.value.delai_jours!))
  return d.toISOString().slice(0, 10)
})

const canSubmit = computed(() => {
  if (!form.value.objet?.trim()) return false
  if (form.value.type_os === 'suspension') {
    if (!form.value.description?.trim()) return false
    if (!form.value.date_effet) return false
  }
  if (form.value.impact_delai === 'extend' || form.value.impact_delai === 'reduce') {
    const n = form.value.delai_jours
    return typeof n === 'number' && n >= 1
  }
  return true
})

const formatDate = (d: string) => (d ? new Date(d).toLocaleDateString('fr-FR') : '-')

const onSubmit = () => {
  const payload: Record<string, any> = {
    type_os: form.value.type_os,
    objet: form.value.objet,
    description: form.value.description || undefined,
    date_emission: form.value.date_emission,
    date_effet: form.value.date_effet || undefined,
    impact_delai: form.value.impact_delai,
  }
  if (form.value.impact_delai === 'extend' || form.value.impact_delai === 'reduce') {
    const jours = Number(form.value.delai_jours)
    if (jours >= 1) payload.delai_jours = Math.floor(jours)
  }
  emit('submit', payload)
}

const onCancel = () => emit('cancel')
</script>

<template>
  <form @submit.prevent="onSubmit">
    <VRow>
      <VCol cols="12">
        <VSelect
          v-model="form.type_os"
          :items="typeOptions"
          item-title="title"
          item-value="value"
          label="Type d'OS *"
        />
      </VCol>
      <VCol cols="12">
        <VTextField
          v-model="form.objet"
          label="Objet *"
          required
        />
      </VCol>
      <VCol cols="12">
        <VTextarea
          v-model="form.description"
          label="Description / Motif"
          rows="3"
          :hint="form.type_os === 'suspension' ? 'Requis pour une suspension' : ''"
          persistent-hint
        />
      </VCol>
      <VCol cols="12" md="6">
        <VTextField
          v-model="form.date_emission"
          label="Date d'émission *"
          type="date"
          required
        />
      </VCol>
      <VCol cols="12" md="6">
        <VTextField
          v-model="form.date_effet"
          label="Date d'effet"
          type="date"
          :hint="form.type_os === 'suspension' ? 'Requis pour une suspension' : ''"
          persistent-hint
        />
      </VCol>
      <VCol cols="12" md="6">
        <VSelect
          v-model="form.impact_delai"
          :items="impactOptions"
          item-title="title"
          item-value="value"
          label="Impact sur le délai"
        />
      </VCol>
      <VCol v-if="showDelaiJours" cols="12" md="6">
        <VTextField
          v-model.number="form.delai_jours"
          label="Délai (jours) *"
          type="number"
          min="1"
        />
      </VCol>
      <VCol v-if="dateFinRef && showDelaiJours && nouvelleDateFin" cols="12">
        <VCard variant="outlined" class="pa-3">
          <p class="text-caption text-medium-emphasis mb-1">
            Ancienne date de fin contrat : {{ formatDate(dateFinRef) }}
          </p>
          <p class="text-body-1 font-weight-bold mb-0">
            Nouvelle date de fin (simulation) : {{ formatDate(nouvelleDateFin) }}
          </p>
        </VCard>
      </VCol>
    </VRow>

    <VDivider class="my-4" />
    <div class="d-flex justify-end gap-2">
      <VBtn variant="tonal" @click="onCancel">
        Annuler
      </VBtn>
      <VBtn type="submit" color="primary" :disabled="!canSubmit">
        {{ isEditing ? 'Enregistrer' : 'Créer l\'OS' }}
      </VBtn>
    </div>
  </form>
</template>
