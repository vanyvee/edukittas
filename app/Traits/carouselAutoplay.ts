import { defineStore } from "pinia";
import { ref } from "vue";

export const useCarouselAutoplayStore = defineStore("carouselAutoplay", () => {
  const activeCarousels = ref({}); // { id: nextFn }
  const timer = ref(null);

  const register = (id, nextFn) => {
    activeCarousels.value[id] = nextFn;
    ensureRunning();
  };

  const unregister = (id) => {
    delete activeCarousels.value[id];

    if (Object.keys(activeCarousels.value).length === 0) {
      stop();
    }
  };

  const ensureRunning = () => {
    if (timer.value) return;

    timer.value = setInterval(() => {
      Object.values(activeCarousels.value).forEach((nextFn) => nextFn());
    }, 5000);
  };

  const stop = () => {
    if (timer.value) {
      clearInterval(timer.value);
      timer.value = null;
    }
  };

  return { register, unregister, stop };
});