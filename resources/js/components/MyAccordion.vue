<script setup>
/**
 * MyAccordion — master/detail list.
 *   Desktop (>= md): list on the left, detail panel with tabs on the right.
 *   Phone:           the row opens downward.
 *
 * STORE  (selection checkboxes, attendance mode, selectedItems)
 *   1. the `store` prop, if you pass one            <MyAccordion :store="classStore" ...>
 *      (pass :store="false" to switch selection off even when a parent provides a store)
 *   2. otherwise the `accordionStore` a parent provided (what Cbt/Staff/MyClass do today)
 *   3. otherwise none: selection is simply off (no checkboxes, no long-press).
 *   The store needs: selectionMode, attendanceMode, selectedItems (Map), toggleSelection(item, keyField).
 *
 * SLOTS (all optional)
 *   nav-header       top of the list. No default: use <AccordionDefaultHeader v-bind="h" /> to get the old one.
 *   row              the whole row.      row-avatar / row-main / row-trailing replace one part of it.
 *   accordion-body   phone: content under an opened row.
 *   <tab key>        desktop: content of the active tab.
 *   detail           fallback for BOTH of the above (and the only one needed when there are no tabs).
 *   right-nav        the tab bar. Pass any element to replace it (an empty slot falls back to the default).
 *   right-empty      desktop, shown while nothing is selected.
 *   profile          desktop, shown when another row is picked while on the first tab.
 *   fullView         the whole-list view.
 *
 * LOOK   :row-height="'compact'|'comfortable'|'spacious"  :stagger="ms between rows"
 *        Both default to the person's own saved preference (stores/accordionPrefs).
 */
import { ref, computed, nextTick, inject, watch, watchEffect, onMounted } from 'vue'
import { useBreakpoints, breakpointsTailwind, onLongPress } from '@vueuse/core'
import { gsap } from 'gsap'
import { CheckIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'
import { useAccordionPrefsStore } from '@/stores/accordionPrefs'

const props = defineProps({
  items:         { type: Array,  required: true },
  keyField:      { type: String, default: 'id' },
  displayConfig: { type: Object, default: () => ({}) },
  tabs:          { type: Array,  default: () => [] },
  store:         { type: [Object, Boolean], default: null },   // false = no store, selection off
  rowHeight:     { type: String, default: null },
  stagger:       { type: Number, default: null },
})

// ─── Store: prop -> injected -> none ────────────────────────────────────
const injectedStore = inject('accordionStore', null)
const st         = computed(() => (props.store === false ? null : props.store ?? injectedStore ?? null))
const selectable = computed(() => typeof st.value?.toggleSelection === 'function')
const selMode    = computed(() => selectable.value && !!st.value.selectionMode)
const attMode    = computed(() => !!st.value?.attendanceMode)
const selCount   = computed(() => (selectable.value ? st.value.selectedItems.size : 0))
const isSelected = (item) => selectable.value && st.value.selectedItems.has(item[props.keyField])
const allSelected = computed(() => selectable.value && props.items.length > 0 && selCount.value === props.items.length)

// ─── Look: density + stagger (props win over the person's saved preference) ─
const prefs = useAccordionPrefsStore()
const DENSITY = {
  compact:     { row: 'min-h-[40px]', avatar: 'w-9 h-9 sm:w-10 sm:h-10 md:w-11 md:h-11 text-base' },
  comfortable: { row: 'min-h-[48px]', avatar: 'w-11 h-11 sm:w-12 sm:h-12 md:w-14 md:h-14 text-lg sm:text-xl md:text-2xl' },
  spacious:    { row: 'min-h-[64px]', avatar: 'w-14 h-14 sm:w-16 sm:h-16 md:w-[4.5rem] md:h-[4.5rem] text-2xl' },
}
const dens     = computed(() => DENSITY[props.rowHeight ?? prefs.rowHeight] ?? DENSITY.comfortable)
const staggerMs = computed(() => props.stagger ?? prefs.stagger)

// ─── State ──────────────────────────────────────────────────────────────
const openItemId    = ref(null)
const selectedItem  = ref(null)
const breakpoints   = useBreakpoints(breakpointsTailwind)
const isMobile      = breakpoints.smaller('md')
const listContainer = ref(null)
const itemRefs      = {}
const pressBound    = new Set()
const emptyPanel    = ref(null)
const profilePanel  = ref(null)
const activeTab     = ref(null)
const viewType      = ref('list')
const showProfile   = ref(false)

// No tabs -> the right panel shows the `detail` slot.
const defaultTab = computed(() => props.tabs[0]?.key ?? 'detail')

watchEffect(() => {
  if (!activeTab.value) activeTab.value = defaultTab.value
})

const setTab = (key) => {
  activeTab.value = key
  showProfile.value = false
}

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

// Long-press on the avatar starts selection. Own key set, so it can never collide with the row refs.
const bindPress = (el, key, item) => {
  if (!el || pressBound.has(key) || !selectable.value) return
  pressBound.add(key)
  onLongPress(el, () => {
    st.value.selectionMode = true
    st.value.toggleSelection(item, props.keyField)
  }, { delay: 500 })
}

const toggleAttendanceMode = () => {
  if (!selectable.value) return
  if (st.value.attendanceMode) {
    st.value.attendanceMode = false
    st.value.selectionMode  = false
    st.value.selectedItems.clear()
  } else {
    st.value.selectedItems.clear()
    st.value.attendanceMode = true
    st.value.selectionMode  = true
  }
}

const toggleView = () => {
  viewType.value = viewType.value === 'list' ? 'fullview' : 'list'
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

const toggleSelectionOf = (item) => {
  if (!selectable.value) return
  st.value.toggleSelection(item, props.keyField)
}

const startSelection = (item) => {
  if (!selectable.value) return
  st.value.selectionMode = true
  st.value.toggleSelection(item, props.keyField)
}

const toggleAccordion = async (item) => {
  const itemId = item[props.keyField]

  if (selMode.value) {
    toggleSelectionOf(item)
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

const toggleSelectAll = () => {
  if (!selectable.value) return
  if (allSelected.value) {
    st.value.selectedItems.clear()
    st.value.selectionMode = false
  } else {
    props.items.forEach(item => {
      st.value.selectedItems.set(item[props.keyField], item)
    })
  }
}

const getDisplayValue = (item, configKey) => {
  const config = props.displayConfig?.[configKey]
  return typeof config === 'function' ? config(item) : config == null ? '' : item[config]
}
const hasField = (key) => props.displayConfig?.[key] != null

// ─── Motion ─────────────────────────────────────────────────────────────
const reduceMotion = () =>
  typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches

// Rows fade/slide in one after another when the list shows or its length changes.
const playStagger = async () => {
  const ms = staggerMs.value
  if (!ms || reduceMotion()) return
  await nextTick()
  const els = props.items.map(i => itemRefs[i[props.keyField]]).filter(Boolean)
  if (!els.length) return
  gsap.fromTo(els,
    { opacity: 0, y: 14 },
    { opacity: 1, y: 0, duration: 0.4, ease: 'power2.out', stagger: ms / 1000, clearProps: 'opacity,transform', overwrite: 'auto' }
  )
}
onMounted(playStagger)
watch(() => props.items.length, playStagger)
watch(viewType, (v) => { if (v === 'list') playStagger() })

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

    <!-- ═══════════════ LEFT PANEL ═══════════════ -->
    <div
      class="flex flex-col w-full h-full flex-1 md:flex-none md:border-r md:border-neutral-300 transition-all duration-300"
      :class="viewType === 'fullview' ? 'md:w-full' : 'md:w-[30%]'"
    >

      <!-- HEADER: no default. Use <AccordionDefaultHeader v-bind="h" /> for the classic one. -->
      <slot
        name="nav-header"
        :store="st"
        :all-selected="allSelected"
        :selected-count="selCount"
        :view-type="viewType"
        :selected-item="selectedItem"
        :toggle-select-all="toggleSelectAll"
        :toggle-attendance-mode="toggleAttendanceMode"
        :toggle-view="toggleView"
      />

      <!-- LIST VIEW -->
      <div
        ref="listContainer"
        v-if="viewType === 'list'"
        class="overflow-y-auto overflow-x-hidden w-full"
        :class="openItemId !== null && isMobile ? 'flex-none' : 'flex-1'"
      >
        <slot name="list-empty" v-if="!items.length" />

        <div
          v-for="item in items"
          :key="item[keyField]"
          :ref="el => { if (el) itemRefs[item[keyField]] = el }"
          class="border-b border-neutral-300"
          :class="openItemId === item[keyField] && isMobile ? 'flex flex-col h-full' : ''"
        >

          <!-- ROW (replace it all with the `row` slot) -->
          <slot
            name="row"
            :item="item"
            :is-open="openItemId === item[keyField]"
            :is-selected="isSelected(item)"
            :selection-mode="selMode"
            :toggle="() => toggleAccordion(item)"
            :toggle-select="() => toggleSelectionOf(item)"
          >
            <div class="flex items-center hover:bg-surface-300 transition-colors">

              <!-- Checkbox -->
              <Transition @enter="onCheckboxEnter" @leave="onCheckboxLeave">
                <div
                  v-if="selMode"
                  class="px-2 sm:px-3 flex items-center cursor-pointer touch-manipulation"
                  :class="dens.row"
                  @click="toggleSelectionOf(item)"
                >
                  <span
                    class="w-5 h-5 sm:w-6 sm:h-6 border-2 border-accent rounded-full flex items-center
                    justify-center text-xs sm:text-sm text-white transition-all"
                    :class="isSelected(item) ? 'bg-accent' : ''"
                  >
                    <CheckIcon v-if="isSelected(item)" class="w-3 h-3 sm:h-4 stroke-[3]" />
                  </span>
                </div>
              </Transition>

              <!-- Avatar (shown when displayConfig.avatar exists or you fill row-avatar) -->
              <slot name="row-avatar" :item="item">
                <div
                  v-if="hasField('avatar')"
                  class="rounded-full bg-neutral-300 flex items-center justify-center my-2 mx-2
                  sm:mx-3 shrink-0 cursor-pointer select-none touch-manipulation active:scale-95
                  transition-transform"
                  :class="dens.avatar"
                  :ref="el => bindPress(el, 'av-' + item[keyField], item)"
                  @click="startSelection(item)"
                >
                  {{ getDisplayValue(item, 'avatar') }}
                </div>
              </slot>

              <!-- Main button -->
              <button
                class="flex-1 flex items-center justify-between py-2 sm:py-3 pr-3 sm:pr-4
                bg-transparent border-none cursor-pointer text-left min-w-0 touch-manipulation"
                :class="[dens.row, hasField('avatar') ? '' : 'pl-3 sm:pl-4']"
                :ref="el => bindPress(el, 'btn-' + item[keyField], item)"
                @click="toggleAccordion(item)"
              >
                <div class="flex-1 min-w-0 pr-2">
                  <slot name="row-main" :item="item">
                    <h3 class="text-sm sm:text-base md:text-lg font-medium m-0 mb-0.5 sm:mb-1
                    text-neutral-900 leading-tight">
                      {{ getDisplayValue(item, 'title') }}
                    </h3>
                    <p class="text-xs sm:text-sm md:text-base text-neutral-500 m-0 whitespace-nowrap
                    overflow-hidden text-ellipsis leading-tight">
                      {{ getDisplayValue(item, 'subtitle') }}
                    </p>
                  </slot>
                </div>
                <div class="flex flex-col items-end gap-0.5 sm:gap-1 shrink-0">
                  <slot name="row-trailing" :item="item" :is-open="openItemId === item[keyField]">
                    <span class="text-[10px] sm:text-xs md:text-sm text-neutral-500 whitespace-nowrap">
                      {{ getDisplayValue(item, 'timestamp') }}
                    </span>
                    <ChevronRightIcon
                      class="w-4 h-4 sm:w-5 sm:h-5 text-neutral-500 transition-transform duration-500 ease-out"
                      :class="openItemId === item[keyField] ? 'rotate-90' : ''"
                    />
                  </slot>
                </div>
              </button>

            </div>
          </slot>
          <!-- END ROW -->

          <!-- ACCORDION BODY (phone) -->
          <Transition @enter="onContentEnter" @leave="onContentLeave" :css="false">
            <div
              v-if="openItemId === item[keyField] && isMobile"
              class="flex-1 overflow-y-auto overflow-x-hidden min-h-0"
            >
              <slot name="accordion-body" :item="item" :close="() => openItemId = null">
                <slot name="detail" :item="item" :close="() => openItemId = null" />
              </slot>
            </div>
          </Transition>

        </div>
      </div>
      <!-- END LIST VIEW -->

      <!-- FULL VIEW -->
      <div v-if="viewType === 'fullview'" class="flex-1 overflow-y-auto overflow-x-hidden w-full">
        <slot name="fullView" :items="items" />
      </div>

    </div>
    <!-- END LEFT PANEL -->


    <!-- ═══════════════ RIGHT PANEL (desktop only) ═══════════════ -->
    <div
      v-if="!isMobile"
      class="hidden md:flex md:flex-col md:w-[70%] bg-surface-300 overflow-hidden"
    >

      <!-- Tab bar: the default shows whenever there are tabs. Replace it with the right-nav slot. -->
      <slot name="right-nav" :tabs="tabs" :active-tab="activeTab" :set-tab="setTab" :item="selectedItem">
        <div
          v-if="tabs.length"
          class="flex items-center justify-between px-3 py-2 sm:px-4 sm:py-3 shrink-0 z-10
          transition-colors duration-300 h-[56px] sm:h-[64px] bg-primary"
        >
          <div class="flex items-center gap-2">
            <button
              v-for="tab in tabs"
              :key="tab.key"
              @click="setTab(tab.key)"
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
      </slot>

      <!-- Profile panel -->
      <div
        ref="profilePanel"
        v-if="showProfile"
        class="flex flex-col h-full overflow-hidden"
      >
        <slot name="profile" :item="selectedItem" :close="closeProfile" />
      </div>

      <!-- Nothing picked yet (only when you provide right-empty) -->
      <div
        v-if="!selectedItem && $slots['right-empty']"
        ref="emptyPanel"
        class="flex flex-col flex-1 overflow-hidden"
      >
        <slot name="right-empty" />
      </div>

      <!-- Tab content (falls back to the detail slot) -->
      <div v-else v-show="!showProfile" class="flex flex-col flex-1 overflow-hidden">
        <slot :name="activeTab" :item="selectedItem" :key="activeTab" :close="closePanel">
          <slot name="detail" :item="selectedItem" :close="closePanel" />
        </slot>
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
