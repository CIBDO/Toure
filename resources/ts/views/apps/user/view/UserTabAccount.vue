<script lang="ts" setup>
interface Props {
  userData?: {
    id: number
    nom: string
    prenom: string
    email: string
    telephone?: string
    fonction?: string
    unite_service?: string
    region?: string
    statut: string
    type_compte: string
    last_login_at?: string
    created_at: string
  }
}

const props = defineProps<Props>()

// Les logs d'audit seront ajoutés plus tard quand l'endpoint API sera disponible
</script>

<template>
  <VRow>
    <!-- 👉 Informations du compte -->
    <VCol cols="12">
      <VCard title="Informations du compte">
        <VCardText>
          <VRow>
            <VCol
              cols="12"
              md="6"
            >
              <VTable class="text-no-wrap">
                <tr>
                  <td class="text-h6">
                    Nom:
                  </td>
                  <td class="text-body-1">
                    {{ props.userData?.nom || 'N/A' }}
                  </td>
                </tr>
                <tr>
                  <td class="text-h6">
                    Prénom:
                  </td>
                  <td class="text-body-1">
                    {{ props.userData?.prenom || 'N/A' }}
                  </td>
                </tr>
                <tr>
                  <td class="text-h6">
                    Email:
                  </td>
                  <td class="text-body-1">
                    {{ props.userData?.email || 'N/A' }}
                  </td>
                </tr>
                <tr>
                  <td class="text-h6">
                    Téléphone:
                  </td>
                  <td class="text-body-1">
                    {{ props.userData?.telephone || 'N/A' }}
                  </td>
                </tr>
              </VTable>
            </VCol>
            <VCol
              cols="12"
              md="6"
            >
              <VTable class="text-no-wrap">
                <tr>
                  <td class="text-h6">
                    Fonction:
                  </td>
                  <td class="text-body-1">
                    {{ props.userData?.fonction || 'N/A' }}
                  </td>
                </tr>
                <tr>
                  <td class="text-h6">
                    Unité/Service:
                  </td>
                  <td class="text-body-1">
                    {{ props.userData?.unite_service || 'N/A' }}
                  </td>
                </tr>
                <tr>
                  <td class="text-h6">
                    Région:
                  </td>
                  <td class="text-body-1">
                    {{ props.userData?.region || 'N/A' }}
                  </td>
                </tr>
                <tr>
                  <td class="text-h6">
                    Type de compte:
                  </td>
                  <td class="text-body-1">
                    {{ props.userData?.type_compte || 'N/A' }}
                  </td>
                </tr>
              </VTable>
            </VCol>
          </VRow>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>
