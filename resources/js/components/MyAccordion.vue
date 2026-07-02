<script setup>
import { ref, computed, nextTick, inject, watchEffect  } from 'vue'
import { useBreakpoints, breakpointsTailwind, onLongPress } from '@vueuse/core'
import { gsap } from 'gsap'

import {CheckIcon, ChevronRightIcon} from '@heroicons/vue/24/outline'

const props = defineProps({
  items: { type: Array, required: true },
  keyField: { type: String, default: 'id' },
  displayConfig: { type: Object, required: true },
  tabs: { type: Array, required: true, default: ()=>[] }
})

const store = inject('accordionStore', {
  attendanceMode: false,
  selectionMode: false
})

const openItemId    = ref(null)
const selectedItem  = ref(null)
const breakpoints   = useBreakpoints(breakpointsTailwind)
const isMobile      = breakpoints.smaller('md')
const listContainer = ref(null)
const itemRefs      = {}
const emptyPanel    = ref(null)
const profilePanel  = ref(null)
const activeTab     = ref(null)
const viewType      = ref('list')
const hoverTimers   = ref({})
const showProfile   = ref(false)

const allSelected = computed(() =>
  store.selectedItems.size === props.items.length && props.items.length > 0
)

const defaultTab = computed(() => props.tabs[0].key)

watchEffect(() => {
  if (!activeTab.value && props.tabs?.length) {
    activeTab.value = props.tabs[0].key
  }
})

const closePanel = () => {
  selectedItem.value = null
  showProfile.value  = false
  activeTab.value    = defaultTab.value
}

const closeProfile = async () => {
  if (profilePanel.value) {
    gsap.to(profilePanel.value, {
      opacity: 0, scale: 0.97, y: 10,
      duration: 0.25, ease: 'power2.in',
      onComplete: () => closePanel()
    })
  }
}

const setItemRef = (el, itemId, item) => {
  if (el && !itemRefs[itemId]) {
    itemRefs[itemId] = el
    onLongPress(el, () => {
      store.selectionMode = true
      store.toggleSelection(item, props.keyField)
    }, { delay: 500 })
  }
}

const toggleAttendanceMode = () => {
  if (store.attendanceMode) {
    store.attendanceMode = false
    store.selectionMode  = false
    store.selectedItems.clear()
  } else {
    store.selectedItems.clear()
    store.attendanceMode = true
    store.selectionMode  = true
  }
}

const selectItem = async (item) => {
  if (selectedItem.value) {
    selectedItem.value = item
    if (activeTab.value === defaultTab.value) {
      showProfile.value = true
    }
    return
  }

  const emptyEl = emptyPanel.value
  if (emptyEl) {
    gsap.to(emptyEl, { opacity: 0, scale: 0.95, duration: 0.25, ease: 'power2.in' })
  }

  setTimeout(async () => {
    selectedItem.value = item
    await nextTick()
    if (profilePanel.value) {
      gsap.fromTo(profilePanel.value,
        { opacity: 0, scale: 0.97, y: 10 },
        { opacity: 1, scale: 1, y: 0, duration: 0.4, ease: 'power3.out' }
      )
    }
  }, 150)
}

const toggleAccordion = async (item) => {
  const itemId = item[props.keyField]

  if (store.selectionMode) {
    store.toggleSelection(item, props.keyField)
    return
  }

  if (!isMobile.value) {
    selectItem(item)
    return
  }

  if (openItemId.value === itemId) {
    openItemId.value = null
    return
  }

  if (openItemId.value !== null) {
    openItemId.value = null
    await new Promise(resolve => setTimeout(resolve, 520))
  }

  openItemId.value = itemId
  await nextTick()

  const itemEl    = itemRefs[itemId]
  const container = listContainer.value
  if (itemEl && container) {
    const containerTop = container.getBoundingClientRect().top
    const itemTop      = itemEl.getBoundingClientRect().top
    const scrollOffset = container.scrollTop + (itemTop - containerTop)
    gsap.to(container, { scrollTop: scrollOffset, duration: 0.5, ease: 'power3.out' })
  }
}

const toggleSelection = (item) => {
  store.selectionMode = true
  store.toggleSelection(item, props.keyField)
}

const handleThumbnailMouseEnter = (itemId) => {
  if (!store.selectionMode) {
    hoverTimers.value[itemId] = setTimeout(() => {}, 1000)
  }
}

const handleThumbnailMouseLeave = (itemId) => {
  if (hoverTimers.value[itemId]) {
    clearTimeout(hoverTimers.value[itemId])
    delete hoverTimers.value[itemId]
  }
}

const toggleSelectAll = () => {
  if (allSelected.value) {
    store.selectedItems.clear()
    store.selectionMode = false
  } else {
    props.items.forEach(item => {
      store.selectedItems.set(item[props.keyField], item)
    })
  }
}

const getDisplayValue = (item, configKey) => {
  const config = props.displayConfig[configKey]
  return typeof config === 'function' ? config(item) : item[config]
}

const onCheckboxEnter = (el) =>
  gsap.from(el, { scale: 0, opacity: 0, duration: 0.2, ease: 'back.out(1.7)' })

const onCheckboxLeave = (el, done) =>
  gsap.to(el, { scale: 0, opacity: 0, duration: 0.15, ease: 'power2.in', onComplete: done })

const onContentEnter = (el) =>
  gsap.from(el, {
    height: 0, opacity: 0, duration: 0.6, ease: 'power2.out',
    onComplete: () => gsap.set(el, { height: 'auto' })
  })

const onContentLeave = (el, done) =>
  gsap.to(el, { height: 0, opacity: 0, duration: 0.5, ease: 'power2.in', onComplete: done })
</script>

<template>
  <div class="flex flex-col md:flex-row w-full h-full bg-surface-300">

    <!-- ═══════════════════════════════════════
         LEFT PANEL
    ════════════════════════════════════════ -->
    <div
      class="flex flex-col w-full h-full flex-1 md:flex-none md:border-r md:border-neutral-300 transition-all duration-300"
      :class="viewType === 'fullview' ? 'md:w-full' : 'md:w-[30%]'"
    >

      <!-- NAV / SELECTION HEADER -->
      <div
        class="flex items-center justify-between px-3 py-2 sm:px-4 sm:py-3 shrink-0 z-10
        transition-colors duration-300 h-[56px] sm:h-[64px]"
        :class="store.attendanceMode ? 'bg-danger' : 'bg-primary'"
      >

        <!-- LEFT: Select All -->
        <div class="flex items-center gap-2 min-w-[80px]">
          <Transition name="fade">
            <button
              v-if="store.selectionMode"
              @click="toggleSelectAll"
              class="flex items-center gap-2 bg-transparent border-none text-white text-sm
              cursor-pointer px-2 py-1 rounded active:bg-white/20 transition-colors
              touch-manipulation min-h-[44px]"
            >
              <span
                class="w-5 h-5 sm:w-6 sm:h-6 border-2 border-white rounded-full flex items-center
                justify-center transition-all shrink-0"
                :class="allSelected ? 'bg-white text-primary' : ''"
              >
                <CheckIcon v-if="allSelected" class="w-3 h-3 sm:w-4 sm:h-4 stroke-[3]" />
              </span>
              <span class="hidden sm:inline text-white/90 text-xs font-medium">
                {{ allSelected ? 'None' : 'All' }}
              </span>
            </button>
          </Transition>

          <!-- Count badge -->
          <Transition name="fade">
            <span
              v-if="store.selectionMode && store.selectedItems.size > 0"
              class="text-xs sm:text-sm font-semibold text-white/90 whitespace-nowrap"
            >
              {{ store.selectedItems.size }}{{ store.attendanceMode ? ' Absent' : '' }}
            </span>
          </Transition>
        </div>

        <!-- CENTER: View toggle -->
        <div class="min-w-[80px] flex justify-end">
          <button
            @click="viewType = viewType === 'list' ? 'fullview' : 'list'"
            class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs sm:text-sm
            font-medium transition-all touch-manipulation min-h-[36px] border"
            :class="store.attendanceMode
              ? 'bg-white text-danger border-white'
              : 'bg-white/15 text-white border-white/30 hover:bg-white/25'"
          >
            <span>{{ viewType === 'list' ? 'Full View' : 'Mini View' }}</span>
          </button>
        </div>

        <!-- RIGHT: Attendance toggle -->
        <div class="min-w-[80px] flex justify-end">
          <button
            @click="toggleAttendanceMode"
            class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs sm:text-sm
            font-medium transition-all touch-manipulation min-h-[36px] border"
            :class="store.attendanceMode
              ? 'bg-white text-danger border-white'
              : 'bg-white/15 text-white border-white/30 hover:bg-white/25'"
          >
            <span class="hidden sm:inline">
              {{ store.attendanceMode ? 'Cancel' : 'Take Attendance' }}
            </span>
          </button>
        </div>

      </div>
      <!-- END NAV HEADER -->


      <!-- LIST VIEW -->
      <div
        ref="listContainer"
        v-if="viewType === 'list'"
        class="overflow-y-auto overflow-x-hidden w-full"
        :class="openItemId !== null && isMobile ? 'flex-none' : 'flex-1'"
      >
        <div
          v-for="item in items"
          :key="item[keyField]"
          :ref="el => { if(el) itemRefs[item[keyField]] = el }"
          class="border-b border-neutral-300"
          :class="openItemId === item[keyField] && isMobile ? 'flex flex-col h-full' : ''"
        >

          <!-- List Item Row -->
          <div class="flex items-center hover:bg-surface-300 transition-colors">

            <!-- Checkbox -->
            <Transition @enter="onCheckboxEnter" @leave="onCheckboxLeave">
              <div
                v-if="store.selectionMode"
                class="px-2 sm:px-3 flex items-center cursor-pointer touch-manipulation min-h-[48px]"
                @click="store.toggleSelection(item, keyField)"
              >
                <span
                  class="w-5 h-5 sm:w-6 sm:h-6 border-2 border-accent rounded-full flex items-center
                  justify-center text-xs sm:text-sm text-white transition-all"
                  :class="store.selectedItems.has(item[keyField]) ? 'bg-accent' : ''"
                >
                  <CheckIcon v-if="store.selectedItems.has(item[keyField])" class="w-3 h-3 sm:h-4 stroke-[3]" />
                </span>
              </div>
            </Transition>

            <!-- Avatar/Thumbnail -->
            <div
              class="w-11 h-11 sm:w-12 sm:h-12 md:w-14 md:h-14 rounded-full bg-neutral-300
              flex items-center justify-center text-lg sm:text-xl md:text-2xl my-2 mx-2
              sm:mx-3 shrink-0 cursor-pointer select-none touch-manipulation active:scale-95
              transition-transform"
              :ref="el => setItemRef(el, item[keyField], item)"
              @click="toggleSelection(item)"
              @mouseenter="handleThumbnailMouseEnter(item[keyField])"
              @mouseleave="handleThumbnailMouseLeave(item[keyField])"
            >
              {{ getDisplayValue(item, 'avatar') }}
            </div>

            <!-- Main content button -->
            <button
              class="flex-1 flex items-center justify-between py-2 sm:py-3 pr-3 sm:pr-4
              bg-transparent border-none cursor-pointer text-left min-w-0
              touch-manipulation min-h-[48px]"
              :ref="el => setItemRef(el, 'btn-' + item[keyField], item)"
              @click="toggleAccordion(item)"
            >
              <div class="flex-1 min-w-0 pr-2">
                <h3 class="text-sm sm:text-base md:text-lg font-medium m-0 mb-0.5 sm:mb-1
                text-neutral-900 leading-tight">
                  {{ getDisplayValue(item, 'title') }}
                </h3>
                <p class="text-xs sm:text-sm md:text-base text-neutral-500 m-0 whitespace-nowrap
                overflow-hidden text-ellipsis leading-tight">
                  {{ getDisplayValue(item, 'subtitle') }}
                </p>
              </div>
              <div class="flex flex-col items-end gap-0.5 sm:gap-1 shrink-0">
                <span class="text-[10px] sm:text-xs md:text-sm text-neutral-500 whitespace-nowrap">
                  {{ getDisplayValue(item, 'timestamp') }}
                </span>
                <ChevronRightIcon
                  class="w-4 h-4 sm:w-5 sm:h-5 text-neutral-500 transition-transform duration-500 ease-out"
                  :class="openItemId === item[keyField] ? 'rotate-90' : ''"
                />
              </div>
            </button>

          </div>
          <!-- END List Item Row -->

          <!-- ACCORDION BODY (Mobile) -->
          <Transition @enter="onContentEnter" @leave="onContentLeave" :css="false">
            <div
              v-if="openItemId === item[keyField] && isMobile"
              class="flex-1 overflow-y-auto overflow-x-hidden min-h-0"
            >
              <slot name="accordion-body" :item="item" :close="() => openItemId = null" />
            </div>
          </Transition>

        </div>
      </div>
      <!-- END LIST VIEW -->


      <!-- FULL VIEW -->
      <div v-if="viewType === 'fullview'" class="flex-1 overflow-y-auto overflow-x-hidden w-full">
        <slot name="fullView" :items="items" />
      </div>
      <!-- END FULL VIEW -->

    </div>
    <!-- END LEFT PANEL -->


    <!-- ═══════════════════════════════════════
         RIGHT PANEL (PC only)
    ════════════════════════════════════════ -->
    <div
      v-if="!isMobile"
      class="hidden md:flex md:flex-col md:w-[70%] bg-surface-300 overflow-hidden"
    >

      <!-- Right nav bar -->
      <div
        class="flex items-center justify-between px-3 py-2 sm:px-4 sm:py-3 shrink-0 z-10
        transition-colors duration-300 h-[56px] sm:h-[64px] bg-primary"
      >
        <div class="flex items-center gap-2">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            @click="activeTab = tab.key; showProfile = false"
            class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs sm:text-sm
            font-medium transition-all touch-manipulation min-h-[36px] border"
            :class="activeTab === tab.key
              ? 'bg-white text-primary border-white'
              : 'bg-white/15 text-white border-white/30 hover:bg-white/25'"
          >
            <span v-if="tab.icon">{{ tab.icon }}</span>
            {{ tab.label }}
          </button>
        </div>
      </div>
      <!-- END Right nav bar -->

      <!-- Profile panel -->
      <div
        ref="profilePanel"
        v-if="showProfile"
        class="flex flex-col h-full overflow-hidden"
      >
        <slot name="profile" :item="selectedItem" :close="closeProfile" />
      </div>

      <!-- Tab slots -->
      <div v-show="!showProfile" class="flex flex-col flex-1 overflow-hidden">
        <slot
          :name="activeTab"
          :item="selectedItem"
          :key="activeTab"
          :close="closePanel"
        />
      </div>

    </div>
    <!-- END RIGHT PANEL -->

  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from,  .fade-leave-to      { opacity: 0; }

.overflow-y-auto::-webkit-scrollbar       { width: 3px; }
.overflow-y-auto::-webkit-scrollbar-track { background: transparent; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: var(--neutral-300); border-radius: 2px; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: var(--neutral-500); }

@media (min-width: 640px) {
  .overflow-y-auto::-webkit-scrollbar { width: 4px; }
}
</style>
