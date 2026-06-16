<script setup lang="ts">
definePage({ meta: { title: 'Tableau de bord CANAM' } })

const router = useRouter()
onMounted(() => router.replace({ name: 'dashboards-crm' }))
</script>

<template>
  <div />
</template>
