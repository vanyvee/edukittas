<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number], default: null },
  tabs:  { type: Array, required: true },            // [{ id, label, icon? }]
  swipe: { type: Boolean, default: true },           // swipe the panel to change tab
})
const emit = defineEmits(['update:modelValue'])

const activeIndex = computed(() => Math.max(0, props.tabs.findIndex(t => t.id === props.modelValue)))
const els      = ref([])
const track    = ref(null)
const ind      = ref({ left: 0, width: 0 })
const ready    = ref(false)
const morphing = ref(false)
let ro, timer

function measure() {
  const el = els.value[activeIndex.value]
  if (!el) return
  ind.value = { left: el.offsetLeft, width: el.offsetWidth }
}

function select(i) {
  const t = props.tabs[i]
  if (!t || t.id === props.modelValue) return
  emit('update:modelValue', t.id)
}

watch(activeIndex, () => {
  nextTick(() => {
    measure()
    morphing.value = false
    requestAnimationFrame(() => {
      morphing.value = true
      clearTimeout(timer)
      timer = setTimeout(() => (morphing.value = false), 650)
    })
  })
})

// tabs may change when the style/theme changes label widths
watch(() => props.tabs, () => nextTick(measure), { deep: true })

onMounted(() => {
  measure()
  requestAnimationFrame(() => (ready.value = true))   // no slide-in from 0 on first paint
  if (window.ResizeObserver && track.value) {
    ro = new ResizeObserver(measure)
    ro.observe(track.value)
  }
})
onBeforeUnmount(() => { clearTimeout(timer); ro && ro.disconnect() })

function onKey(e) {
  const n = props.tabs.length
  if (e.key === 'ArrowRight') { select((activeIndex.value + 1) % n); e.preventDefault() }
  if (e.key === 'ArrowLeft')  { select((activeIndex.value - 1 + n) % n); e.preventDefault() }
}

let sx = 0, sy = 0
function touchStart(e) { const t = e.changedTouches[0]; sx = t.clientX; sy = t.clientY }
function touchEnd(e) {
  if (!props.swipe) return
  const t = e.changedTouches[0], dx = t.clientX - sx, dy = t.clientY - sy
  if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy) * 1.25) select(activeIndex.value + (dx < 0 ? 1 : -1))
}
</script>

<template>
  <div class="ek-tabs">
    <div ref="track" class="ek-tabs__track" role="tablist" @keydown="onKey">
      <div
        class="ek-tabs__indicator"
        :class="{ 'is-ready': ready, morph: morphing }"
        :style="{ left: ind.left + 'px', width: ind.width + 'px' }"
      ></div>
      <button
        v-for="(t, i) in tabs"
        :key="t.id"
        :ref="el => (els[i] = el)"
        type="button"
        role="tab"
        class="ek-tab"
        :aria-selected="t.id === modelValue"
        :tabindex="t.id === modelValue ? 0 : -1"
        @click="select(i)"
      >
        <span v-if="t.icon" aria-hidden="true">{{ t.icon }}</span>
        <span>{{ t.label }}</span>
      </button>
    </div>

    <div v-if="$slots.default" class="ek-tabs__panel" role="tabpanel" @touchstart="touchStart" @touchend="touchEnd">
      <slot :active="modelValue" :tab="tabs[activeIndex]" />
    </div>
  </div>
</template>
