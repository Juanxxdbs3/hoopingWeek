import 'bootstrap/dist/css/bootstrap.min.css'  // ✅ PRIMERO Bootstrap CSS
import 'bootstrap/dist/js/bootstrap.bundle.min.js'  // ✅ LUEGO Bootstrap JS
import './assets/css/font-inter.css';  // ✅ Tus fuentes
import './assets/css/main.css';  // ✅ Tus estilos generales
// ❌ NO importar admin.css aquí si tiene conflictos

import { createApp } from 'vue'
import App from './App.vue'
import router from './router'

const app = createApp(App)
app.use(router)
app.mount('#app')
