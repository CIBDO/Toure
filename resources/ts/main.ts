import { createApp } from 'vue'

import App from '@/App.vue'
import { registerPlugins } from '@core/utils/plugins'

// Styles
import '@core-scss/template/index.scss'
import '@styles/styles.scss'

// Forcer le layout vertical en supprimant le cookie de layout potentiellement en cache
document.cookie = 'CANAM-appContentLayoutNav=vertical; path=/; max-age=31536000'

// Create vue app
const app = createApp(App)

// Register plugins
registerPlugins(app)

// Mount vue app
app.mount('#app')
