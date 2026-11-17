<script setup>
import { ref, provide, onMounted , computed, inject } from 'vue'
import { useAuthStore }  from '@/stores/auth'
import { useMainStore }  from '@/stores/mainStore'
import { useGuestStore }  from '@/stores/guest'
import MainView          from '@/components/MainView.vue'
import MainCarousel2     from '@/components/MainCarousel2.vue'
import GuestTopBar     from '@/components/guest/GuestTopBar.vue'
import GuestHero     from '@/components/guest/GuestHero.vue'
import AtAGlance     from '@/components/guest/AtAGlance.vue'
import AffiliateInfo     from '@/components/affiliate/AffiliateInfo.vue'
import AboutEdukittas     from '@/components/guest/AboutEdukittas.vue'
import GatewayIntro from '@/components/guest/GatewayIntro.vue'
import LoginForm    from '@/components/LoginForm.vue'
import RegisterForm from '@/components/RegisterForm.vue'
import DemoLogin from '@/components/DemoLogin.vue'


const landing = inject('landing', null)
const gatewayDone = ref(false)

// The intro belongs to Edukittas, so a school's own link never shows it.
const showGateway = computed(() => auth.isGuest && landing?.type !== 'school' && !gatewayDone.value)




const auth  = useAuthStore()
const store = useMainStore()
const guest = useGuestStore()
provide('accordionStore', store)

// Slides inside the Home section. We add the guest slides here one at a time.
const slides = [
  { component: GuestHero, name: 'Dashboard' },
  { component: AtAGlance, name: 'At a glance' },
  { component: AffiliateInfo, name: 'Affiliate' },
  { component: AboutEdukittas, name: 'About Edukittas' },
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
  { key: 'Demo',     label: 'Try the demo', icon: '🎮' }
]


 onMounted(() => guest.init()) 
</script>

<template>
  <template v-if="!auth.checked">
  <div
  
    class="w-full h-full flex items-center justify-center bg-neutral-900"
  >
    <span class="text-primary-300 text-sm animate-pulse">Loading...</span>
  </div>

  </template>
<template v-else>
  <MainView ref="mainView" :nav-items="navItems">
   
    <template #Home>
  <div class="relative w-full">
    <GuestTopBar />
    <MainCarousel2 :slides="slides" carousel-id="guest" />
  </div>
</template>

<template #Demo><DemoLogin />
</template>

    <template #Login>
      <LoginForm />
    </template>

    <template #Register>
      <RegisterForm />
    </template>

    <GatewayIntro v-if="showGateway" @done="gatewayDone = true" />
  </MainView>
  </template>
</template>