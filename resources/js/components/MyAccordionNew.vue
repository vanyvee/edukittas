<script setup>
import { ref, computed, nextTick, inject, watchEffect } from 'vue'
import { useBreakpoints, breakpointsTailwind, onLongPress } from '@vueuse/core'
import { gsap } from 'gsap'
import { CheckIcon, ChevronRightIcon, ClipboardDocumentCheckIcon } from '@heroicons/vue/24/outline'

// ─── Props ───────────────────────────────────────────────────────────────────
const props = defineProps({
  items:         { type: Array,  required: true },
  keyField:      { type: String, default: 'id' },
  displayConfig: { type: Object, required: true },
  tabs:          { type: Array,  required: true, default: ()=>[] },
  mode: {
    type: String , default: "split"
  }
})

// ─── Store (injected from parent) ────────────────────────────────────────────
const store = inject('accordionStore', {
  attendanceMode: false,
  selectionMode: false,
  selectedItems: new Map(),
  toggleSelection: ()=>{},
})

// ─── Local State ─────────────────────────────────────────────────────────────
const openItemId    = ref(null)
const selectedItem  = ref(null)
const showProfile   = ref(false)
const activeTab     = ref(null)
const viewType      = ref('list')
const listContainer = ref(null)
const emptyPanel    = ref(null)
const profilePanel  = ref(null)
const hoverTimers   = ref({})
const itemRefs      = {}

const breakpoints = useBreakpoints(breakpointsTailwind)
const isMobile    = breakpoints.smaller('md')



// ─── Computed ─────────────────────────────────────────────────────────────────
const allSelected = computed(() =>
  store.selectedItems.size === props.items.length && props.items.length > 0
)

const defaultTab = computed(() => props.tabs[0].key)

watchEffect(() => {
  if (!activeTab.value && props.tabs?.length) {
    activeTab.value = props.tabs[0].key
  }
})

// ─── Nav bar slot props ───────────────────────────────────────────────────────
const navBarSlotProps = computed(() => ({
  store,
  allSelected:          allSelected.value,
  toggleSelectAll,
  toggleAttendanceMode,
  viewType:             viewType.value,
  setViewType:          (v) => { viewType.value = v },
  activeTab:            activeTab.value,
  setActiveTab:         (t) => { activeTab.value = t; showProfile.value = false },
  tabs:                 props.tabs,
}))

// ─── Item Refs + Long Press ───────────────────────────────────────────────────
const setItemRef = (el, itemId, item) => {
  if (el && !itemRefs[itemId]) {
    itemRefs[itemId] = el
    onLongPress(el, () => {
      store.selectionMode = true
      store.toggleSelection(item, props.keyField)
    }, { delay: 500 })
  }
}

// ─── Attendance Mode ──────────────────────────────────────────────────────────
const toggleAttendanceMode = () => {
  if (store.attendanceMode) {
    store.attendanceMode = false
    store.selectionMode  = false
    store.selectedItems.clear()
  } else {
    store.selectedItems.clear()
    store.attendanceMode = true
    store.selectionMode  = true
    viewType.value = 'list'
  }
}

// ─── Close Panel ─────────────────────────────────────────────────────────────
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
  } else {
    closePanel()
  }
}

// ─── Select Item (PC) ────────────────────────────────────────────────────────
const selectItem = async (item) => {
  // On default tab — show profile panel
  if (activeTab.value === defaultTab.value) {
    showProfile.value = true
  }

  // Profile already showing — just swap the item
  if (selectedItem.value && showProfile.value) {
    selectedItem.value = item
    return
  }

  // Animate empty panel out
  const emptyEl = emptyPanel.value
  if (emptyEl) {
    gsap.to(emptyEl, {
      opacity: 0, scale: 0.95,
      duration: 0.25, ease: 'power2.in',
    })
  }

  // Set item after short delay, animate profile in
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

// ─── Toggle Accordion ────────────────────────────────────────────────────────
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

// ─── Selection Mode ───────────────────────────────────────────────────────────
const toggleSelection = (item) => {
  store.selectionMode = true
  store.toggleSelection(item, props.keyField)
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

// ─── Hover ────────────────────────────────────────────────────────────────────
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

// ─── Display Helper ───────────────────────────────────────────────────────────
const getDisplayValue = (item, configKey) => {
  const config = props.displayConfig[configKey]
  return typeof config === 'function' ? config(item) : item[config]
}

// ─── GSAP Hooks ──────────────────────────────────────────────────────────────
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
  <div class="flex flex-col md:flex-row w-full h-full bg-white">

    <!-- ══════════════════════════════════════════
         LEFT PANEL
    ═══════════════════════════════════════════ -->
    <div
      class="flex flex-col w-full h-full flex-1 md:flex-none md:border-r md:border-[#e9edef] transition-all duration-300"
      :class=" [mode==='accordion' ? 'md:w-full' : (viewType==='fullview' ? 'md:w-full': 'md:w-[40%]')]"
    >

      <!-- NAV BAR SLOT -->
      <slot v-if="mode==='split'"  name='nav-bar' v-bind='navBarSlotProps'>

      </slot>
      
      <!-- END NAV BAR SLOT -->


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
          :ref="el => { if (el) itemRefs[item[keyField]] = el }"
          class="border-b border-[#e9edef]"
          :class="openItemId === item[keyField] && isMobile ? 'flex flex-col h-full' : ''"
        >


          <!-- List items -->    
           
          <slot
          name="list-item"
          :item="item"
          :is-open="openItemId === item[keyField]"
          :is-selected="store.selectedItems.has(item[keyField])"
          :is-selection-mode="store.selectionMode"
          :is-attendance-mode="store.attendanceMode"
          :toggle-accordion="()=>toggleAccordion(item)"
          :toggle-selection="()=>toggleSelection(item)"
          :toggle-checkbox="()=>store.toggleSelection(item, keyField)"
          :get-display-value="(key)=>getDisplayValue(item, key)"
          :item-ref="(el)=>setItemRef(el, item[keyField], item)"
          >
        
        </slot>
         

          <!-- Accordion Body (Mobile) -->
          <Transition @enter="onContentEnter" @leave="onContentLeave" :css="false">
            <div
              v-if="openItemId === item[keyField] && isMobile"
              class="flex-1 overflow-y-auto overflow-x-hidden"
            >
              <slot name="accordion-body" :item="item" :close="() => openItemId = null" />
            </div>
          </Transition>
        </div>
      </div>
      <!-- END LIST VIEW -->


      <!-- FULL VIEW SLOT -->
      <div v-if="viewType === 'fullview'" class="flex-1 overflow-y-auto overflow-x-hidden w-full">
        <slot
          name="full-view"
          :items="items"
          :selected-item="selectedItem"
          :select-item="selectItem"
        >
          <!-- Default fallback -->
          <div class="flex items-center justify-center h-full text-[#667781] text-sm p-8 text-center">
            Pass a <code>#full-view</code> slot to display content here
          </div>
        </slot>
      </div>
      <!-- END FULL VIEW SLOT -->

    </div>
    <!-- END LEFT PANEL -->


    <!-- ══════════════════════════════════════════
         RIGHT PANEL (PC only)
    ═══════════════════════════════════════════ -->
    <div
      v-if="!isMobile && mode==='split' "
      class="hidden md:flex md:flex-col md:w-[60%] bg-[#efeae2] overflow-hidden"
    >

      <!-- Right nav bar -->
      <div
        class="flex items-center justify-between px-3 py-2 sm:px-4 sm:py-3 shrink-0 z-10
        transition-colors duration-300 h-[56px] sm:h-[64px] bg-[#075e54]"
      >
        <!-- Tab buttons -->
        <div class="flex items-center gap-2">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            @click="activeTab = tab.key; showProfile = false"
            class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs sm:text-sm
            font-medium transition-all touch-manipulation min-h-[36px] border"
            :class="activeTab === tab.key
              ? 'bg-white text-[#075e54] border-white'
              : 'bg-white/15 text-white border-white/30 hover:bg-white/25'"
          >
            <span v-if="tab.icon">{{ tab.icon }}</span>
            {{ tab.label }}
          </button>
        </div>

        <!-- Back button — only when profile is visible -->
        <Transition name="fade">
          <button
            v-if="showProfile"
            @click="closeProfile"
            class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs sm:text-sm
            font-medium transition-all touch-manipulation min-h-[36px] border
            bg-white/15 text-white border-white/30 hover:bg-white/25"
          >
            <ChevronRightIcon class="w-4 h-4 rotate-180 shrink-0" />
            <span class="hidden sm:inline">Back</span>
          </button>
        </Transition>
      </div>

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
        <template v-for="tab in tabs" :key="tab.key">
          <div
            v-show="activeTab === tab.key"
            class="flex flex-col flex-1 overflow-hidden"
          >
            <slot :name="tab.key" :item="selectedItem" :close="closeProfile" />
          </div>
        </template>
      </div>

    </div>
    <!-- END RIGHT PANEL -->

  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from,  .fade-leave-to      { opacity: 0; }

.overflow-y-auto::-webkit-scrollbar         { width: 3px; }
.overflow-y-auto::-webkit-scrollbar-track   { background: transparent; }
.overflow-y-auto::-webkit-scrollbar-thumb   { background: #c4c4c4; border-radius: 2px; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #a0a0a0; }

@media (min-width: 640px) {
  .overflow-y-auto::-webkit-scrollbar { width: 4px; }
}
</style>
