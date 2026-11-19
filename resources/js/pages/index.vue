<script setup>
import { ref, provide, onMounted, computed, inject } from 'vue'
import { useAuthStore }  from '@/stores/auth'
import { useMainStore }  from '@/stores/mainStore'
import { useGuestStore } from '@/stores/guest'
import MainView          from '@/components/MainView.vue'
import MainCarousel2     from '@/components/MainCarousel2.vue'
import GuestTopBar       from '@/components/guest/GuestTopBar.vue'
import GuestHero         from '@/components/guest/GuestHero.vue'
import AtAGlance         from '@/components/guest/AtAGlance.vue'
import AffiliateInfo     from '@/components/affiliate/AffiliateInfo.vue'
import AboutEdukittas    from '@/components/guest/AboutEdukittas.vue'
import GatewayIntro      from '@/components/guest/GatewayIntro.vue'
import LoginForm         from '@/components/LoginForm.vue'
import RegisterForm      from '@/components/RegisterForm.vue'
import DemoLogin         from '@/components/DemoLogin.vue'

const auth  = useAuthStore()
const store = useMainStore()
const guest = useGuestStore()

const landing = inject('landing', null)
const gatewayDone = ref(false)

// The intro belongs to Edukittas, so a school's own link never shows it.
const showGateway = computed(() => auth.isGuest && landing?.type !== 'school' && !gatewayDone.value)

provide('accordionStore', store)

// Featured schools for the hero and the picker; reopens the school the visitor picked last time.
onMounted(() => guest.init())

// Slides inside the Home section. Only "At a glance" changes with the picked school.
const slides = [
  { component: GuestHero,      name: 'Dashboard' },
  { component: AtAGlance,      name: 'At a glance' },
  { component: AffiliateInfo,  name: 'Affiliate' },
  { component: AboutEdukittas, name: 'About Edukittas' },
]

const mainView = ref(null)
const carousel = ref(null)

const switchTo = (key) => {
  mainView.value?.switchTo(key)
}
provide('switchTo', switchTo)

// Lets the top bar take the visitor to a slide by name, for example to "At a glance" after picking a school.
provide('goToSlideByName', (name) => {
  const i = slides.findIndex((s) => s.name === name)
  if (i >= 0) carousel.value?.goToSlide(i, 'click')
})

// Side menu for a guest.
const navItems = [
  { key: 'Home',     label: 'Home',         icon: '🏠' },
  { key: 'Login',    label: 'Login',        icon: '🔑' },
  { key: 'Register', label: 'Register',     icon: '📝' },
  { key: 'Demo',     label: 'Try the demo', icon: '🎮' },
]
</script>

<template>
  <div
    v-if="!auth.checked"
    class="w-full h-full flex items-center justify-center bg-neutral-900"
  >
    <span class="text-primary-300 text-sm animate-pulse">Loading...</span>
  </div>

  <template v-else>
    <MainView ref="mainView" :nav-items="navItems">
      <template #Home>
        <MainCarousel2 ref="carousel" :slides="slides" carousel-id="guest">
          <!-- The top bar sits above the carousel's tab strip, in the layout, so nothing covers anything -->
          <template #header>
            <GuestTopBar />
          </template>
        </MainCarousel2>
      </template>

      <template #Demo>
        <DemoLogin />
      </template>

      <template #Login>
        <LoginForm />
      </template>

      <template #Register>
        <RegisterForm />
      </template>
    </MainView>

    <!-- A sibling of MainView, not inside it: MainView has no slot for it -->
    <GatewayIntro v-if="showGateway" @done="gatewayDone = true" />
  </template>
</template>
