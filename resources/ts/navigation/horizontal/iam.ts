export default [
  {
    title: 'Tableau de bord',
    icon: { icon: 'tabler-smart-home' },
    to: 'dashboards-crm',
  },
  {
    title: 'Gestion IAM',
    icon: { icon: 'tabler-shield' },
    children: [
      {
        title: 'Utilisateurs',
        icon: { icon: 'tabler-users' },
        children: [
          { title: 'Liste des utilisateurs', to: 'apps-user-list' },
        ],
      },
      {
        title: 'Rôles',
        icon: { icon: 'tabler-user-shield' },
        to: 'apps-roles',
      },
      {
        title: 'Permissions',
        icon: { icon: 'tabler-key' },
        to: 'apps-permissions',
      },
    ],
  },
  {
    title: 'Applications',
    icon: { icon: 'tabler-layout-grid' },
    children: [
      {
        title: 'Email',
        icon: { icon: 'tabler-mail' },
        to: 'apps-email',
      },
      {
        title: 'Chat',
        icon: { icon: 'tabler-message-circle' },
        to: 'apps-chat',
      },
      {
        title: 'Calendrier',
        icon: { icon: 'tabler-calendar' },
        to: 'apps-calendar',
      },
    ],
  },
]
