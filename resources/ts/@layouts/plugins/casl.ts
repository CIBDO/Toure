import { useAbility } from '@casl/vue'
import type { NavGroup } from '@layouts/types'
import type { RouteLocationNormalized } from 'vue-router'

/**
 * Returns ability result if ACL is configured or else just return true
 * We should allow passing string | undefined to can because for admin ability we omit defining action & subject
 *
 * Useful if you don't know if ACL is configured or not
 * Used in @core files to handle absence of ACL without errors
 *
 * @param {string} action CASL Actions // https://casl.js.org/v4/en/guide/intro#basics
 * @param {string} subject CASL Subject // https://casl.js.org/v4/en/guide/intro#basics
 */
export const can = (action: string | undefined, subject: string | undefined) => {
  const vm = getCurrentInstance()

  if (!vm)
    return false

  const localCan = vm.proxy && '$can' in vm.proxy

  // @ts-expect-error We will get TS error in below line because we aren't using $can in component instance
  return localCan ? vm.proxy?.$can(action, subject) : true
}

/**
 * Check if user can view item based on it's ability
 * Based on item's action and subject & Hide group if all of it's children are hidden
 * @param {object} item navigation object item
 */
export const canViewNavMenuGroup = (item: NavGroup) => {
  const hasAnyVisibleChild = item.children.some(i => can(i.action, i.subject))

  // If subject and action is defined in item => Return based on children visibility (Hide group if no child is visible)
  // Else check for ability using provided subject and action along with checking if has any visible child
  if (!(item.action && item.subject))
    return hasAnyVisibleChild

  return can(item.action, item.subject) && hasAnyVisibleChild
}

export const canNavigate = (to: RouteLocationNormalized) => {
  // Si la route est publique ou unauthenticatedOnly, permettre la navigation
  if (to.meta.public || to.meta.unauthenticatedOnly)
    return true

  // Si aucune route n'est matchée, permettre la navigation
  if (!to.matched || to.matched.length === 0)
    return true

  try {
    const ability = useAbility()

    // Si aucune route n'a de meta.action ou meta.subject, permettre la navigation (pas de restriction CASL)
    const hasCaslMeta = to.matched.some(route => route.meta.action || route.meta.subject)
    if (!hasCaslMeta)
      return true

    // @ts-expect-error We should allow passing string | undefined to can because for admin ability we omit defining action & subject
    return to.matched.some(route => ability.can(route.meta.action, route.meta.subject))
  } catch (error) {
    // Si useAbility() n'est pas disponible (plugin non initialisé), permettre la navigation
    // Cela peut arriver sur les pages publiques ou avant l'initialisation complète de CASL
    console.warn('CASL ability not available in canNavigate, allowing navigation:', error)
    return true
  }
}
