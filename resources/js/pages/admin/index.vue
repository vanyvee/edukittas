   <script setup>
   import { ref, provide } from 'vue'
   import { useAuthStore } from '@/stores/auth'
   import { useMainStore } from '@/stores/mainStore'
   import MainView from '@/components/MainView.vue'
   import MainCarousel2 from '@/components/MainCarousel2.vue'
   import AdminHome from '@/components/admin/AdminHome.vue'

   const auth = useAuthStore()
   const store = useMainStore()
   provide('accordionStore', store)

   const slides = [{ component: AdminHome, name: 'Home' }]

   const mainView = ref(null)
   const switchTo = (key) => mainView.value?.switchTo(key)
   provide('switchTo', switchTo)

   const navItems = [{ key: 'Home', label: 'Home', icon: '🏠' }]
   </script>

   <template>
     <div v-if="!auth.checked" class="w-full h-full flex items-center justify-center bg-neutral-900">
       <span class="text-primary-300 text-sm animate-pulse">Loading...</span>
     </div>

     <MainView ref="mainView" v-else :nav-items="navItems">
       <template #Home>
         <MainCarousel2 :slides="slides" carousel-id="admin" />
       </template>
     </MainView>
   </template>