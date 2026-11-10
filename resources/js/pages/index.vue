<script setup>
import { ref, provide } from 'vue'
import { useAuthStore }  from '@/stores/auth'
import { useMainStore }  from '@/stores/mainStore'
import MainView          from '@/components/MainView.vue'
import MainCarousel2     from '@/components/MainCarousel2.vue'

import HomePage     from '@/components/HomePage.vue'
import LoginForm    from '@/components/LoginForm.vue'
import RegisterForm from '@/components/RegisterForm.vue'

const auth  = useAuthStore()
const store = useMainStore()

provide('accordionStore', store)

// Slides inside the Home section. We add the guest slides here one at a time.
const slides = [
  { component: HomePage, name: 'Home' },
]

const mainView = ref(null)
const switchTo = (key) => {
  mainView.value?.switchTo(key)
}
provide('switchTo', switchTo)

// Side menu for a guest.
const navItems = [
  { key: 'Home',     label: 'Home',     icon: '🏠' },
  { key: 'Login',    label: 'Login',    icon: '🔑' },
  { key: 'Register', label: 'Register', icon: '📝' },
]
</script>

<template>
  <div
    v-if="!auth.checked"
    class="w-full h-full flex items-center justify-center bg-neutral-900"
  >
    <span class="text-primary-300 text-sm animate-pulse">Loading...</span>
  </div>

  <MainView ref="mainView" v-else :nav-items="navItems">
    <template #Home>
      <MainCarousel2 :slides="slides" carousel-id="guest" />
    </template>

    <template #Login>
      <LoginForm />
    </template>

    <template #Register>
      <RegisterForm />
    </template>
  </MainView>
</template>