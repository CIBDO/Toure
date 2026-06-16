/**
 * Store Pinia pour la gestion de l'état d'authentification
 * Plateforme de Gestion des Contrats - CANAM
 */

import { useAuth } from '@/composables/useAuth'
import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', () => {
  // Ne pas appeler useAuth() au niveau du setup, mais dans les actions
  // pour éviter les problèmes d'initialisation

  // State
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // Getters - utiliser directement les cookies pour éviter les problèmes d'initialisation
  const user = computed(() => useCookie('userData').value || null)
  const token = computed(() => useCookie('accessToken').value || null)
  const authenticated = computed(() => !!(useCookie('accessToken').value && useCookie('userData').value))

  // Actions
  const loginUser = async (credentials: { email: string; password: string; remember?: boolean }) => {
    isLoading.value = true
    error.value = null

    try {
      // Appeler useAuth() dans l'action pour éviter les problèmes d'initialisation
      const { login } = useAuth()
      const result = await login(credentials)

      if (!result.success && result.error) {
        error.value = result.error.message

        // Gérer les erreurs spécifiques
        if (result.error.status === 401) {
          error.value = 'Identifiants incorrects.'
        }
        else if (result.error.status === 403 || result.error.status === 423) {
          error.value = 'Compte suspendu ou désactivé. Contactez l\'administrateur.'
        }
        else if (result.error.status === 422) {
          // Les erreurs de validation sont gérées par les champs spécifiques
          error.value = 'Veuillez corriger les erreurs du formulaire.'
        }

        return { success: false, error: result.error }
      }

      return { success: true, redirectHandled: result.redirectHandled }
    }
    catch (err: any) {
      error.value = err.message || 'Une erreur est survenue lors de la connexion.'
      return { success: false, error: { message: error.value } }
    }
    finally {
      isLoading.value = false
    }
  }

  const logoutUser = async () => {
    isLoading.value = true
    error.value = null

    try {
      // Appeler useAuth() dans l'action pour éviter les problèmes d'initialisation
      const { logout } = useAuth()
      await logout()
    }
    catch (err: any) {
      error.value = err.message || 'Une erreur est survenue lors de la déconnexion.'
    }
    finally {
      isLoading.value = false
    }
  }

  const clearError = () => {
    error.value = null
  }

  return {
    // State
    isLoading,
    error,
    // Getters
    user,
    token,
    authenticated,
    // Actions
    loginUser,
    logoutUser,
    clearError,
  }
})
