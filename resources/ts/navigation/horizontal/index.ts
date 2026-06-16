import type { HorizontalNavItems } from '@layouts/types'
import iam from './iam'

// Menu horizontal centré sur IAM
export default [...iam] as HorizontalNavItems

// Menu complet (décommenter si vous voulez tous les menus)
// export default [...dashboard, ...iam, ...apps, ...pages, ...uiElements, ...forms, ...tables, ...charts, ...misc] as HorizontalNavItems
