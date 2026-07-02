<template>
  <div class="w-full flex flex-col relative font-inherit h-[100dvh]" :id="carouselId">
    <!-- Optional header slot -->
    <slot name="header" />

    <!-- Indicators (always visible) -->
    <div
      v-if="totalSlides > 0"
      class="flex justify-center flex-shrink-0 py-2 "
      :class="indicatorPosition === 'top' ? 'relative' : ''"
      role="tablist"
    >
      <button
        v-for="(slide, idx) in props.slides"
        :key="`ind-${idx}`"
        :class="[
          'bg-transparent border-none cursor-pointer text-lime-400 rounded-md text-sm transition-all duration-150 px-2 py-1',
          { 'bg-blue-500/12 text-teal-800 font-semibold': idx === activeIndex }
        ]"
        @click="goToSlide(idx, 'click')"
        :aria-label="`Go to slide ${idx + 1}`"
      >
        {{ slide?.name || slide?.props?.name || `Slide ${idx + 1}` }}
      </button>
    </div>

    <!-- Viewport -->
    <div
      ref="viewportRef"
      v-if="totalSlides > 0"
      class="relative overflow-hidden flex-1 w-full h-full select-none"
    >
      <!-- All slides stacked (GSAP handles visibility) -->
      <div
        v-for="(slide, sIndex) in props.slides"
        :key="`slide-${sIndex}`"
        :ref="el => slideRefs[sIndex] = el"
        class="absolute inset-0"
      >
        <!-- Eager render first 3, lazy render rest -->
        <template v-if="sIndex < eagerCount">
          <div class=" h-full  w-full">
            <component
              :is="slide.component"
              v-bind="slide.props || {}"
              class="w-full"
            />
          </div>
        </template>

        <SoftUnmount v-else :show="shouldRender(sIndex)" :linger="softLinger">
          <div class=" h-full w-full">
            <component
              :is="slide.component"
              v-bind="slide.props || {}"
              class="w-full"
            />
          </div>
        </SoftUnmount>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { useSwipe, useLocalStorage } from '@vueuse/core'
import { gsap } from 'gsap'
import SoftUnmount from '@/components/SoftUnmount.vue'
import { useMainStore } from '@/stores/mainStore'

/* ---------------- Props ---------------- */
const props = defineProps({
  slides: { type: Array, required: true },
  carouselId: { type: String, default: 'carousel' },
  persistActive: { type: Boolean, default: false },
  initialSlide: { type: Number, default: 0 },
  eagerCount: { type: Number, default: 3 },
  softLinger: { type: Number, default: 2500 },
  indicatorPosition: { type: String, default: 'bottom' },
})

/* ---------------- Refs ---------------- */
const mainStore = useMainStore()
const viewportRef = ref(null)
const slideRefs = ref([])
const activeIndex = ref(props.initialSlide)
const rendered = ref([])

/* ---------------- VueUse LocalStorage (if persistActive) ---------------- */
const storedIndex = props.persistActive 
  ? useLocalStorage(`${props.carouselId}-active`, props.initialSlide)
  : ref(props.initialSlide)

// Sync activeIndex with storage
watch(activeIndex, (v) => {
  if (props.persistActive) storedIndex.value = v
})

/* ---------------- Computed ---------------- */
const totalSlides = computed(() => props.slides?.length || 0)

const shouldRender = (index) => {
  return rendered.value.includes(index) || Math.abs(index - activeIndex.value) <= 1
}

/* ---------------- Navigation Logic ---------------- */
const goToSlide = (index, method = 'swipe') => {
  if (index === activeIndex.value || index < 0 || index >= totalSlides.value) return

  const current = slideRefs.value[activeIndex.value]
  const next = slideRefs.value[index]
  
  if (!current || !next) return

  // Track rendered slides
  if (!rendered.value.includes(index)) {
    rendered.value.push(index)
  }

  const previousIndex = activeIndex.value
  activeIndex.value = index

  if (method === 'click') {
    // FADE animation for indicator clicks
    gsap.timeline()
      .to(current, { 
        opacity: 0, 
        duration: 0.1, 
        ease: 'power2.out', 
        onComplete: () => {
          gsap.set(current, { x: '0%', clearProps: 'transform' })
        }
      })
      .set(next, { opacity: 0, zIndex: 10, x: '0%', clearProps: 'transform' })
      .to(next, { opacity: 1, duration: 0.1, ease: 'power2.in' }, '-=0.2')
      .call(() => hideAllExcept(index))
  } else {
    // SLIDE animation for swipe gestures
    const direction = index > previousIndex ? 1 : -1
    const distance = direction * 100

    gsap.timeline()
      .set(next, { x: `${distance}%`, opacity: 1, zIndex: 10 })
      .to(current, { x: `${-distance}%`, duration: 0.5, ease: 'power2.inOut' }, 0)
      .to(next, { x: '0%', duration: 0.5, ease: 'power2.inOut' }, 0)
      .set(current, { clearProps: 'transform' })
      .set(next, { clearProps: 'transform' })
      .call(() => hideAllExcept(index))
  }

  const slideName = getSlideNameByIndex(index)
  if (slideName) {
    mainStore.switchTab(slideName)
  }
}

/* ---------------- VueUse Swipe ---------------- */
const { direction, lengthX } = useSwipe(viewportRef, {
  threshold: 50,
  onSwipeEnd: (e, dir) => {
    if (dir === 'left' && activeIndex.value < totalSlides.value - 1) {
      goToSlide(activeIndex.value + 1, 'swipe')
    } else if (dir === 'right' && activeIndex.value > 0) {
      goToSlide(activeIndex.value - 1, 'swipe')
    } else if (dir === 'left' && activeIndex.value === totalSlides.value - 1) {
      showEndBounce('left')
    } else if (dir === 'right' && activeIndex.value === 0) {
      showEndBounce('right')
    }
  }
})

/* ---------------- Lifecycle ---------------- */
onMounted(() => {
  // Restore from storage if enabled
  if (props.persistActive && storedIndex.value !== props.initialSlide) {
    activeIndex.value = Math.min(storedIndex.value, totalSlides.value - 1)
  }

  // Initialize eager renders
  rendered.value = Array.from(
    { length: Math.min(props.eagerCount, totalSlides.value) },
    (_, i) => i
  )

  nextTick(() => {
    // Set initial visibility
    slideRefs.value.forEach((slide, idx) => {
      if (slide) {
        gsap.set(slide, { 
          opacity: idx === activeIndex.value ? 1 : 0,
          zIndex: idx === activeIndex.value ? 10 : 1,
          x: '0%',
          clearProps: 'transform'
        })
      }
    })

    const initialSlideName = getSlideNameByIndex(activeIndex.value)
    if (initialSlideName) {
      mainStore.switchTab(initialSlideName)
    }
  })
})

/* ---------------- Helper Functions ---------------- */
const getSlideNameByIndex = (index) => {
  const slide = props.slides[index]
  return slide?.name || slide?.props?.name || null
}

const hideAllExcept = (activeIdx) => {
  slideRefs.value.forEach((slide, idx) => {
    if (slide && idx !== activeIdx) {
      gsap.set(slide, { opacity: 0, zIndex: 1, x: '0%', clearProps: 'transform' })
    } else if (slide && idx === activeIdx) {
      gsap.set(slide, { opacity: 1, zIndex: 10, x: '0%', clearProps: 'transform' })
    }
  })
}

const showEndBounce = (direction) => {
  const currentSlide = slideRefs.value[activeIndex.value]
  if (!currentSlide) return
  
  // Determine bounce direction
  const bounceDistance = direction === 'left' ? -15 : 15
  
  // Elastic bounce animation
  gsap.timeline()
    .to(currentSlide, {
      x: `${bounceDistance}px`,
      duration: 0.2,
      ease: 'power2.out'
    })
    .to(currentSlide, {
      x: '0%',
      duration: 0.4,
      ease: 'elastic.out(1, 0.5)',
      clearProps: 'transform'
    })
}

// Expose for parent access
defineExpose({ goToSlide, activeIndex })
</script>

<style scoped>
@media (max-width: 576px) {
  .carousel-viewport {
    min-height: 70px;
  }
}
</style>