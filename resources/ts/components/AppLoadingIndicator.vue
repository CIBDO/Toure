<script setup lang="ts">
const bufferValue = ref(20)
const progressValue = ref(10)
const isFallbackState = ref(false)
const interval = ref<ReturnType<typeof setInterval>>()
const showProgress = ref(false)

watch([progressValue, isFallbackState], () => {
  if (progressValue.value > 80 && isFallbackState.value)
    progressValue.value = 82

  startBuffer()
})

function startBuffer() {
  clearInterval(interval.value)
  interval.value = setInterval(() => {
    progressValue.value += Math.random() * (15 - 5) + 5
    bufferValue.value += Math.random() * (15 - 5) + 6
  }, 800)
}

const fallbackHandle = () => {
  showProgress.value = true
  progressValue.value = 10
  isFallbackState.value = true
  startBuffer()
}

const resolveHandle = () => {
  isFallbackState.value = false
  progressValue.value = 100

  setTimeout(() => {
    clearInterval(interval.value)
    progressValue.value = 0
    bufferValue.value = 20
    showProgress.value = false
  }, 300)
}

defineExpose({
  fallbackHandle,
  resolveHandle,
})
</script>

<template>
  <!-- loading state via #fallback slot -->
  <div
    v-if="showProgress"
    class="position-fixed"
    style="z-index: 9999; inset-block-start: 0; inset-inline: 0 0;"
  >
    <VProgressLinear
      v-model="progressValue"
      :buffer-value="bufferValue"
      class="route-loading-indicator"
      height="3"
      bg-color="rgba(5, 155, 83, 0.1)"
    />
  </div>
</template>

<style lang="scss" scoped>
.route-loading-indicator {
  :deep(.v-progress-linear__determinate) {
    background: linear-gradient(90deg, #059b53 0%, #ebc333 50%, #dc3c3c 100%);
    box-shadow: 0 0 10px rgba(5, 155, 83, 0.5);
  }
  
  :deep(.v-progress-linear__buffer) {
    background: rgba(5, 155, 83, 0.15);
  }
}
</style>
