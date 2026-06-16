/**
 * Composable pour la gestion de l'authentification
 * Plateforme de Gestion des Contrats - CANAM
 */

// useAbility est auto-importé depuis resources/ts/plugins/casl/composables/useAbility
// $api est auto-importé depuis resources/ts/utils/api

import { router } from '@/plugins/1.router'

interface LoginCredentials {
  email: string
  password: string
  remember?: boolean
}

interface LoginResponse {
  accessToken: string
  userData: any
  userAbilityRules?: any[]
  must_change_password?: boolean
}

interface AuthError {
  message: string
  errors?: Record<string, string[]>
  status?: number
}

export const useAuth = () => {
  // Ne pas appeler useAbility() au niveau du setup pour éviter les problèmes d'injection
  // Il sera appelé uniquement quand nécessaire dans les fonctions login/logout

  /**
   * Connexion utilisateur
   */
  const login = async (credentials: LoginCredentials): Promise<{ success: boolean; redirectHandled?: boolean; error?: AuthError }> => {
    try {
      const response = await $api('/auth/login', {
        method: 'POST',
        body: {
          email: credentials.email,
          password: credentials.password,
        },
      }) as LoginResponse

      // Vérifier que la réponse contient les données attendues
      if (!response?.accessToken || !response?.userData) {
        return {
          success: false,
          error: {
            message: 'Réponse invalide du serveur',
            status: 500,
          },
        }
      }

      const { accessToken, userData, userAbilityRules, must_change_password } = response

      // Stocker les données dans les cookies
      useCookie('accessToken').value = accessToken
      useCookie('userData').value = userData
      // Règles CASL dans localStorage (évite la limite ~4 Ko des cookies pour les rôles avec beaucoup de permissions)
      const rules = userAbilityRules && Array.isArray(userAbilityRules) ? userAbilityRules : []
      try {
        if (typeof window !== 'undefined')
          window.localStorage.setItem('CANAM-userAbilityRules', JSON.stringify(rules))
      } catch {
        // Fallback cookie si localStorage indisponible
        useCookie<any[]>('userAbilityRules').value = rules
      }

      // Si remember me, on peut ajuster la durée du cookie
      if (credentials.remember) {
        // Les cookies sont déjà persistants par défaut dans useCookie
        // On pourrait ajuster les options ici si nécessaire
      }

      // Vérifier si l'utilisateur doit changer son mot de passe (première connexion)
      if (must_change_password === true || userData?.must_change_password === true) {
        await nextTick(() => {
          router.replace({
            path: '/change-password',
            query: userData?.id ? {
              user_id: String(userData.id),
            } : {},
          })
        })
        return { success: true, redirectHandled: true }
      }

      // Mettre à jour les règles d'abilité CASL (seulement si disponible)
      try {
        const ability = useAbility()
        if (userAbilityRules && Array.isArray(userAbilityRules) && userAbilityRules.length > 0) {
          ability.update(userAbilityRules)
        } else {
          ability.update([
            { action: 'view', subject: 'Dashboard' },
            { action: 'view', subject: 'User' },
          ])
        }
      } catch {
        // Règles chargées au rechargement via localStorage
      }

      // Redirection vers le dashboard par rechargement complet pour que le guard CASL
      // voie bien les règles (lues depuis localStorage au boot). Évite la redirection /not-authorized.
      await nextTick()
      const dashboardPath = '/dashboards/crm'
      window.location.replace(window.location.origin + dashboardPath)
      return { success: true, redirectHandled: true }
    }
    catch (error: any) {
      // Gestion des erreurs
      let authError: AuthError = {
        message: 'Une erreur est survenue lors de la connexion',
        status: 500,
      }

      // Vérifier si c'est une erreur HTTP (ofetch structure)
      if (error?.response) {
        const response = error.response
        const data = response._data || response.data || {}
        
        authError = {
          message: data.message || authError.message,
          errors: data.errors,
          status: response.status || 500,
        }
      }
      else if (error?.data) {
        authError = {
          message: error.data.message || authError.message,
          errors: error.data.errors,
          status: error.data.status || 500,
        }
      }
      else if (error?.message) {
        // Erreur réseau
        const errorMessage = String(error.message).toLowerCase()
        if (errorMessage.includes('fetch') || errorMessage.includes('network') || errorMessage.includes('failed')) {
          authError.message = 'Impossible de joindre le serveur. Réessayez.'
        }
        else {
          authError.message = error.message
        }
      }

      return {
        success: false,
        error: authError,
      }
    }
  }

  /**
   * Déconnexion utilisateur
   */
  const logout = async () => {
    try {
      // Appel API pour invalider le token côté serveur (optionnel)
      await $api('/auth/logout', {
        method: 'POST',
      }).catch(() => {
        // Ignorer les erreurs de déconnexion (le token peut être déjà expiré)
      })
    }
    catch {
      // Ignorer les erreurs
    }
    finally {
      // Supprimer les cookies et le stockage des règles
      useCookie('accessToken').value = null
      useCookie('userData').value = null
      try {
        if (typeof window !== 'undefined')
          window.localStorage.removeItem('CANAM-userAbilityRules')
      } catch {}
      useCookie('userAbilityRules').value = null

      // Réinitialiser les règles d'abilité (seulement si disponible)
      try {
        const ability = useAbility()
        ability.update([])
      } catch (error) {
        // Ignorer l'erreur si CASL n'est pas encore initialisé
        console.warn('CASL ability not available during logout:', error)
      }

      // Rediriger vers la page de connexion
      await nextTick(() => {
        router.replace({ path: '/login' })
      })
    }
  }

  /**
   * Vérifier si l'utilisateur est connecté
   */
  const isAuthenticated = computed(() => {
    return !!(useCookie('accessToken').value && useCookie('userData').value)
  })

  /**
   * Obtenir les données utilisateur
   */
  const getUser = computed(() => {
    return useCookie('userData').value || null
  })

  /**
   * Obtenir le token d'accès
   */
  const getToken = computed(() => {
    return useCookie('accessToken').value || null
  })

  return {
    login,
    logout,
    isAuthenticated,
    getUser,
    getToken,
  }
}
