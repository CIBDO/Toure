import { canNavigate } from '@layouts/plugins/casl'
import type { RouteNamedMap, _RouterTyped } from 'unplugin-vue-router'

export const setupGuards = (router: _RouterTyped<RouteNamedMap & { [key: string]: any }>) => {
  router.beforeEach(to => {
    const isLoggedIn = !!(useCookie('userData').value && useCookie('accessToken').value)
    const userData = useCookie<Record<string, any> | null>('userData').value
    const mustChangePassword = !!(userData?.must_change_password)

    // Gérer la page change-password : accessible uniquement si connecté ET must_change_password
    if (to.path === '/change-password') {
      if (!isLoggedIn)
        return { name: 'login' }
      if (!mustChangePassword)
        return { name: 'dashboards-crm' }
      return
    }

    // Si l'utilisateur est connecté mais doit changer son mot de passe,
    // bloquer l'accès à toutes les autres pages protégées
    if (isLoggedIn && mustChangePassword && !to.meta.public) {
      return {
        path: '/change-password',
        query: userData?.id ? { user_id: String(userData.id) } : {},
      }
    }

    if (to.meta.public)
      return

    if (to.meta.unauthenticatedOnly) {
      if (isLoggedIn) {
        const redirectTo = to.query?.to && typeof to.query.to === 'string' ? to.query.to : '/'
        return redirectTo
      }
      else {
        return undefined
      }
    }

    if (!canNavigate(to) && to.matched.length) {
      /* eslint-disable indent */
      return isLoggedIn
        ? { name: 'not-authorized' }
        : {
            name: 'login',
            query: {
              ...(to.query || {}),
              ...(to.fullPath && to.fullPath !== '/' && to.path ? { to: to.path } : {}),
            },
          }
      /* eslint-enable indent */
    }
  })
}
