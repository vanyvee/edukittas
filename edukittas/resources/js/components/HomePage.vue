<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

// ─── Props ────────────────────────────────────────────────────────────────────
const props = defineProps({
  mediaItems:    { type: Array, default: () => [] },
  promos:        { type: Array, default: () => [] },
  trendingMeals: { type: Array, default: () => [] },
})

// ─── Modal ────────────────────────────────────────────────────────────────────
const modalData  = ref(null)
const modalType  = ref(null)
const showModal  = ref(false)
const openModal  = (type, data) => { modalType.value = type; modalData.value = data; showModal.value = true }
const closeModal = () => { showModal.value = false; modalData.value = null }

// ─── Single carousel engine — controls all 3 carousels ───────────────────────
const mediaIndex  = ref(0)
const promoIndex  = ref(0)
const mealsIndex  = ref(0)

const mediaPaused = ref(false)
const promoPaused = ref(false)
const mealsPaused = ref(false)

// Visible meal slots
const mealsVisible = ref(5)
const updateVisible = () => { mealsVisible.value = window.innerWidth >= 768 ? 5 : 4 }

let masterTimer = null

const tick = () => {
  // media
  if (!mediaPaused.value && displayMedia.value.length > 1) {
    mediaIndex.value = (mediaIndex.value + 1) % displayMedia.value.length
  }
  // promo — slower cadence via modulo on a counter
  tickCount.value++
  if (tickCount.value % 2 === 0) {
    if (!promoPaused.value && displayPromos.value.length > 1) {
      promoIndex.value = (promoIndex.value + 1) % displayPromos.value.length
    }
  }
  // meals — slower still
  if (tickCount.value % 3 === 0) {
    if (!mealsPaused.value) {
      const max = Math.max(0, displayMeals.value.length - mealsVisible.value)
      mealsIndex.value = mealsIndex.value >= max ? 0 : mealsIndex.value + 1
    }
  }
}

const tickCount = ref(0)

onMounted(() => {
  updateVisible()
  window.addEventListener('resize', updateVisible)
  masterTimer = setInterval(tick, 3000)
})

onUnmounted(() => {
  clearInterval(masterTimer)
  window.removeEventListener('resize', updateVisible)
})

// ─── Display data (fallbacks for dev) ────────────────────────────────────────
const displayMedia = computed(() => props.mediaItems.length ? props.mediaItems : [
  { type: 'image', url: 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800&q=80', alt: 'Food' },
  { type: 'image', url: 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=800&q=80', alt: 'Grilled' },
  { type: 'image', url: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=800&q=80', alt: 'Salad' },
])

const displayPromos = computed(() => props.promos.length ? props.promos : [
  { id: 1, title: '🔥 Weekend Bonanza', subtitle: '30% off all rice dishes Friday–Sunday', badge: 'HOT', image: 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=600&q=80' },
  { id: 2, title: '🎉 New on the Menu', subtitle: 'Smokey Jollof Rice is here — try it today', badge: 'NEW', image: 'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=600&q=80' },
  { id: 3, title: '⚡ Flash Deal', subtitle: 'Buy 2 proteins, get a drink free till 6PM', badge: 'DEAL', image: 'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?w=600&q=80' },
])

const displayMeals = computed(() => props.trendingMeals.length ? props.trendingMeals : [
  { id: 1, name: 'Jollof Rice',     image: 'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=300&q=80', price: '₦2,500' },
  { id: 2, name: 'Fried Chicken',   image: 'https://images.unsplash.com/photo-1562967914-608f82629710?w=300&q=80', price: '₦3,000' },
  { id: 3, name: 'Suya Wrap',       image: 'https://images.unsplash.com/photo-1529006557810-274b9b2fc783?w=300&q=80', price: '₦1,800' },
  { id: 4, name: 'Pepper Soup',     image: 'https://images.unsplash.com/photo-1547592180-85f173990554?w=300&q=80', price: '₦2,200' },
  { id: 5, name: 'Egusi',           image: 'https://images.unsplash.com/photo-1565557623262-b51c2513a641?w=300&q=80', price: '₦2,800' },
  { id: 6, name: 'Tilapia',         image: 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=300&q=80', price: '₦3,500' },
  { id: 7, name: 'Moi Moi',         image: 'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?w=300&q=80', price: '₦800'  },
])

const mealsTranslate = computed(() => {
  const pct = 100 / mealsVisible.value
  return `translateX(-${mealsIndex.value * pct}%)`
})
</script>

<template>
  <div class="wrap">

    <!-- ══ LEFT COLUMN (60%) ══════════════════════════════════════════════ -->
    <div class="col-left">

      <!-- Brand banner -->
      <div class="brand-banner">
        <p class="welcome">you're welcome to</p>
        <h1 class="brand-title">
          <span class="glow">Tasty</span><span class="glow glow--b">Byti</span>
        </h1>
      </div>

      <!-- Media carousel — no gap, flush below banner -->
      <div
        class="media-wrap"
        @mouseenter="mediaPaused = true"
        @mouseleave="mediaPaused = false"
        @touchstart.passive="mediaPaused = true"
        @touchend.passive="mediaPaused = false"
      >
        <template v-for="(item, i) in displayMedia" :key="i">
          <Transition name="fade">
            <div v-if="i === mediaIndex" class="media-slide">
              <video v-if="item.type === 'video'" :src="item.url" autoplay muted loop playsinline class="media-asset" />
              <img  v-else :src="item.url" :alt="item.alt" class="media-asset" />
            </div>
          </Transition>
        </template>

        <div class="dots">
          <span
            v-for="(_, i) in displayMedia" :key="i"
            class="dot" :class="{ 'dot--on': i === mediaIndex }"
            @click="mediaIndex = i"
          />
        </div>
      </div>

    </div>

    <!-- ══ RIGHT COLUMN (40%) ═════════════════════════════════════════════ -->
    <div class="col-right">

      <!-- Promo carousel -->
      <div
        class="promo-wrap"
        @mouseenter="promoPaused = true"
        @mouseleave="promoPaused = false"
        @touchstart.passive="promoPaused = true"
        @touchend.passive="promoPaused = false"
      >
        <template v-for="(promo, i) in displayPromos" :key="promo.id">
          <Transition name="fade">
            <button v-if="i === promoIndex" class="promo-slide" @click="openModal('promo', promo)">
              <img v-if="promo.image" :src="promo.image" :alt="promo.title" class="promo-bg" />
              <div class="promo-overlay">
                <span v-if="promo.badge" class="badge">{{ promo.badge }}</span>
                <p class="promo-title">{{ promo.title }}</p>
                <p class="promo-sub">{{ promo.subtitle }}</p>
              </div>
            </button>
          </Transition>
        </template>

        <div class="dots dots--promo">
          <span
            v-for="(_, i) in displayPromos" :key="i"
            class="dot" :class="{ 'dot--on': i === promoIndex }"
            @click="promoIndex = i"
          />
        </div>
      </div>

      <!-- Meals row — same height as brand banner -->
      <div
        class="meals-row"
        @mouseenter="mealsPaused = true"
        @mouseleave="mealsPaused = false"
        @touchstart.passive="mealsPaused = true"
        @touchend.passive="mealsPaused = false"
      >
        <p class="meals-label">❤️ Last week's faves</p>
        <div class="meals-viewport">
          <div class="meals-track" :style="{ transform: mealsTranslate }">
            <button
              v-for="meal in displayMeals" :key="meal.id"
              class="meal-tile"
              :style="{ width: `calc(${100 / mealsVisible}% - 0.2rem)` }"
              @click="openModal('meal', meal)"
            >
              <img :src="meal.image" :alt="meal.name" class="meal-img" />
              <span class="meal-name">{{ meal.name }}</span>
            </button>
          </div>
        </div>
      </div>

    </div>

    <!-- ══ MODAL ══════════════════════════════════════════════════════════ -->
    <Transition name="fade">
      <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
        <div class="modal-box">
          <button class="modal-close" @click="closeModal">✕</button>

          <template v-if="modalType === 'meal' && modalData">
            <img :src="modalData.image" :alt="modalData.name" class="modal-img" />
            <div class="modal-body">
              <h2 class="modal-title">{{ modalData.name }}</h2>
              <p class="modal-price">{{ modalData.price }}</p>
              <button class="modal-cta">Add to Order</button>
            </div>
          </template>

          <template v-if="modalType === 'promo' && modalData">
            <img v-if="modalData.image" :src="modalData.image" :alt="modalData.title" class="modal-img" />
            <div class="modal-body">
              <span v-if="modalData.badge" class="badge">{{ modalData.badge }}</span>
              <h2 class="modal-title">{{ modalData.title }}</h2>
              <p class="modal-desc">{{ modalData.subtitle }}</p>
              <button class="modal-cta">Claim Deal</button>
            </div>
          </template>
        </div>
      </div>
    </Transition>

  </div>
</template>

<style scoped>
/* ══ LAYOUT ════════════════════════════════════════════════════════════════ */
.wrap {
  display: flex;
  width: 100%;
  height: 100%;
  overflow: hidden;
  background: var(--surface-900);
  gap: 0.25rem;
}

.col-left  { display: flex; flex-direction: column; flex: 60; min-width: 0; }
.col-right { display: flex; flex-direction: column; flex: 40; min-width: 0; gap: 0.25rem; }

/* ══ BRAND BANNER ══════════════════════════════════════════════════════════ */
.brand-banner {
  flex: 0 0 auto;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 0.3rem 0.5rem 0.2rem;
  line-height: 1;
}

.welcome {
  margin: 0;
  font-size: clamp(0.5rem, 1.2vw, 0.7rem);
  font-style: italic;
  color: var(--primary-300);
  letter-spacing: 0.06em;
  transform: rotate(-2deg);
  opacity: 0.8;
}

.brand-title {
  margin: 0;
  font-size: clamp(1.6rem, 5vw, 3.2rem);
  font-weight: 900;
  letter-spacing: -0.02em;
  text-transform: uppercase;
  display: flex;
  gap: 0.25em;
}

.glow {
  color: var(--primary-300);
  text-shadow:
    0 0 12px var(--primary-500),
    0 0 40px var(--primary-700);
  animation: glow-pulse 3s ease-in-out infinite;
}
.glow--b {
  color: var(--primary-500);
  animation-delay: 0.5s;
}

@keyframes glow-pulse {
  0%, 100% { text-shadow: 0 0 12px var(--primary-500), 0 0 40px var(--primary-700); }
  50%       { text-shadow: 0 0 20px var(--primary-300), 0 0 60px var(--primary-500); }
}

/* ══ MEDIA CAROUSEL ════════════════════════════════════════════════════════ */
.media-wrap {
  flex: 1 1 0;
  position: relative;
  overflow: hidden;
  min-height: 0;
}

.media-slide {
  position: absolute;
  inset: 0;
}

.media-asset {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

/* ══ PROMO CAROUSEL ════════════════════════════════════════════════════════ */
.promo-wrap {
  flex: 1 1 0;
  position: relative;
  overflow: hidden;
  min-height: 0;
  border-radius: 0.5rem;
}

.promo-slide {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  border: none;
  padding: 0;
  cursor: pointer;
  background: var(--surface-800);
}

.promo-bg {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.promo-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(in oklch to top, var(--surface-900) 0%, transparent 60%);
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  padding: 0.6rem 0.7rem;
  gap: 0.1rem;
  text-align: left;
}

.badge {
  font-size: 0.5rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: var(--surface-900);
  background: var(--primary-500);
  padding: 0.08rem 0.35rem;
  border-radius: 0.2rem;
  width: fit-content;
}

.promo-title {
  margin: 0;
  font-size: clamp(0.65rem, 1.6vw, 0.8rem);
  font-weight: 700;
  color: var(--primary-300);
  line-height: 1.2;
}

.promo-sub {
  margin: 0;
  font-size: clamp(0.55rem, 1.2vw, 0.68rem);
  color: var(--neutral-300);
  line-height: 1.3;
}

/* ══ MEALS ROW ═════════════════════════════════════════════════════════════ */
.meals-row {
  flex: 0 0 auto;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  /* match brand-banner height via padding logic */
  padding: 0.25rem 0.1rem 0.2rem;
}

.meals-label {
  margin: 0;
  font-size: 0.58rem;
  font-weight: 600;
  letter-spacing: 0.05em;
  color: var(--primary-500);
  text-transform: uppercase;
}

.meals-viewport {
  overflow: hidden;
}

.meals-track {
  display: flex;
  gap: 0.25rem;
  transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
  will-change: transform;
}

.meal-tile {
  flex-shrink: 0;
  aspect-ratio: 1 / 1;
  border-radius: 50%;
  overflow: hidden;
  position: relative;
  cursor: pointer;
  border: 2px solid var(--primary-700);
  background: var(--surface-800);
  padding: 0;
  transition: border-color 0.2s, transform 0.2s;
}

.meal-tile:hover {
  border-color: var(--primary-300);
  transform: scale(1.06);
}

.meal-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.meal-name {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  text-align: center;
  font-size: clamp(0.45rem, 1vw, 0.58rem);
  font-weight: 600;
  color: var(--primary-300);
  background: linear-gradient(to top, var(--surface-900), transparent);
  padding: 0.3rem 0.1rem 0.15rem;
}

/* ══ DOTS ══════════════════════════════════════════════════════════════════ */
.dots {
  position: absolute;
  bottom: 0.35rem;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  gap: 0.25rem;
  z-index: 2;
}
.dots--promo { bottom: 0.25rem; }

.dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: var(--primary-700);
  cursor: pointer;
  transition: background 0.3s, transform 0.3s;
}
.dot--on {
  background: var(--primary-300);
  transform: scale(1.5);
}

/* ══ MODAL ═════════════════════════════════════════════════════════════════ */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: var(--overlay-hard);
  z-index: 200;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
}

.modal-box {
  position: relative;
  background: var(--surface-800);
  border: 1px solid var(--primary-700);
  border-radius: 1rem;
  overflow: hidden;
  box-shadow: 0 8px 48px var(--shadow-primary);

  /* PC: 50% container width, container height */
  width: 50%;
  height: 100%;
  max-height: 90%;

  display: flex;
  flex-direction: column;
}

@media (max-width: 767px) {
  .modal-box {
    width: 100%;
    height: 100%;
    max-height: 100%;
    border-radius: 0;
  }
}

.modal-close {
  position: absolute;
  top: 0.6rem;
  right: 0.6rem;
  width: 1.8rem;
  height: 1.8rem;
  border-radius: 50%;
  border: none;
  background: var(--surface-700);
  color: var(--neutral-300);
  cursor: pointer;
  font-size: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2;
  transition: background 0.2s;
}
.modal-close:hover { background: var(--danger-700); color: white; }

.modal-img {
  width: 100%;
  height: 45%;
  object-fit: cover;
  flex-shrink: 0;
}

.modal-body {
  flex: 1;
  padding: 1rem 1.2rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  overflow-y: auto;
}

.modal-title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--primary-300);
}

.modal-price {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--primary-500);
}

.modal-desc {
  margin: 0;
  font-size: 0.85rem;
  color: var(--neutral-400);
  line-height: 1.5;
}

.modal-cta {
  margin-top: auto;
  padding: 0.65rem 1.4rem;
  border-radius: 0.6rem;
  border: none;
  background: var(--primary-500);
  color: var(--surface-900);
  font-weight: 700;
  font-size: 0.88rem;
  cursor: pointer;
  align-self: flex-start;
  transition: background 0.2s, transform 0.15s;
}
.modal-cta:hover { background: var(--primary-300); transform: translateY(-1px); }

/* ══ FADE TRANSITION ═══════════════════════════════════════════════════════ */
.fade-enter-active, .fade-leave-active { transition: opacity 0.7s ease; }
.fade-enter-from,  .fade-leave-to      { opacity: 0; }

/* ══ MOBILE LAYOUT ═════════════════════════════════════════════════════════ */
@media (max-width: 767px) {
  .wrap {
    flex-direction: column;
    gap: 0.2rem;
  }
  .col-left  { flex: 0 0 55%; }
  /* .col-right { flex: 0 0 45%; flex-direction: row; gap: 0.2rem; }

  .promo-wrap  { flex: 1.4; }
  .meals-row   { flex: 1; padding: 0.15rem; } */





  .meals-row {
    flex: 0 0 35%;  /* was taking too much space */
  }
  .meal-tile {
    aspect-ratio: 1/1;
    max-height: 100%;  /* circles shrink to row height */
  }



  .col-right {
    flex-direction: column;  /* change from row to column */
    gap: 0.2rem;
  }
  .promo-wrap { flex: 1 1 0; }   /* promo takes remaining space */
  .meals-row  { flex: 0 0 35%; } /* meals gets fixed slice at bottom */



}

/* ══ REDUCED MOTION ════════════════════════════════════════════════════════ */
@media (prefers-reduced-motion: reduce) {
  .glow { animation: none; }
  .meals-track { transition: none; }
  .fade-enter-active, .fade-leave-active { transition: none; }
}
</style>
