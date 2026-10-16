
find out how to refactor autoplay imto store so that
you omly have one instance of autoppay no matter how 
many carousel u hav

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
    <div ref="viewport" class="carousel-viewport position-relative overflow-hidden">
      <div class="carousel-inner d-flex h-100" :style="slideInnerStyle">
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
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from "vue";
import { useCarouselAutoplayStore } from "@/stores/carouselAutoplay";

const props = defineProps({
  slides: { type: Array, required: true },
  pcDisplay: { type: Number, default: 1 },
  mobileDisplay: { type: Number, default: 1 },
  showIndicators: { type: Boolean, default: true },
  showControls: { type: Boolean, default: false },
  autoplay: { type: Boolean, default: false },
  interval: { type: Number, default: 5000 },
  carouselId: { type: String, default: "" },
  transitionDuration: { type: Number, default: 100 },
  initialSlide: { type: Number, default: 0 },
});

const viewport = ref(null);
const activeIndex = ref(props.initialSlide);
const carouselIdRef = computed(
  () => props.carouselId || `custom-carousel-${Math.random().toString(36).slice(2, 9)}`
);

const isPc = ref(window.innerWidth >= 992);
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

const computedColClass = computed(() => {
  const colPc = Math.floor(12 / Math.max(1, props.pcDisplay));
  const colMob = Math.floor(12 / Math.max(1, props.mobileDisplay));
  return `col-${colMob} col-lg-${colPc}`;
});

const slideInnerStyle = computed(() => ({
  width: `${totalGroups.value * 100}%`,
  transform: `translateX(-${activeIndex.value * (100 / totalGroups.value)}%)`,
  transition: `transform ${props.transitionDuration}ms ease`,
}));

const pageStyle = computed(() => ({
  width: `${100 / Math.max(1, totalGroups.value)}%`,
  padding: "0.25rem",
  boxSizing: "border-box",
}));

const labelForGroup = (idx) => {
  const g = grouped.value[idx];
  if (!g || !g.length) return `${idx + 1}`;
  const first = g[0];
  return first.name || first.props?.name || `Slide ${idx + 1}`;
};

const goTo = (index) => {
  const safe = Math.max(0, Math.min(index, totalGroups.value - 1));
  activeIndex.value = safe;
};
const next = () => {
  const ni = activeIndex.value + 1;
  activeIndex.value = ni >= totalGroups.value ? 0 : ni;
};
const prev = () => {
  const pi = activeIndex.value - 1;
  activeIndex.value = pi < 0 ? totalGroups.value - 1 : pi;
};

// Store-based autoplay
const autoplayStore = useCarouselAutoplayStore();

onMounted(() => {
  nextTick(() => {
    if (props.autoplay) {
      autoplayStore.register(carouselIdRef.value, next, props.interval);
    }
  });
});

onBeforeUnmount(() => {
  autoplayStore.unregister(carouselIdRef.value);
});
</script>

<style scoped>
.custom-carousel {
  width: 100%;
  position: relative;
  font-family: inherit;
}

/* Indicators */
.carousel-indicators {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
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
  user-select: none;
}

/* Slide mode */
.carousel-inner {
  display: flex;
  width: 100%;
  height: 100%;
  will-change: transform;
}
.carousel-page {
  flex-shrink: 0;
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
  min-width: 36px;
  min-height: 36px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  font-size: 1.2rem;
}
</style>





pinia store

// /stores/carouselAutoplay.js
import { defineStore } from "pinia";
import { ref, onUnmounted } from "vue";

export const useCarouselAutoplayStore = defineStore("carouselAutoplay", () => {
  const activeCarousels = ref(new Map());
  const timer = ref(null);
  const interval = ref(5000);

  const register = (id, nextFn, customInterval) => {
    activeCarousels.value.set(id, nextFn);
    if (customInterval) interval.value = customInterval;
    ensureRunning();
  };

  const unregister = (id) => {
    activeCarousels.value.delete(id);
    if (activeCarousels.value.size === 0) stop();
  };

  const ensureRunning = () => {
    if (!timer.value && activeCarousels.value.size > 0) {
      timer.value = setInterval(() => {
        activeCarousels.value.forEach((next) => next());
      }, interval.value);
    }
  };

  const stop = () => {
    if (timer.value) {
      clearInterval(timer.value);
      timer.value = null;
    }
  };
  

  return { register, unregister, stop };
});