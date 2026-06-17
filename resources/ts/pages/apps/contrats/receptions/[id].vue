<script setup lang="ts">
import { useReceptionsStore } from '@/stores/receptions'
import DocumentsPanel from '@/components/DocumentsPanel.vue'

definePage({ meta: { title: 'Détail Réception' } })

const route = useRoute()
const router = useRouter()
const store = useReceptionsStore()
const snackbar = ref({ show: false, text: '', color: 'success' })

const receptionId = computed(() => Number(route.params.id))
const reception = computed(() => store.currentReception)

const typeLabels: Record<string, string> = {
  provisoire: 'Provisoire',
  partielle: 'Partielle',
  definitive: 'Définitive',
}

const conformiteLabels: Record<string, string> = {
  conforme: 'Conforme',
  non_conforme: 'Non conforme',
  conforme_avec_reserves: 'Conforme avec réserves',
}

const statutColor = (s: string) =>
  ({ draft: 'default', submitted: 'info', approved: 'success', rejected: 'error' }[s] || 'default')

const statutLabel = (s: string) =>
  ({ draft: 'Brouillon', submitted: 'Soumis', approved: 'Approuvé', rejected: 'Rejeté' }[s] || s)

const formatMontant = (v: number) =>
  v != null ? new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v) : '-'

const formatDate = (d: string) => (d ? new Date(d).toLocaleDateString('fr-FR') : '-')

onMounted(() => store.fetchReception(receptionId.value))
</script>

<template>
  <div v-if="reception">
    <VRow class="mb-4">
      <VCol cols="12">
        <VCard>
          <VCardText class="pa-4">
            <div class="d-flex align-center gap-3 flex-wrap">
              <VBtn icon variant="text" @click="router.back()">
                <VIcon icon="tabler-arrow-left" />
              </VBtn>
              <div class="flex-grow-1">
                <div class="d-flex align-center gap-2 flex-wrap">
                  <h1 class="text-h5 font-weight-bold">
                    Réception {{ reception.numero }}
                  </h1>
                  <VChip :color="statutColor(reception.statut)" size="small">
                    {{ statutLabel(reception.statut) }}
                  </VChip>
                  <VChip :color="reception.statut_conformite === 'conforme' ? 'success' : reception.statut_conformite === 'non_conforme' ? 'error' : 'warning'" size="small">
                    {{ conformiteLabels[reception.statut_conformite] }}
                  </VChip>
                  <span class="text-body-2 text-medium-emphasis">
                    {{ typeLabels[reception.type_reception] }}
                  </span>
                </div>
                <p v-if="reception.contrat" class="text-body-2 text-medium-emphasis mb-0 mt-1">
                  Contrat : {{ reception.contrat.reference }} — {{ (reception.contrat.objet || '').substring(0, 80) }}{{ (reception.contrat.objet || '').length > 80 ? '…' : '' }}
                </p>
              </div>
              <VBtn
                variant="tonal"
                color="secondary"
                @click="router.push(`/apps/contrats/${reception.contrat_id}`)"
              >
                Voir le contrat
              </VBtn>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VRow>
      <VCol cols="12" md="6">
        <VCard>
          <VCardTitle class="pa-4 text-subtitle-1">
            Données de la réception
          </VCardTitle>
          <VDivider />
          <VCardText>
            <VTable density="compact">
              <tbody>
                <tr>
                  <td class="text-medium-emphasis text-caption" style="width: 45%">Numéro</td>
                  <td class="font-weight-medium">{{ reception.numero }}</td>
                </tr>
                <tr>
                  <td class="text-medium-emphasis text-caption">Type</td>
                  <td>{{ typeLabels[reception.type_reception] }}</td>
                </tr>
                <tr>
                  <td class="text-medium-emphasis text-caption">Date de réception</td>
                  <td>{{ formatDate(reception.date_reception) }}</td>
                </tr>
                <tr v-if="reception.contrat?.date_previsionnelle_reception">
                  <td class="text-medium-emphasis text-caption">Date prévisionnelle de réception</td>
                  <td>{{ formatDate(reception.contrat.date_previsionnelle_reception) }}</td>
                </tr>
                <tr v-if="reception.lieu_reception">
                  <td class="text-medium-emphasis text-caption">Lieu</td>
                  <td>{{ reception.lieu_reception }}</td>
                </tr>
                <tr v-if="reception.responsable_reception">
                  <td class="text-medium-emphasis text-caption">Responsable</td>
                  <td>{{ reception.responsable_reception }}</td>
                </tr>
                <tr>
                  <td class="text-medium-emphasis text-caption">Conformité</td>
                  <td>{{ conformiteLabels[reception.statut_conformite] }}</td>
                </tr>
                <tr v-if="reception.quantite_receptionnee != null">
                  <td class="text-medium-emphasis text-caption">Quantité réceptionnée</td>
                  <td>{{ reception.quantite_receptionnee }}</td>
                </tr>
                <tr v-if="reception.taux_execution != null">
                  <td class="text-medium-emphasis text-caption">Taux d'exécution</td>
                  <td>{{ reception.taux_execution }} %</td>
                </tr>
                <tr>
                  <td class="text-medium-emphasis text-caption">Créé le</td>
                  <td>{{ reception.created_at ? formatDate(reception.created_at.slice(0, 10)) : '-' }}</td>
                </tr>
                <tr v-if="reception.created_by_user">
                  <td class="text-medium-emphasis text-caption">Créé par</td>
                  <td>{{ reception.created_by_user.prenom }} {{ reception.created_by_user.nom }}</td>
                </tr>
                <tr v-if="reception.approved_at">
                  <td class="text-medium-emphasis text-caption">Approuvé le</td>
                  <td>{{ formatDate(reception.approved_at.slice(0, 10)) }}</td>
                </tr>
                <tr v-if="reception.approved_by_user">
                  <td class="text-medium-emphasis text-caption">Approuvé par</td>
                  <td>{{ reception.approved_by_user.prenom }} {{ reception.approved_by_user.nom }}</td>
                </tr>
              </tbody>
            </VTable>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="6">
        <VCard v-if="reception.constatations || reception.reserves">
          <VCardTitle class="pa-4 text-subtitle-1">
            Constatations & réserves
          </VCardTitle>
          <VDivider />
          <VCardText>
            <template v-if="reception.constatations">
              <p class="text-caption text-medium-emphasis mb-1">Constatations</p>
              <p class="text-body-2 mb-4">{{ reception.constatations }}</p>
            </template>
            <template v-if="reception.reserves">
              <p class="text-caption text-medium-emphasis mb-1">Réserves / non-conformités</p>
              <p class="text-body-2" :class="reception.statut_conformite === 'non_conforme' ? 'text-error' : ''">
                {{ reception.reserves }}
              </p>
            </template>
            <template v-if="reception.commentaire_validation">
              <p class="text-caption text-medium-emphasis mb-1 mt-3">Commentaire de validation</p>
              <p class="text-body-2" :class="reception.statut === 'rejected' ? 'text-error' : ''">
                {{ reception.commentaire_validation }}
              </p>
            </template>
          </VCardText>
        </VCard>

        <VCard v-if="reception.reception_items?.length" class="mt-4">
          <VCardTitle class="pa-4 text-subtitle-1">
            Lignes / lots
          </VCardTitle>
          <VDivider />
          <VCardText>
            <VTable density="compact">
              <thead>
                <tr>
                  <th>Libellé</th>
                  <th>Qté prévue</th>
                  <th>Qté reçue</th>
                  <th>Conforme</th>
                  <th>Observation</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, idx) in reception.reception_items" :key="idx">
                  <td>{{ item.label || '-' }}</td>
                  <td>{{ item.quantite_prevue ?? '-' }}</td>
                  <td>{{ item.quantite_recue ?? '-' }}</td>
                  <td>
                    <VChip :color="item.conforme ? 'success' : 'error'" size="x-small">
                      {{ item.conforme ? 'Oui' : 'Non' }}
                    </VChip>
                  </td>
                  <td>{{ item.observation || '-' }}</td>
                </tr>
              </tbody>
            </VTable>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- GED Documents -->
    <VRow>
      <VCol cols="12">
        <DocumentsPanel
          documentable-type="receptions"
          :documentable-id="reception.id"
          entity-label="Réception"
        />
      </VCol>
    </VRow>
  </div>

  <div v-else class="d-flex justify-center align-center" style="min-height: 300px">
    <VProgressCircular indeterminate color="primary" />
  </div>

  <VSnackbar v-model="snackbar.show" :color="snackbar.color" timeout="4000" location="top right">
    {{ snackbar.text }}
  </VSnackbar>
</template>
