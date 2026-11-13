
//import {setupGuards} from '@/router/guards'

import './bootstrap';
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router/auto';
import { createPinia } from 'pinia';
import { routes, handleHotUpdate } from 'vue-router/auto-routes'
import App from './App.vue'
import { useAuthStore } from '@/stores/auth'



const pinia = createPinia()

const router = createRouter({
    history: createWebHistory(),
    routes
})
// Each person lives in exactly one world: guests at /, everyone else at their own.
const HOME = { guest: '/', school: '/school', admin: '/admin', affiliate: '/affiliate' }
const worldOfPath = (path) =>
    ['school', 'admin', 'affiliate'].find((w) => path === `/${w}` || path.startsWith(`/${w}/`)) ?? 'guest'

router.beforeEach(async (to) => {
    const auth = useAuthStore(pinia)
    if (!auth.checked) await auth.me()

    if (worldOfPath(to.path) !== auth.world) return HOME[auth.world]
})

if (import.meta.hot) {
    handleHotUpdate(router)
}


// if (import.meta.hot) {
//     handleHotUpdate(router)
// }

const landing = window.__LANDING__

const app = createApp(App)
app.provide('landing', landing)
app.use(pinia)      // before the router, because the guard above uses the store
app.use(router)
app.mount('#app')




