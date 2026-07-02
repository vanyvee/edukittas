<template>
  <div class="custom-carousel" :id="carouselIdRef">
    <!-- Optional header slot (sticky by parent CSS if they want) -->
    <div v-if="$slots.header" class="carousel-header">
      <slot name="header" />
    </div>

    <!-- Indicators (tabs) -->
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
      @mouseenter="onMouseEnter"
      @mouseleave="onMouseLeave"
      @touchstart.passive="onTouchStart"
      @touchmove.passive="onTouchMove"
      @touchend.passive="onTouchEnd"
      @mousedown="onDragStart"
      @mousemove="onDragMove"
      @mouseup="onDragEnd"
      @mouseleave="onDragEnd"
    >
      <!-- Slide container for 'slide' animation -->
      <div
        v-if="isSlide"
        class="carousel-inner d-flex h-100"
        :style="slideInnerStyle"
      >
        <div
          v-for="(group, gIndex) in grouped"
          :key="`group-${gIndex}`"
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
      </div>

      <!-- Fade mode: overlay pages and animate opacity -->
      <div v-else class="carousel-fade-wrapper position-relative h-100">
        <div
          v-for="(group, gIndex) in grouped"
          :key="`fade-${gIndex}`"
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
      </div>
    </div>

    <!-- Controls -->
    <div v-if="showControls" class="carousel-controls d-flex justify-content-between align-items-center mt-2">
      <button class="btn btn-sm btn-light" @click="prev" aria-label="Previous">‹</button>
      <button class="btn btn-sm btn-light" @click="next" aria-label="Next">›</button>
    </div>
  </div>
</template>

<script setup>
/* CustomCarousel.vue
   - Pure Vue 3 Composition API
   - Props documented in the component signature below
   - No dependency on Bootstrap JS
*/

import {
  ref,
  computed,
  watch,
  onMounted,
  onBeforeUnmount,
  nextTick,
  defineExpose,
} from "vue";

/* ---------------- Props ---------------- */
const props = defineProps({
  slides: { type: Array, required: true }, // [{ component, props, name? }, ...]
  pcDisplay: { type: Number, default: 1 }, // slides per view on large screens
  mobileDisplay: { type: Number, default: 1 }, // slides per view on small screens
  transitionType: { type: String, default: "slide" }, // 'slide' | 'fade'
  enableSwipe: { type: Boolean, default: false }, // left/right swipe
  showIndicators: { type: Boolean, default: true },
  showControls: { type: Boolean, default: true },
  autoplay: { type: Boolean, default: false },
  interval: { type: Number, default: 5000 }, // ms
  persistActive: { type: Boolean, default: false },
  carouselId: { type: String, default: "" }, // optional id
  breakAt: { type: Number, default: 992 }, // px breakpoint to switch between mobile/pc
  transitionDuration: { type: Number, default: 400 }, // ms animation duration
  initialSlide: { type: Number, default: 0 }
});

/* ---------------- Internals ---------------- */
const viewport = ref(null);
const isTouching = ref(false);
const isDragging = ref(false);
const startX = ref(0);
const currentTranslate = ref(0);
const savedTranslate = ref(0);
const activeIndex = ref(props.initialSlide)// index of group/page
const autoplayTimer = ref(null);

// Generate id if not provided
const carouselIdRef = computed(() => {
  return props.carouselId || `custom-carousel-${Math.random().toString(36).slice(2, 9)}`;
});

// Determine breakpoint (pc vs mobile)
const isPc = ref(window.innerWidth >= props.breakAt);
const handleResize = () => {
  const prev = isPc.value;
  isPc.value = window.innerWidth >= props.breakAt;
  if (prev !== isPc.value) {
    // when breakpoint changes, ensure active index clamped
    clampActiveIndex();
  }
};

/* Group slides based on display count */
const slidesPerView = computed(() => (isPc.value ? Math.max(1, props.pcDisplay) : Math.max(1, props.mobileDisplay)));

const grouped = computed(() => {
  const per = slidesPerView.value;
  const groups = [];
  for (let i = 0; i < props.slides.length; i += per) {
    groups.push(props.slides.slice(i, i + per));
  }
  // ensure at least one group so UI doesn't break
  return groups.length ? groups : [[]];
});

const totalGroups = computed(() => grouped.value.length);

/* Clamp activeIndex if slides change */
const clampActiveIndex = () => {
  if (activeIndex.value >= totalGroups.value) {
    activeIndex.value = Math.max(0, totalGroups.value - 1);
  }
};

/* Restore/persist active index if needed */
const sessionKey = computed(() => `${carouselIdRef.value}-activeSlide`);
if (props.persistActive) {
  const saved = sessionStorage.getItem(sessionKey.value);
  if (saved !== null) {
    const idx = parseInt(saved, 10);
    if (!Number.isNaN(idx)) activeIndex.value = idx;
  }
}

/* Watch slides & grouped changes to keep index valid */
watch([() => props.slides, grouped], () => {
  clampActiveIndex();
});

/* Save when activeIndex changes */
watch(activeIndex, (v) => {
  if (props.persistActive) {
    sessionStorage.setItem(sessionKey.value, String(v));
  }
  // reset swipe translate base
  savedTranslate.value = -v * 100;
  currentTranslate.value = savedTranslate.value;
});

/* Computed classes/styles */
const isSlide = computed(() => props.transitionType === "slide");
const computedColClass = computed(() => {
  // Each item is a column occupying equal fraction of the row
  // We'll rely on Bootstrap column counts - e.g., pcDisplay=3 -> col-lg-4 (12/3=4)
  const colPc = Math.floor(12 / Math.max(1, props.pcDisplay));
  const colMob = Math.floor(12 / Math.max(1, props.mobileDisplay));
  return `col-${colMob} col-lg-${colPc}`;
});

/* Slide inner style for slide mode */
const slideInnerStyle = computed(() => {
  const duration = props.transitionDuration;
  // translate based on activeIndex (and any temporary drag offset)
  const  = -activeIndex.value * 100; // percent
  const translate = currentTranslate.value !== 0 ? currentTranslate.value : baseTranslate;
  return {
    width: `${totalGroups.value * 100}%`,
    transform: `translateX(${translate}%)`,
    transition: isTouching.value || isDragging.value ? "none" : `transform ${duration}ms ease`,
  };
});

const pageStyle = computed(() => {
  return {
    width: `${100 / Math.max(1, totalGroups.value)}%`,
    padding: "0.25rem",
    boxSizing: "border-box",
  };
});

/* Fade page style computed on the fly */
const fadePageStyle = (gIndex) => {
  const duration = props.transitionDuration;
  return {
    transition: `opacity ${duration}ms ease, visibility ${duration}ms ease`,
    opacity: gIndex === activeIndex.value ? 1 : 0,
    visibility: gIndex === activeIndex.value ? "visible" : "hidden",
    position: "absolute",
    inset: "0",
    padding: "0.25rem",
    boxSizing: "border-box",
  };
};

/* Label for indicator (use group name of first slide or index+1) */
const labelForGroup = (idx) => {
  const g = grouped.value[idx];
  if (!g || !g.length) return `${idx + 1}`;
  const first = g[0];
  return first.name || first.props?.name || `Slide ${idx + 1}`;
};

/* ---------------- Navigation API ---------------- */
const goTo = (index) => {
  const safe = Math.max(0, Math.min(index, totalGroups.value - 1));
  activeIndex.value = safe;
  // reset drag positions
  savedTranslate.value = -safe * 100;
  currentTranslate.value = savedTranslate.value;
};

const next = () => {
  const nextIndex = activeIndex.value + 1;
  if (nextIndex >= totalGroups.value) {
    goTo(0); // wrap
  } else {
    goTo(nextIndex);
  }
};

const prev = () => {
  const prevIndex = activeIndex.value - 1;
  if (prevIndex < 0) {
    goTo(totalGroups.value - 1); // wrap
  } else {
    goTo(prevIndex);
  }
};

/* Expose methods to parent */
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

/* Pause on hover if autoplay enabled */
const onMouseEnter = () => {
  if (props.autoplay) stopAutoplay();
};
const onMouseLeave = () => {
  if (props.autoplay) startAutoplay();
};

/* Watch autoplay and slides length */
watch(
  () => [props.autoplay, totalGroups.value, props.interval],
  () => {
    if (props.autoplay) startAutoplay();
    else stopAutoplay();
  },
  { immediate: true }
);

/* ---------------- Swipe & Drag Handling ---------------- */
const onTouchStart = (e) => {
  if (!props.enableSwipe) return;
  stopAutoplay();
  isTouching.value = true;
  startX.value = e.touches[0].clientX;
  savedTranslate.value = -activeIndex.value * 100;
  currentTranslate.value = savedTranslate.value;
  
};

const onTouchMove = (e) => {
  if (!isTouching.value) return;
  const dx = e.touches[0].clientX - startX.value;
  const percent = (dx / (viewport.value?.clientWidth || window.innerWidth)) * 100;
  currentTranslate.value = savedTranslate.value + percent;
};

const onTouchEnd = () => {
  if (!isTouching.value) return;
  isTouching.value = false;
  // decide threshold 20% to move
  const moved = currentTranslate.value - savedTranslate.value;
  if (moved > 15) {
    // dragged right -> prev
    prev();
  } else if (moved < -15) {
    // dragged left -> next
    next();
  } else {
    // snap back
    goTo(activeIndex.value);
  }
  if (props.autoplay) startAutoplay();
};

/* Mouse drag for desktop if enableSwipe */
const onDragStart = (e) => {
  if (!props.enableSwipe) return;
  // prevent text selection
  e.preventDefault();
  stopAutoplay();
  isDragging.value = true;
  startX.value = e.clientX;
  savedTranslate.value = -activeIndex.value * 100;
  currentTranslate.value = savedTranslate.value;
};

const onDragMove = (e) => {
  if (!isDragging.value) return;
  const dx = e.clientX - startX.value;
  const percent = (dx / (viewport.value?.clientWidth || window.innerWidth)) * 100;
  currentTranslate.value = savedTranslate.value + percent;
};

const onDragEnd = () => {
  if (!isDragging.value) return;
  isDragging.value = false;
  const moved = currentTranslate.value - savedTranslate.value;
  if (moved > 15) prev();
  else if (moved < -15) next();
  else goTo(activeIndex.value);
  if (props.autoplay) startAutoplay();
};

/* ---------------- Lifecycle ---------------- */
onMounted(() => {
  
  // ensure viewport ref is ready
  nextTick(() => {
    // set initial translate base
    savedTranslate.value = -activeIndex.value * 100;
    currentTranslate.value = savedTranslate.value;
    // start autoplay if enabled
    if (props.autoplay) startAutoplay();
  });
});

onBeforeUnmount(() => {
  stopAutoplay();
  window.removeEventListener("resize", handleResize);
});

/* expose some reactive state if parent wants to watch */
defineExpose({
  activeIndex,
  grouped,
  slidesPerView,
});
</script>
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

usage
<template>
  <div>
    <CustomizedCarousel
      :slides="carouselSlides"
      :pcDisplay="2"
      :mobileDisplay="1"
      :transitionType="'slide'"
      :enableSwipe="true"
      :showIndicators="true"
      :showControls="true"
      :autoplay="true"
      :interval="4000"
      :persistActive="true"
      :preloadAdjacent="true"
      carouselId="mySchoolCarousel"
      :transitionDuration="500"
      : initialSlide = "1"
    >
      <template #header>
        <div class="d-flex sticky-top justify-content-between align-items-center p-2 bg-primary text-white">
          <Ribbon />
          <KebabMenu />
        </div>
      </template>
    </CustomizedCarousel>
  </div>
</template>

<script setup>
import CustomCarousel from './CustomCarousel.vue';
import Ribbon from './Ribbon.vue';
import KebabMenu from './KebabMenu.vue';
import StudentSlide from './StudentSlide.vue';
import TeacherSlide from './TeacherSlide.vue';
import SubjectSlide from './SubjectSlide.vue';
import RegistrationForm from './RegistrationForm.vue';

// Example data
const students = [
  { id: 1, name: 'Alice' },
  { id: 2, name: 'Bob' },
];
const teachers = [
  { id: 1, name: 'Mr. Smith' },
  { id: 2, name: 'Ms. Johnson' },
];
const subjects = [
  { id: 1, name: 'Math' },
  { id: 2, name: 'Science' },
];

// Carousel slides
const carouselSlides = [
  { component: StudentSlide, props: { students }, name: 'Students' },
  { component: TeacherSlide, props: { teachers }, name: 'Teachers' },
  { component: SubjectSlide, props: { subjects }, name: 'Subjects' },
  { component: RegistrationForm, props: {}, name: 'Admission' },
];
</script>