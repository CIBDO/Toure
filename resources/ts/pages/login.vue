<!-- Page de connexion - Plateforme CANAM -->
<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import { VForm } from 'vuetify/components/VForm'

definePage({
  meta: {
    layout: 'blank',
    unauthenticatedOnly: true,
  },
})

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const refVForm = ref<VForm>()
const isValid = ref(false)

const credentials = ref({
  email: '' as string | null,
  password: '' as string | null,
})

const rememberMe = ref(false)
const isPasswordVisible = ref(false)
const isRedirecting = ref(false)

const successMessage = computed(() => {
  if (route.query.message === 'password_changed')
    return 'Votre mot de passe a été changé avec succès. Veuillez vous connecter.'
  if (route.query.message === 'password_reset')
    return 'Votre mot de passe a été réinitialisé avec succès. Veuillez vous connecter.'
  return null
})

const fieldErrors = ref<Record<string, string>>({
  email: '',
  password: '',
})

const isFormValid = computed(() => {
  const email = credentials.value.email ?? ''
  const password = credentials.value.password ?? ''
  const emailValid = email.trim().length > 0 && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim())
  const passwordValid = password.length >= 8
  return emailValid && passwordValid
})

const handleLogin = async () => {
  fieldErrors.value = { email: '', password: '' }
  authStore.clearError()

  const { valid } = await refVForm.value?.validate() || { valid: false }
  if (!valid) return

  const result = await authStore.loginUser({
    email: (credentials.value.email ?? '').trim(),
    password: credentials.value.password ?? '',
    remember: rememberMe.value,
  })

  if (!result.success) {
    if (result.error?.errors) {
      Object.keys(result.error.errors).forEach((field) => {
        const errorMessages = result.error.errors[field]
        if (Array.isArray(errorMessages) && errorMessages.length > 0)
          fieldErrors.value[field] = errorMessages[0]
      })
    }
    if (result.error?.message && !Object.values(fieldErrors.value).some(e => e))
      fieldErrors.value.email = result.error.message
    return
  }

  // Si la redirection a déjà été gérée (ex: must_change_password), ne pas rediriger à nouveau
  if (result.redirectHandled)
    return

  isRedirecting.value = true
  const redirectTo = route.query?.to && typeof route.query.to === 'string' ? route.query.to : '/'
  await nextTick()
  window.location.href = redirectTo
}

const onSubmit = () => {
  handleLogin()
}

onMounted(() => {
  nextTick(() => {
    const emailInput = document.querySelector('input[type="email"]') as HTMLInputElement
    emailInput?.focus()
  })
})
</script>

<template>
  <div class="login-page">
    <!-- Écran de transition après connexion -->
    <Transition name="fade">
      <div
        v-if="isRedirecting"
        class="redirecting-overlay"
      >
        <img
          src="@images/canam.png"
          alt="Logo CANAM"
          class="redirecting-logo"
        >
        <div class="redirecting-spinner">
          <div class="effect-1 effects" />
          <div class="effect-2 effects" />
          <div class="effect-3 effects" />
        </div>
      </div>
    </Transition>

    <!-- Carte de connexion -->
    <div
      class="login-card"
      :class="{ 'card-hidden': isRedirecting }"
    >
      <!-- Logo CANAM -->
      <div class="login-logo-wrap">
        <img
          src="@images/canam.png"
          alt="Logo CANAM"
          class="login-logo"
        >
      </div>

      <!-- Titre -->
      <h1 class="login-title">
        Connexion
      </h1>

      <!-- Alertes -->
      <div style="position: relative; z-index: 4;">
        <VAlert
          v-if="successMessage"
          color="success"
          variant="tonal"
          class="mb-4"
          closable
          density="compact"
        >
          {{ successMessage }}
        </VAlert>

        <VAlert
          v-if="authStore.error && !Object.values(fieldErrors).some(e => e)"
          color="error"
          variant="tonal"
          class="mb-4"
          closable
          density="compact"
          @click:close="authStore.clearError()"
        >
          {{ authStore.error }}
        </VAlert>
      </div>

      <!-- Formulaire -->
      <VForm
        ref="refVForm"
        v-model="isValid"
        class="login-form"
        @submit.prevent="onSubmit"
      >
        <!-- Email -->
        <div class="field-group">
          <label class="field-label">Nom d'utilisateur</label>
          <AppTextField
            v-model="credentials.email"
            placeholder="Email ou téléphone"
            type="email"
            autofocus
            density="comfortable"
            variant="outlined"
            hide-details
            autocomplete="username"
            clearable
            class="login-field"
            :error-messages="fieldErrors.email ? [fieldErrors.email] : []"
          />
        </div>

        <!-- Mot de passe -->
        <div class="field-group">
          <label class="field-label">Mot de passe</label>
          <AppTextField
            v-model="credentials.password"
            placeholder="Mot de passe"
            :type="isPasswordVisible ? 'text' : 'password'"
            density="comfortable"
            variant="outlined"
            hide-details
            autocomplete="current-password"
            class="login-field"
            :error-messages="fieldErrors.password ? [fieldErrors.password] : []"
            :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
            @click:append-inner="isPasswordVisible = !isPasswordVisible"
          />
        </div>

        <!-- Options -->
        <div class="login-options">
          <VCheckbox
            v-model="rememberMe"
            label="Se souvenir de moi"
            density="compact"
            hide-details
            class="remember-check"
          />
          <RouterLink
            class="forgot-link"
            :to="{ name: 'forgot-password' }"
          >
            Mot de passe oublié ?
          </RouterLink>
        </div>

        <!-- Bouton -->
        <VBtn
          block
          type="submit"
          size="large"
          :loading="authStore.isLoading"
          :disabled="!isFormValid || authStore.isLoading"
          class="login-btn mt-2"
        >
          Se connecter
        </VBtn>
      </VForm>

      <!-- Footer -->
      <div class="login-divider" />
      <p class="login-footer">
       CAISSE NATIONALE D'ASSURANCE MALADIE
      </p>
      <p class="login-year">
        © {{ new Date().getFullYear() }} CANAM
      </p>
    </div>
  </div>
</template>

<style lang="scss" scoped>
// ── Couleurs CANAM ──────────────────────────────────────────────
$canam-blue:   #1a5fa8;
$canam-green:  #059b53;
$canam-red:    #dc3c3c;
$canam-gold:   #ebc333;
$canam-dark:   #0d1b2a;
$canam-card:   #ffffff;
$canam-border: rgba(26, 95, 168, 0.18);

// ── Page ────────────────────────────────────────────────────────
.login-page {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 2rem 1rem;
  background:
    radial-gradient(circle at top left, rgba(26, 95, 168, 0.16), transparent 32%),
    radial-gradient(circle at bottom right, rgba(235, 195, 51, 0.18), transparent 30%),
    linear-gradient(135deg, #eef4fb 0%, #f8fbff 48%, #f3f7fb 100%);
  overflow: hidden;
}

// ── Carte ───────────────────────────────────────────────────────
.login-card {
  position: relative;
  z-index: 3;
  width: 100%;
  max-width: 420px;
  margin: 1.5rem;
  padding: 2.5rem 2rem 1.75rem;
  background: rgba(255, 255, 255, 0.88);
  border: 1px solid rgba(255, 255, 255, 0.65);
  border-radius: 24px;
  box-shadow: 0 22px 60px rgba(26, 95, 168, 0.16), 0 10px 20px rgba(13, 27, 42, 0.08);
  backdrop-filter: blur(16px);
  overflow: hidden;
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.login-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 28px 72px rgba(26, 95, 168, 0.18), 0 12px 26px rgba(13, 27, 42, 0.08);
}

.card-accent {
  position: absolute;
  inset-block-start: 0;
  inset-inline: 0;
  height: 5px;
  background: linear-gradient(90deg, $canam-blue 0%, $canam-green 45%, $canam-gold 100%);
  z-index: 5;
}

// ── Logo ────────────────────────────────────────────────────────
.login-logo-wrap {
  display: flex;
  justify-content: center;
  margin-bottom: 1.25rem;
  position: relative;
  z-index: 4;
}

.login-logo {
  width: 90px;
  height: 90px;
  object-fit: contain;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.9);
  box-shadow: 0 10px 28px rgba($canam-blue, 0.22);
  padding: 0.4rem;
}

// ── Titre ───────────────────────────────────────────────────────
.login-title {
  font-size: 1.75rem;
  font-weight: 700;
  background: linear-gradient(135deg, $canam-dark 0%, $canam-blue 75%);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
  text-align: center;
  margin-bottom: 0.25rem;
  letter-spacing: 0.01em;
  position: relative;
  z-index: 4;
}

// ── Formulaire ──────────────────────────────────────────────────
.login-form {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  position: relative;
  z-index: 4;
}

.field-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  margin-bottom: 0.85rem;
}

.field-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: rgba($canam-dark, 0.7);
  letter-spacing: 0.03em;
}

// Surcharge des champs pour le thème clair
.login-field {
  :deep(.v-field) {
    background: rgba(247, 249, 252, 0.92) !important;
    border-radius: 12px;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);

    .v-field__overlay,
    .v-field__outline__start,
    .v-field__outline__end,
    .v-field__outline__notch {
      border-color: transparent !important;
      box-shadow: none !important;
    }

    &:hover .v-field__overlay,
    &:hover .v-field__outline__start,
    &:hover .v-field__outline__end,
    &:hover .v-field__outline__notch {
      border-color: transparent !important;
      box-shadow: none !important;
    }

    &.v-field--focused .v-field__overlay,
    &.v-field--focused .v-field__outline__start,
    &.v-field--focused .v-field__outline__end,
    &.v-field--focused .v-field__outline__notch {
      border-color: transparent !important;
      box-shadow: none !important;
    }
  }

  :deep(input),
  :deep(.v-field__input) {
    color: $canam-dark !important;
    font-size: 0.9rem;
    border-top: none !important;
    border-left: none !important;
    border-right: none !important;
    border-bottom: none !important;
    box-shadow: none !important;
    outline: none !important;
    background: transparent !important;

    &::placeholder {
      color: rgba($canam-dark, 0.3) !important;
    }
  }

  :deep(.v-icon) {
    color: rgba($canam-dark, 0.35) !important;
  }
}

// ── Options ─────────────────────────────────────────────────────
.login-options {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-top: 0.1rem;
  padding-top: 0.35rem;
  margin-bottom: 0.5rem;
  position: relative;
  z-index: 4;
}

.remember-check {
  :deep(.v-label) {
    font-size: 0.8rem;
    color: rgba($canam-dark, 0.6);
  }
}

.forgot-link {
  font-size: 0.8rem;
  color: $canam-blue;
  text-decoration: none;
  transition: color 0.2s;

  &:hover {
    color: darken($canam-blue, 10%);
    text-decoration: underline;
  }
}

// ── Bouton principal ─────────────────────────────────────────────
.login-btn {
  background: linear-gradient(135deg, $canam-blue 0%, darken($canam-blue, 10%) 100%) !important;
  color: #fff !important;
  font-weight: 700 !important;
  font-size: 0.95rem !important;
  letter-spacing: 0.03em !important;
  text-transform: none !important;
  border-radius: 12px !important;
  height: 50px !important;
  box-shadow: 0 10px 24px rgba($canam-blue, 0.32) !important;
  transition: transform 0.2s ease, box-shadow 0.2s ease !important;
  position: relative;
  z-index: 4;
  overflow: hidden;

  &::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(120deg, transparent 20%, rgba(255, 255, 255, 0.24) 50%, transparent 80%);
    transform: translateX(-120%);
    transition: transform 0.45s ease;
  }

  &:not(:disabled):hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba($canam-blue, 0.4) !important;

    &::before {
      transform: translateX(120%);
    }
  }

  &:not(:disabled):active {
    transform: translateY(0);
  }

  &:disabled {
    background: rgba($canam-blue, 0.3) !important;
    color: rgba(255, 255, 255, 0.5) !important;
  }
}

// ── Footer ───────────────────────────────────────────────────────
.login-divider {
  width: 100%;
  height: 1px;
  margin-top: 1.35rem;
  background: linear-gradient(90deg, transparent 0%, rgba($canam-blue, 0.18) 50%, transparent 100%);
}

.login-footer {
  margin-top: 1rem;
  font-size: 0.7rem;
  color: rgba($canam-dark, 0.35);
  text-align: center;
  line-height: 1.5;
  position: relative;
  z-index: 4;
}

.login-year {
  margin-top: 0.25rem;
  font-size: 0.65rem;
  color: rgba($canam-dark, 0.25);
  text-align: center;
  position: relative;
  z-index: 4;
}

// ── Overlay de redirection ───────────────────────────────────────
.redirecting-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1.5rem;
  background: #f0f4f8;
}

.redirecting-logo {
  width: 120px;
  height: 120px;
  object-fit: contain;
}

.redirecting-spinner {
  position: relative;
  width: 55px;
  height: 55px;
}

.redirecting-spinner .effects {
  position: absolute;
  inset: 0;
  box-sizing: border-box;
  border: 3px solid transparent;
  border-radius: 50%;
}

.redirecting-spinner .effect-1 {
  border-inline-start: 3px solid #059b53;
  animation: spinnerRotate 1s ease infinite;
}

.redirecting-spinner .effect-2 {
  border-inline-start: 3px solid #ebc333;
  animation: spinnerRotate 1s ease infinite 0.1s;
  opacity: 0.1;
}

.redirecting-spinner .effect-3 {
  border-inline-start: 3px solid #dc3c3c;
  animation: spinnerRotate 1s ease infinite 0.2s;
  opacity: 0.1;
}

@keyframes spinnerRotate {
  0%   { transform: rotate(0deg); opacity: 0.1; }
  100% { transform: rotate(1turn); opacity: 1; }
}

.card-hidden {
  opacity: 0;
  pointer-events: none;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

// ── Responsive ───────────────────────────────────────────────────
@media (max-width: 480px) {
  .login-card {
    padding: 2rem 1.25rem 1.5rem;
    margin: 1rem;
    border-radius: 20px;
  }

  .login-logo {
    width: 72px;
    height: 72px;
  }

  .login-title {
    font-size: 1.5rem;
  }

  .login-options {
    flex-direction: column;
    align-items: flex-start;
  }

  .blob-top-left {
    width: 140px;
    height: 140px;
    top: -50px;
    left: -50px;
  }

  .blob-bottom-right {
    width: 130px;
    height: 130px;
    bottom: -45px;
    right: -45px;
  }

  .glow-left,
  .glow-right {
    width: 180px;
    height: 180px;
  }
}
</style>
