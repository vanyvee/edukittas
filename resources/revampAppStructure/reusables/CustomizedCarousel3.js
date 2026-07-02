this component was optimized so that
not all component slide are rendered 
once by the loop... only 3....on swipe the 
rest are loaded to view...

<template>
  <div class="custom-carousel" :id="carouselIdRef">
    <!-- Optional header slot -->
    <div v-if="$slots.header" class="carousel-header">
      <slot name="header" />
    </div>

    <!-- Indicators -->
    <div
      v-if="showIndicators"
      class="carousel-indicators d-flex justify-content-center my-2"
      role="tablist"
    >
      <button
        v-for="(group, idx) in grouped"
        :key="`ind-${idx}`"
        :class="['indicator-btn', { active: idx === activeIndex }]"
        @click="goTo(idx)"
        :aria-label="`Go to slide ${idx + 1}`"
      >
        {{ labelForGroup(idx) }}
      </button>
    </div>

    <!-- Viewport -->
    <div
      ref="viewport"
      class="carousel-viewport position-relative overflow-hidden"
      @touchstart.passive="onTouchStart"
      @touchmove.passive="onTouchMove"
      @touchend.passive="onTouchEnd"
    >
      <!-- Slide mode -->
      <div v-if="isSlide" class="carousel-inner d-flex h-100" :style="slideInnerStyle">
        <template v-for="(group, gIndex) in grouped" :key="`group-${gIndex}`">
          <!-- Eager first three -->
          <div
            v-if="gIndex < eagerCount"
            class="carousel-page flex-shrink-0 h-100"
            :style="pageStyle"
          >
            <div class="row g-2 h-100 align-items-stretch">
              <component
                v-for="(item, i) in group"
                :is="item.component"
                v-bind="item.props || {}"
                :key="`item-${gIndex}-${i}`"
                class="col"
                :class="computedColClass"
              />
            </div>
          </div>

          <!-- Lazy + linger unmount -->
          <SoftUnmount
            v-else
            v-if="shouldRender(gIndex)"
            :linger="softLinger"
            class="flex-shrink-0 h-100"
          >
            <div class="carousel-page h-100" :style="pageStyle">
              <div class="row g-2 h-100 align-items-stretch">
                <component
                  v-for="(item, i) in group"
                  :is="item.component"
                  v-bind="item.props || {}"
                  :key="`lazy-item-${gIndex}-${i}`"
                  class="col"
                  :class="computedColClass"
                />
              </div>
            </div>
          </SoftUnmount>
        </template>
      </div>

      <!-- Fade mode -->
      <div v-else class="carousel-fade-wrapper position-relative h-100">
        <template v-for="(group, gIndex) in grouped" :key="`fade-${gIndex}`">
          <!-- Eager first three -->
          <div
            v-if="gIndex < eagerCount"
            class="carousel-page-fade position-absolute top-0 start-0 w-100 h-100"
            :style="fadePageStyle(gIndex)"
          >
            <div class="row g-2 h-100 align-items-stretch">
              <component
                v-for="(item, i) in group"
                :is="item.component"
                v-bind="item.props || {}"
                :key="`fade-item-${gIndex}-${i}`"
                class="col"
                :class="computedColClass"
              />
            </div>
          </div>

          <!-- Lazy + linger unmount -->
          <div 
          <SoftUnmount
            v-else
            v-if="shouldRender(gIndex)"
            :linger="softLinger"
            class="carousel-page-fade position-absolute top-0 start-0 w-100 h-100"
            :style="fadePageStyle(gIndex)"
          >
            <div class="row g-2 h-100 align-items-stretch">
              <component
                v-for="(item, i) in group"
                :is="item.component"
                v-bind="item.props || {}"
                :key="`fade-lazy-item-${gIndex}-${i}`"
                class="col"
                :class="computedColClass"
              />
            </div>
          </SoftUnmount>
        </template>
      </div>
    </div>

    <!-- Controls -->
    <div
      v-if="showControls"
      class="carousel-controls d-flex justify-content-between align-items-center mt-2"
    >
      <button class="btn btn-sm btn-light" @click="prev" aria-label="Previous">‹</button>
      <button class="btn btn-sm btn-light" @click="next" aria-label="Next">›</button>
    </div>
  </div>
</template>

<script setup>
import {
  ref,
  computed,
  watch,
  onMounted,
  onBeforeUnmount,
  nextTick,
  defineExpose,
} from "vue";
import SoftUnmount from "./SoftUnmount.vue";

/* ---------------- Props ---------------- */
const props = defineProps({
  slides: { type: Array, required: true },
  pcDisplay: { type: Number, default: 1 },
  mobileDisplay: { type: Number, default: 1 },
  transitionType: { type: String, default: "slide" },
  enableSwipe: { type: Boolean, default: false },
  showIndicators: { type: Boolean, default: true },
  showControls: { type: Boolean, default: false },
  autoplay: { type: Boolean, default: false },
  interval: { type: Number, default: 5000 },
  persistActive: { type: Boolean, default: false },
  carouselId: { type: String, default: "" },
  breakAt: { type: Number, default: 992 },
  transitionDuration: { type: Number, default: 400 },
  initialSlide: { type: Number, default: 0 },
  // New optional customization
  eagerCount: { type: Number, default: 3 },
  softLinger: { type: Number, default: 2500 },
});

const viewport = ref(null);
const isTouching = ref(false);
const startX = ref(0);
const currentTranslate = ref(0);
const savedTranslate = ref(0);
const activeIndex = ref(props.initialSlide);
const autoplayTimer = ref(null);

const carouselIdRef = computed(
  () => props.carouselId || `custom-carousel-${Math.random().toString(36).slice(2, 9)}`
);

const isPc = ref(window.innerWidth >= props.breakAt);
const handleResize = () => {
  const prev = isPc.value;
  isPc.value = window.innerWidth >= props.breakAt;
  if (prev !== isPc.value) clampActiveIndex();
};
window.addEventListener("resize", handleResize);

const slidesPerView = computed(() =>
  isPc.value ? Math.max(1, props.pcDisplay) : Math.max(1, props.mobileDisplay)
);
const grouped = computed(() => {
  const per = slidesPerView.value;
  const groups = [];
  for (let i = 0; i < props.slides.length; i += per) {
    groups.push(props.slides.slice(i, i + per));
  }
  return groups.length ? groups : [[]];
});
const totalGroups = computed(() => grouped.value.length);
const clampActiveIndex = () => {
  if (activeIndex.value >= totalGroups.value)
    activeIndex.value = Math.max(0, totalGroups.value - 1);
};

const sessionKey = computed(() => `${carouselIdRef.value}-activeSlide`);
if (props.persistActive) {
  const saved = sessionStorage.getItem(sessionKey.value);
  if (saved !== null) {
    const idx = parseInt(saved, 10);
    if (!Number.isNaN(idx)) activeIndex.value = idx;
  } else {
    activeIndex.value = props.initialSlide;
  }
} else {
  activeIndex.value = props.initialSlide;
}
watch([() => props.slides, grouped], clampActiveIndex);

watch(activeIndex, (v) => {
  if (props.persistActive) sessionStorage.setItem(sessionKey.value, String(v));
  savedTranslate.value = -v * 100;
  currentTranslate.value = savedTranslate.value;
});

/* ---------------- SoftUnmount logic ---------------- */
const eagerCount = props.eagerCount;
const rendered = ref(new Set([...Array(Math.min(eagerCount, totalGroups.value)).keys()]));

watch(totalGroups, (n) => {
  for (let i = 0; i < Math.min(eagerCount, n); i++) rendered.value.add(i);
}, { immediate: true });

watch(activeIndex, (newIdx) => {
  rendered.value.add(newIdx);
});

const shouldRender = (index) => {
  // Render if already rendered or close to active
  return rendered.value.has(index) || Math.abs(index - activeIndex.value) <= 1;
};

/* ---------------- Computed helpers ---------------- */
const isSlide = computed(() => props.transitionType === "slide");
const computedColClass = computed(() => {
  const colPc = Math.floor(12 / Math.max(1, props.pcDisplay));
  const colMob = Math.floor(12 / Math.max(1, props.mobileDisplay));
  return `col-${colMob} col-lg-${colPc}`;
});

const slideInnerStyle = computed(() => {
  const duration = isTouching.value ? 0 : props.transitionDuration;
  return {
    width: `${totalGroups.value * 100}%`,
    transform: `translateX(${currentTranslate.value}%)`,
    transition: `transform ${duration}ms ease`,
  };
});

const pageStyle = computed(() => ({
  width: `${100 / Math.max(1, totalGroups.value)}%`,
  padding: "0.25rem",
  boxSizing: "border-box",
}));

const fadePageStyle = (gIndex) => ({
  transition: `opacity ${props.transitionDuration}ms ease, visibility ${props.transitionDuration}ms ease`,
  opacity: gIndex === activeIndex.value ? 1 : 0,
  visibility: gIndex === activeIndex.value ? "visible" : "hidden",
  position: "absolute",
  inset: "0",
  padding: "0.25rem",
  boxSizing: "border-box",
});

const labelForGroup = (idx) => {
  const g = grouped.value[idx];
  if (!g || !g.length) return `${idx + 1}`;
  const first = g[0];
  return first.name || first.props?.name || `Slide ${idx + 1}`;
};

/* ---------------- Navigation ---------------- */
const goTo = (index) => {
  const safe = Math.max(0, Math.min(index, totalGroups.value - 1));
  activeIndex.value = safe;
  savedTranslate.value = -safe * 100;
  currentTranslate.value = savedTranslate.value;
};
const next = () => {
  const ni = activeIndex.value + 1;
  if (ni >= totalGroups.value) goTo(0);
  else goTo(ni);
};
const prev = () => {
  const pi = activeIndex.value - 1;
  if (pi < 0) goTo(totalGroups.value - 1);
  else goTo(pi);
};
defineExpose({ next, prev, goTo, activeIndex });

/* ---------------- Autoplay ---------------- */
const startAutoplay = () => {
  stopAutoplay();
  if (!props.autoplay || totalGroups.value <= 1) return;
  autoplayTimer.value = setInterval(() => {
    next();
  }, Math.max(200, props.interval));
};
const stopAutoplay = () => {
  if (autoplayTimer.value) {
    clearInterval(autoplayTimer.value);
    autoplayTimer.value = null;
  }
};
const onMouseEnter = () => {
  if (props.autoplay) stopAutoplay();
};
const onMouseLeave = () => {
  if (props.autoplay) startAutoplay();
};
watch(
  () => [props.autoplay, totalGroups.value, props.interval],
  () => {
    if (props.autoplay) startAutoplay();
    else stopAutoplay();
  },
  { immediate: true }
);

/* ---------------- Swipe ---------------- */
const thresholdPercent = 0.15;
const onTouchStart = (e) => {
  if (!props.enableSwipe) return;
  isTouching.value = true;
  startX.value = e.touches[0].clientX;
  savedTranslate.value = -activeIndex.value * 100;
  currentTranslate.value = savedTranslate.value;
  stopAutoplay();
};
const onTouchMove = (e) => {
  if (!isTouching.value) return;
  const dx = e.touches[0].clientX - startX.value;
  let percent = (dx / (viewport.value?.clientWidth || window.innerWidth)) * 100;
  let newTranslate = savedTranslate.value + percent;
  const maxTranslate = 0;
  const minTranslate = -(totalGroups.value - 1) * 100;
  if (newTranslate > maxTranslate)
    newTranslate = maxTranslate + (newTranslate - maxTranslate) * 0.3;
  if (newTranslate < minTranslate)
    newTranslate = minTranslate + (newTranslate - minTranslate) * 0.3;
  currentTranslate.value = newTranslate;
};
const onTouchEnd = () => {
  if (!isTouching.value) return;
  isTouching.value = false;
  const moved = (currentTranslate.value - savedTranslate.value) / 100;
  if (moved > thresholdPercent) prev();
  else if (moved < -thresholdPercent) next();
  else goTo(activeIndex.value);
  if (props.autoplay) startAutoplay();
};

/* ---------------- Lifecycle ---------------- */
onMounted(() => {
  nextTick(() => {
    savedTranslate.value = -activeIndex.value * 100;
    currentTranslate.value = savedTranslate.value;
  });
  if (props.autoplay) startAutoplay();
  viewport.value?.addEventListener("mouseenter", onMouseEnter);
  viewport.value?.addEventListener("mouseleave", onMouseLeave);
});
onBeforeUnmount(() => {
  stopAutoplay();
  viewport.value?.removeEventListener("mouseenter", onMouseEnter);
  viewport.value?.removeEventListener("mouseleave", onMouseLeave);
  window.removeEventListener("resize", handleResize);
});
</script>

<style scoped>
@import "./carousel-styles.css"; /* Optional: you can keep your existing styles inline */
</style>

<style scoped>
.custom-carousel {
  width: 100%;
  --control-size: 36px; /* For control button sizing */
  position: relative;
  font-family: inherit;
}

/* Header slot wrapper */
.carousel-header {
  width: 100%;
}

/* Indicators */
.carousel-indicators {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  padding: 0;
  margin: 0.5rem 0;
}
.indicator-btn {
  background: transparent;
  border: none;
  padding: 6px 10px;
  cursor: pointer;
  color: #333;
  border-radius: 6px;
  font-size: 0.875rem;
  transition: background-color 0.15s, color 0.15s;
}
.indicator-btn.active {
  background-color: rgba(0, 123, 255, 0.12);
  color: #075e54;
  font-weight: 600;
}

/* Viewport */
.carousel-viewport {
  overflow: hidden;
  position: relative;
  height: auto;
  min-height: 160px; /* can be overridden by parent */
  user-select: none;
  touch-action: pan-y;
}

/* Slide mode inner */
.carousel-inner {
  display: flex;
  width: 100%;
  height: 100%;
  will-change: transform;
  transition: transform 400ms ease;
}

/* Each slide/page */
.carousel-page {
  flex-shrink: 0;
  padding: 0.25rem;
  box-sizing: border-box;
  height: 100%;
}

/* Fade mode wrapper */
.carousel-fade-wrapper {
  position: relative;
  width: 100%;
  height: 100%;
}
.carousel-page-fade {
  position: absolute;
  inset: 0;
  padding: 0.25rem;
  box-sizing: border-box;
}

/* Controls */
.carousel-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 0.5rem;
}
.carousel-controls .btn-light {
  min-width: var(--control-size);
  min-height: var(--control-size);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  border-radius: 6px;
  font-size: 1.2rem;
  cursor: pointer;
}

/* Responsive helpers */
@media (max-width: 576px) {
  .carousel-viewport { min-height: 120px; }
  .indicator-btn { padding: 4px 8px; font-size: 0.75rem; }
  .carousel-controls .btn-light { min-width: 28px; min-height: 28px; font-size: 1rem; }
}
</style>

