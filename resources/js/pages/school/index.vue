<script setup>
import { ref, computed, provide, defineAsyncComponent } from 'vue'
import { useThemeStore } from '@/stores/theme'
import { useAuthStore }  from '@/stores/auth'
import { useMainStore }  from '@/stores/mainStore'
import MainView          from '@/components/MainView.vue'
import MainCarousel2     from '@/components/MainCarousel2.vue'

// Eager — always needed
import HomePage    from '@/components/HomePage.vue'
import LoginForm   from '@/components/LoginForm.vue'
import RegisterForm from '@/components/RegisterForm.vue'
import SettingsView from '@/components/SettingsView.vue'

// Your existing carousel slides (school project components — swap for TastyByti later)
import Cbt     from '@/components/school/Cbt.vue'
import Staff   from '@/components/school/Staff.vue'
import Grade   from '@/components/school/Grade.vue'
import MyClass from '@/components/school/MyClass.vue'

// Lazy — staff components only downloaded when needed
// const KitchenScreen  = defineAsyncComponent(() => import('@/components/staff/KitchenScreen.vue'))
// const AllOrders      = defineAsyncComponent(() => import('@/components/staff/AllOrders.vue'))
// const Analytics      = defineAsyncComponent(() => import('@/components/staff/Analytics.vue'))
// const ProductManager = defineAsyncComponent(() => import('@/components/staff/ProductManager.vue'))

const auth  = useAuthStore()
const store = useMainStore()

provide('accordionStore', store)

// ─── Carousel slides — what lives INSIDE the Home section ─────────────────────
// These are swipeable with MainCarousel2
const slides = [
  { component: HomePage, name: 'Home'     },
  { component: MyClass,  name: 'My Class' },
  { component: Staff,    name: 'Staff'    },
  { component: Grade,    name: 'Grade'    },
  { component: Cbt,      name: 'CBT'      },
]
// s
const mainView = ref(null)
const switchTo = (key) => {
  mainView.value?.switchTo(key)
}

provide('switchTo', switchTo)

// ─── Nav items — top level MainView sections, completely separate from slides ──
const navItems = computed(() => {
  const items = [
    { key: 'Home', label: 'Home', icon: '🏠' },
  ]

  if (!auth.isLoggedIn) {
    items.push(
      { key: 'Login',    label: 'Login',    icon: '🔑' },
      { key: 'Register', label: 'Register', icon: '📝' },
      { key: 'Settings', label: 'Settings', icon: '⚙️' },
    )
  }

  if (auth.isLoggedIn) {
    items.push({ key: 'Logout', label: 'Logout', icon: '🚪', action: true })
  }

 
  return items
})

const handleAction = async (key) => {
  if (key === 'Logout') {
    await auth.logout()
    
  }
}
</script>

<template>
  <!-- Loading — while me() resolves -->
  <div
    v-if="!auth.checked"
    class="w-full h-full flex items-center justify-center bg-neutral-900"
  >
    <span class="text-primary-300 text-sm animate-pulse">Loading...</span>
  </div>

  <!-- App shell -->
  <MainView ref="mainView" v-else :nav-items="navItems"  @action="handleAction">

    <!-- Home section contains the carousel -->
    <template #Home>
      <MainCarousel2
        :slides="slides"
        carousel-id="main"
      />
    </template>

    <!-- Guest sections -->
    <template #Login><LoginForm /> </template>
    <template #Register><RegisterForm /> </template>
   <template #Settings><SettingsView /></template>
   
  </MainView>
</template>