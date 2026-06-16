import type { App } from 'vue'

import { createMongoAbility } from '@casl/ability'
import { abilitiesPlugin } from '@casl/vue'
import type { Rule } from './ability'

// Clé de stockage des règles CASL (localStorage évite la limite ~4 Ko des cookies)
const USER_ABILITY_RULES_KEY = 'CANAM-userAbilityRules'

function getStoredAbilityRules(): unknown[] {
  if (typeof window === 'undefined') return []
  try {
    const raw = localStorage.getItem(USER_ABILITY_RULES_KEY)
    if (!raw) return []
    const parsed = JSON.parse(raw)
    return Array.isArray(parsed) ? parsed : []
  } catch {
    return []
  }
}

export default function (app: App) {
  const userAbilityRulesCookie = useCookie<Rule[]>('userAbilityRules')
  // Lire d'abord depuis localStorage (pas de limite de taille), sinon cookie, sinon règles minimales
  const storedRules = getStoredAbilityRules() as Rule[]
  const rules = storedRules.length > 0
    ? storedRules
    : (userAbilityRulesCookie.value && userAbilityRulesCookie.value.length > 0
        ? userAbilityRulesCookie.value
        : [
            { action: 'view' as const, subject: 'Dashboard' as const },
            { action: 'view' as const, subject: 'User' as const },
          ])
  const initialAbility = createMongoAbility(rules)

  app.use(abilitiesPlugin, initialAbility, {
    useGlobalProperties: true,
  })
}
