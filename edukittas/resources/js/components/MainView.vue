<script setup>
import { ref, computed, watchEffect, nextTick, onMounted} from 'vue'
import { gsap } from 'gsap'
import { ChevronRightIcon } from '@heroicons/vue/24/outline'

// Props — same pattern as MyAccordion tabs prop
const props = defineProps({
  navItems: {
    type: Array,
    required: true,
    // shape: [{ key: 'dashboard', label: 'Dashboard', icon: '📊' }, ...]
    // first item is always the default
  }
})

const emit = defineEmits(['action'])

// State
const isHovered       = ref(false)
const activeKey       = ref(null)
const contentPanel    = ref(null)
const navRef          = ref(null)

// Derived
const defaultKey = computed(() => props.navItems[0]?.key)

// Set default on mount
watchEffect(() => {
  if (!activeKey.value && props.navItems?.length) {
    activeKey.value = props.navItems[0].key
  }
})

// Switch slot with GSAP animation
const switchTo = async (key) => {
  if (key === activeKey.value) return

  if (contentPanel.value) {
    await gsap.to(contentPanel.value, {
      opacity: 0,
      x: -12,
      duration: 0.18,
      ease: 'power2.in',
    })
  }

  activeKey.value = key
  await nextTick()

  if (contentPanel.value) {
    gsap.fromTo(
      contentPanel.value,
      { opacity: 0, x: 16 },
      { opacity: 1, x: 0, duration: 0.28, ease: 'power2.out' }
    )
  }
}

// Nav hover — slide in/out
const onNavEnter = () => {
  isHovered.value = true
  gsap.to(navRef.value, {
    x: 0,
    duration: 0.35,
    ease: 'power3.out',
  })
}

const onNavLeave = () => {
  isHovered.value = false
  gsap.to(navRef.value, {
    x: '-82%',
    duration: 0.3,
    ease: 'power2.in',
  })
}

defineExpose({ switchTo })

// Mount — set initial pushed-off state

onMounted(() => {
  gsap.set(navRef.value, { x: '-82%' })
})
</script>

<template>
  <div class="relative w-full h-full flex overflow-hidden ">

    <!-- ─── FLOATING LEFT NAV ─────────────────────────────── -->

      <div class=" z-20 absolute flex flex-col h-full w-44 bg-[#075e54]/90 shadow-2xl 
        border-t border-r border-b rounded-tr-xl rounded-br-xl border-white"
          @mouseenter="onNavEnter"
          @mouseleave="onNavLeave"
        ref="navRef">

        <!-- Top label -->
        <div class="px-4 py-3 border-b border-white/10 shrink-0">
          <span class="text-[10px] font-semibold tracking-widest text-white/40 uppercase">
            Menu
          </span>
        </div>
        




        <!-- Nav buttons — styled exactly like attendance button -->
        <div class="flex flex-col gap-1.5 p-2 flex-1 overflow-y-auto">
          <button
            v-for="navItem in navItems"
            :key="navItem.key"
            @click="navItem.action ? emit('action', navItem.key) : switchTo(navItem.key)"
            class="flex items-center gap-2.5 w-full px-3 py-2.5 rounded-lg text-sm
            font-medium transition-all touch-manipulation border text-left"
            :class="activeKey === navItem.key
              ? 'bg-white text-[#075e54] border-white'
              : 'bg-white/10 text-white border-white/20 hover:bg-white/20'"
          >
            <span v-if="navItem.icon" class="text-base shrink-0">{{ navItem.icon }}</span>
            <span class="flex-1 truncate">{{ navItem.label }}</span>
            <!-- active indicator -->
            <ChevronRightIcon
              v-if="activeKey === navItem.key"
              class="w-3.5 h-3.5 shrink-0 text-[#075e54]"
            />
          </button>
        </div>

      </div>

      <!-- Visible tab — the 18% that sticks out -->
      <!-- <div class="flex flex-col justify-center gap-2 py-4 px-1
        bg-[#075e54] border-l border-white/10 rounded-r-xl shadow-lg">
       
      </div> -->
    <!-- </div> -->

    <!-- ─── CONTENT AREA ───────────────────────────────────── -->
    <div class="flex-1 flex flex-col h-full overflow-hidden pl-7">

      <!-- Content panel — switches by activeKey -->
      <div ref="contentPanel" class="flex-1 overflow-y-auto overflow-x-hidden">
        <template v-for="navItem in navItems" :key="navItem.key">
          <div
            v-show="activeKey === navItem.key"
            class="w-full h-full"
          >
            <slot :name="navItem.key" />
          </div>
        </template>
      </div>

    </div>

  </div>
</template>

<style scoped>
/* Scrollbar — same as MyAccordion */
.overflow-y-auto::-webkit-scrollbar { width:15px; }
.overflow-y-auto::-webkit-scrollbar-track { background: transparent; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: rgb(253, 77, 45); border-radius: 2px; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #a0a0a0; }
</style>
