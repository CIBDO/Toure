<script setup lang="ts">
import { useOrdreServicesStore } from '@/stores/ordreServices'
import DocumentsPanel from '@/components/DocumentsPanel.vue'

definePage({ meta: { title: 'Détail Ordre de service' } })

const route = useRoute()
const router = useRouter()
const store = useOrdreServicesStore()
const snackbar = ref({ show: false, text: '', color: 'success' })

const osId = computed(() => Number(route.params.id))
const os = computed(() => store.currentOS)

const typeLabels: Record<string, string> = {
  demarrage: 'Démarrage',
  suspension: 'Suspension',
  reprise: 'Reprise',
  arret: 'Arrêt',
  modification: 'Modification',
  autre: 'Autre',
}

const statutColor = (s: string) =>
  ({ draft: 'default', submitted: 'info', approved: 'success', rejected: 'error', executed: 'success', archived: 'secondary' }[s] || 'default')

const statutLabel = (s: string) =>
  ({ draft: 'Brouillon', submitted: 'Soumis', approved: 'Approuvé', rejected: 'Rejeté', executed: 'Exécuté', archived: 'Archivé' }[s] || s)

const formatDate = (d: string | undefined) => (d ? new Date(d).toLocaleDateString('fr-FR') : '-')

const impactDisplay = computed(() => {
  const o = os.value
  if (!o || o.impact_delai === 'none' || o.delai_jours == null) return null
  const sign = o.impact_delai === 'extend' ? '+' : '-'
  return `${sign}${o.delai_jours} jour(s)`
})

onMounted(async () => {
  await store.fetchOrdreService(osId.value)
})
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VBtn variant="tonal" class="mb-4" @click="router.push(os?.contrat_id ? `/apps/contrats/${os.contrat_id}` : '/apps/contrats/ordre-services')">
        <VIcon icon="tabler-arrow-left" start />
        {{ os?.contrat_id ? 'Retour au contrat' : 'Liste des OS' }}
      </VBtn>

      <VCard v-if="os">
        <VCardTitle class="d-flex align-center flex-wrap gap-2 pa-4">
          <VIcon icon="tabler-clipboard-text" class="me-2" />
          {{ os.numero }}
          <VChip :color="statutColor(os.statut)" size="small">
            {{ statutLabel(os.statut) }}
          </VChip>
          <VSpacer />
          <VBtn
            v-if="os.contrat_id"
            variant="tonal"
            size="small"
            @click="router.push(`/apps/contrats/${os.contrat_id}`)"
          >
            Voir le contrat
          </VBtn>
        </VCardTitle>

        <VDivider />

        <VCardText>
          <VRow>
            <VCol cols="12" md="6">
              <p class="text-caption text-medium-emphasis mb-1">Type</p>
              <p class="font-weight-medium">{{ typeLabels[os.type_os] ?? os.type_os }}</p>
            </VCol>
            <VCol cols="12" md="6">
              <p class="text-caption text-medium-emphasis mb-1">Date d'émission</p>
              <p class="font-weight-medium">{{ formatDate(os.date_emission) }}</p>
            </VCol>
            <VCol cols="12">
              <p class="text-caption text-medium-emphasis mb-1">Objet</p>
              <p class="font-weight-medium">{{ os.objet }}</p>
            </VCol>
            <VCol v-if="os.description" cols="12">
              <p class="text-caption text-medium-emphasis mb-1">Description / Motif</p>
              <p class="text-body-2">{{ os.description }}</p>
            </VCol>
            <VCol v-if="os.date_effet" cols="12" md="6">
              <p class="text-caption text-medium-emphasis mb-1">Date d'effet</p>
              <p class="font-weight-medium">{{ formatDate(os.date_effet) }}</p>
            </VCol>
            <VCol v-if="impactDisplay" cols="12" md="6">
              <p class="text-caption text-medium-emphasis mb-1">Impact sur le délai</p>
              <p class="font-weight-medium">{{ impactDisplay }}</p>
            </VCol>
            <VCol v-if="os.commentaire_validation" cols="12">
              <p class="text-caption text-medium-emphasis mb-1">Commentaire de validation</p>
              <p class="text-body-2">{{ os.commentaire_validation }}</p>
            </VCol>
            <VCol cols="12" md="4">
              <p class="text-caption text-medium-emphasis mb-1">Émis par</p>
              <p class="text-body-2">{{ os.issued_by_user?.name ?? os.issued_by_user?.nom ?? '-' }}</p>
            </VCol>
            <VCol v-if="os.approved_at" cols="12" md="4">
              <p class="text-caption text-medium-emphasis mb-1">Approuvé le</p>
              <p class="text-body-2">{{ formatDate(os.approved_at) }}</p>
            </VCol>
            <VCol v-if="os.executed_at" cols="12" md="4">
              <p class="text-caption text-medium-emphasis mb-1">Exécuté le</p>
              <p class="text-body-2">{{ formatDate(os.executed_at) }}</p>
            </VCol>
          </VRow>
        </VCardText>
      </VCard>

      <!-- Documents GED -->
      <VCard v-if="os" class="mt-4">
        <VCardItem class="d-flex align-center pa-4">
          <VCardTitle class="text-subtitle-1">
            <VIcon icon="tabler-folder" start />
            Documents (OS signé, pièces justificatives)
          </VCardTitle>
        </VCardItem>
        <VCardText>
          <DocumentsPanel
            documentable-type="ordre_services"
            :documentable-id="os.id"
            entity-label="OS"
          />
        </VCardText>
      </VCard>

      <VSkeletonLoader v-else type="card" class="mt-4" />
    </VCol>
  </VRow>
</template>
