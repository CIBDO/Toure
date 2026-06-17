<script setup lang="ts">
import type { Reception, ReceptionItem } from '@/stores/receptions'

const props = withDefaults(
  defineProps<{
    modelValue: Partial<Reception> & { reception_items?: ReceptionItem[] }
    contrat?: { id: number; reference?: string; date_previsionnelle_reception?: string }
    hasProvisoireApproved?: boolean
    isEditing?: boolean
  }>(),
  { isEditing: false, hasProvisoireApproved: false },
)

const emit = defineEmits<{
  submit: [payload: Record<string, any>]
  cancel: []
}>()

const typeOptions = [
  { title: 'Provisoire', value: 'provisoire' },
  { title: 'Partielle', value: 'partielle' },
  { title: 'Définitive', value: 'definitive' },
]

const conformiteOptions = [
  { title: 'Conforme', value: 'conforme' },
  { title: 'Non conforme', value: 'non_conforme' },
  { title: 'Conforme avec réserves', value: 'conforme_avec_reserves' },
]

const form = ref({
  type_reception: (props.modelValue?.type_reception as string) ?? 'provisoire',
  date_reception: props.modelValue?.date_reception ?? new Date().toISOString().slice(0, 10),
  lieu_reception: props.modelValue?.lieu_reception ?? '',
  responsable_reception: props.modelValue?.responsable_reception ?? '',
  constatations: props.modelValue?.constatations ?? '',
  reserves: props.modelValue?.reserves ?? '',
  statut_conformite: (props.modelValue?.statut_conformite as string) ?? 'conforme',
  quantite_receptionnee: props.modelValue?.quantite_receptionnee ?? null as number | null,
  reception_items: (props.modelValue?.reception_items ?? []) as ReceptionItem[],
})

watch(
  () => props.modelValue,
  (v) => {
    if (v) {
      form.value.type_reception = (v.type_reception as string) ?? 'provisoire'
      form.value.date_reception = v.date_reception ?? new Date().toISOString().slice(0, 10)
      form.value.lieu_reception = v.lieu_reception ?? ''
      form.value.responsable_reception = v.responsable_reception ?? ''
      form.value.constatations = v.constatations ?? ''
      form.value.reserves = v.reserves ?? ''
      form.value.statut_conformite = (v.statut_conformite as string) ?? 'conforme'
      form.value.quantite_receptionnee = v.quantite_receptionnee ?? null
      form.value.reception_items = (v.reception_items ?? []).map((i: ReceptionItem) => ({
        ...i,
        quantite_prevue: i.quantite_prevue ?? null,
        quantite_recue: i.quantite_recue ?? null,
        conforme: i.conforme ?? true,
      }))
    }
  },
  { immediate: true },
)

const showDefinitiveAlert = computed(() =>
  form.value.type_reception === 'definitive' && !props.hasProvisoireApproved,
)

const tauxExecution = computed(() => {
  const items = form.value.reception_items.filter(
    (i) => i.quantite_prevue != null && Number(i.quantite_prevue) > 0,
  )
  if (!items.length) return null
  const totalPrevue = items.reduce((s, i) => s + Number(i.quantite_prevue || 0), 0)
  const totalRecue = items.reduce((s, i) => s + Number(i.quantite_recue || 0), 0)
  if (totalPrevue <= 0) return null
  return Math.round((totalRecue / totalPrevue) * 10000) / 100
})

const addItem = () => {
  form.value.reception_items.push({
    label: '',
    quantite_prevue: null,
    quantite_recue: null,
    conforme: true,
    observation: '',
  })
}

const removeItem = (index: number) => {
  form.value.reception_items.splice(index, 1)
}

const onSubmit = () => {
  const payload: Record<string, any> = {
    type_reception: form.value.type_reception,
    date_reception: form.value.date_reception,
    lieu_reception: form.value.lieu_reception || undefined,
    responsable_reception: form.value.responsable_reception || undefined,
    constatations: form.value.constatations || undefined,
    reserves: form.value.reserves || undefined,
    statut_conformite: form.value.statut_conformite,
    quantite_receptionnee: form.value.quantite_receptionnee ?? undefined,
  }
  if (form.value.reception_items.length) {
    payload.reception_items = form.value.reception_items.map((i) => ({
      label: i.label || undefined,
      quantite_prevue: i.quantite_prevue ?? undefined,
      quantite_recue: i.quantite_recue ?? undefined,
      conforme: i.conforme ?? true,
      observation: i.observation || undefined,
    }))
  }
  emit('submit', payload)
}

const formatDate = (d?: string) => (d ? new Date(d).toLocaleDateString('fr-FR') : '-')

const onCancel = () => emit('cancel')
</script>

<template>
  <form @submit.prevent="onSubmit">
    <VRow>
      <VCol cols="12" md="6">
        <VSelect
          v-model="form.type_reception"
          :items="typeOptions"
          item-title="title"
          item-value="value"
          label="Type de réception *"
        />
      </VCol>
      <VCol cols="12" md="6">
        <VTextField
          v-model="form.date_reception"
          label="Date de réception *"
          type="date"
          required
        />
      </VCol>

      <VCol v-if="contrat?.date_previsionnelle_reception" cols="12" md="6">
        <VTextField
          :model-value="formatDate(contrat.date_previsionnelle_reception)"
          label="Date prévisionnelle de réception (contrat)"
          readonly
          prepend-inner-icon="tabler-calendar-event"
        />
      </VCol>

      <VCol v-if="showDefinitiveAlert" cols="12">
        <VAlert type="warning" variant="tonal" density="compact" class="mb-2">
          Une réception définitive ne pourra être approuvée qu'après au moins une réception provisoire approuvée (ou avec permission dérogatoire).
        </VAlert>
      </VCol>

      <VCol cols="12" md="6">
        <VTextField v-model="form.lieu_reception" label="Lieu de réception" />
      </VCol>
      <VCol cols="12" md="6">
        <VTextField v-model="form.responsable_reception" label="Responsable réception" />
      </VCol>
      <VCol cols="12" md="6">
        <VSelect
          v-model="form.statut_conformite"
          :items="conformiteOptions"
          item-title="title"
          item-value="value"
          label="Conformité"
        />
      </VCol>
      <VCol cols="12" md="6">
        <VTextField
          v-model.number="form.quantite_receptionnee"
          label="Quantité réceptionnée"
          type="number"
          min="0"
          step="0.01"
        />
      </VCol>
      <VCol cols="12">
        <VTextarea v-model="form.constatations" label="Constatations" rows="3" />
      </VCol>
      <VCol cols="12">
        <VTextarea v-model="form.reserves" label="Réserves / non-conformités" rows="2" />
      </VCol>

      <!-- Mode détaillé : lignes -->
      <VCol cols="12">
        <div class="d-flex align-center justify-space-between mb-2">
          <span class="text-subtitle-2">Lignes / lots (optionnel)</span>
          <VBtn size="small" variant="tonal" prepend-icon="tabler-plus" @click="addItem">
            Ajouter une ligne
          </VBtn>
        </div>
        <VTable v-if="form.reception_items.length" density="compact" class="elevation-1 rounded">
          <thead>
            <tr>
              <th>Libellé</th>
              <th style="width: 120px">Qté prévue</th>
              <th style="width: 120px">Qté reçue</th>
              <th style="width: 100px">Conforme</th>
              <th>Observation</th>
              <th style="width: 48px" />
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, idx) in form.reception_items" :key="idx">
              <td>
                <VTextField v-model="item.label" density="compact" hide-details placeholder="Libellé" />
              </td>
              <td>
                <VTextField v-model.number="item.quantite_prevue" density="compact" hide-details type="number" min="0" />
              </td>
              <td>
                <VTextField v-model.number="item.quantite_recue" density="compact" hide-details type="number" min="0" />
              </td>
              <td>
                <VCheckbox v-model="item.conforme" density="compact" hide-details />
              </td>
              <td>
                <VTextField v-model="item.observation" density="compact" hide-details placeholder="Observation" />
              </td>
              <td>
                <VBtn icon variant="text" size="x-small" color="error" @click="removeItem(idx)">
                  <VIcon icon="tabler-trash" size="18" />
                </VBtn>
              </td>
            </tr>
          </tbody>
        </VTable>
        <VCard v-if="form.reception_items.length && tauxExecution != null" variant="tonal" color="primary" class="mt-2 pa-2">
          <span class="text-caption text-medium-emphasis">Taux d'exécution (calculé) : </span>
          <strong>{{ tauxExecution }} %</strong>
        </VCard>
      </VCol>
    </VRow>

    <VDivider class="my-4" />
    <div class="d-flex justify-end gap-2">
      <VBtn variant="tonal" @click="onCancel">
        Annuler
      </VBtn>
      <VBtn type="submit" color="primary">
        {{ isEditing ? 'Enregistrer' : 'Créer la réception' }}
      </VBtn>
    </div>
  </form>
</template>
