import './bootstrap';
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router/auto';
import { createPinia } from 'pinia';
import {routes, handleHotUpdate} from 'vue-router/auto-routes'
import App from './App.vue'
//import {setupGuards} from '@/router/guards'


const router = createRouter({
    history: createWebHistory(),
    routes //: [...routes, { path: '/', redirect: '/school' }],
})

if (import.meta.hot) {
    handleHotUpdate(router)
}

const pinia = createPinia()
const landing = window.__LANDING__

 const app = createApp(App)
app.provide('landing', landing)
app.use(router)
app.use(pinia)
app.mount('#app')






