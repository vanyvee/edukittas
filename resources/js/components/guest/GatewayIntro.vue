<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { usePreferredReducedMotion } from '@vueuse/core'

// The Edukittas intro. The letters fly in from random angles, settle into the word, and breathe with
// a glow that moves through the theme colours. A moment later the Edu / Uni choice rises into view.
//
// Shown once to a guest, then remembered for a day. The parent decides whether it may show at all
// (a school's own link never shows it).
const emit = defineEmits(['done'])

const STORAGE_KEY = 'edukittas-gateway'
const REMEMBER_MS = 24 * 60 * 60 * 1000   // how long a pick is remembered
const BOXES_AFTER = 2000                    // ms between the letters settling and the boxes appearing

const word = 'EDUKITTAS'.split('')

// Each letter glows in a different theme colour, so the breathing runs through the palette.
const glowTokens = [
  '--primary-300', '--accent-500', '--secondary-300', '--primary-500', '--accent-300',
  '--secondary-500', '--primary-300', '--accent-500', '--secondary-300',
]

const reduced = usePreferredReducedMotion()

const remembered = (() => {
  try {
    const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) ?? 'null')
    return !!saved && Date.now() - saved.at < REMEMBER_MS
  } catch {
    return false
  }
})()

const open = ref(!remembered)
const stage = ref('flying')     // flying -> settled -> choose, or uni-soon
const settled = ref(false)

const letterEls = []
const firstBox = ref(null)
let flights = []
let timer = null

/**
 * WHAT IT DOES: Sends every letter in from a random point on a circle outside the screen, with a
 *    random spin and size, and lets it land in its place in the word.
 *
 * USED WHEN: Once, when the intro opens (unless the visitor prefers reduced motion).
 *
 * GIVES BACK: A promise that resolves when the last letter has landed.
 */
const flyIn = () => {
  const cx = window.innerWidth / 2
  const cy = window.innerHeight / 2
  const radius = Math.hypot(window.innerWidth, window.innerHeight) / 2 + 80

  flights = letterEls.map((el) => {
    const rect = el.getBoundingClientRect()
    const angle = Math.random() * Math.PI * 2
    const dx = cx + Math.cos(angle) * radius - (rect.left + rect.width / 2)
    const dy = cy + Math.sin(angle) * radius - (rect.top + rect.height / 2)
    const spin = (Math.random() - 0.5) * 900
    const size = 0.4 + Math.random() * 2.2

    return el.animate(
      [
        { transform: `translate(${dx}px, ${dy}px) rotate(${spin}deg) scale(${size})`, opacity: 0 },
        { opacity: 1, offset: 0.25 },
        { transform: 'translate(0, 0) rotate(0deg) scale(1)', opacity: 1 },
      ],
      {
        duration: 1100 + Math.random() * 900,
        delay: Math.random() * 500,
        easing: 'cubic-bezier(0.2, 0.9, 0.25, 1.15)',
        fill: 'both',
      },
    )
  })

  return Promise.all(flights.map((f) => f.finished))
}

/**
 * WHAT IT DOES: Lets the glow start breathing, then brings in the Edu / Uni boxes after a pause.
 *
 * USED WHEN: When the last letter has landed, or when the visitor presses Skip.
 *
 * GIVES BACK: Nothing.
 */
const settle = () => {
  if (settled.value) return

  settled.value = true
  stage.value = 'settled'

  timer = setTimeout(async () => {
    stage.value = 'choose'
    await nextTick()
    firstBox.value?.focus()
  }, reduced.value === 'reduce' ? 500 : BOXES_AFTER)
}

/**
 * WHAT IT DOES: Jumps every letter straight to its place and moves on, for visitors who do not
 *    want to wait for the animation.
 *
 * USED WHEN: When the visitor presses Skip during the flight.
 *
 * GIVES BACK: Nothing.
 */
const skip = () => {
  flights.forEach((f) => f.finish())
  settle()
}

/**
 * WHAT IT DOES: Handles the visitor's choice. Edu continues to the schools site and is remembered for
 *    a day. Uni shows a short "coming soon" note, and is not remembered.
 *
 * USED WHEN: When one of the two boxes is pressed.
 *
 * GIVES BACK: Nothing.
 */
const pick = (choice) => {
  if (choice === 'uni') {
    stage.value = 'uni-soon'
    return
  }

  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({ choice: 'edu', at: Date.now() }))
  } catch {
    // private browsing: the intro simply shows again next time
  }
  open.value = false
}

onMounted(async () => {
  if (remembered) {
    emit('done')
    return
  }

  await nextTick()

  if (reduced.value === 'reduce') {
    letterEls.forEach((el) => (el.style.opacity = '1'))
    settle()
    return
  }

  await flyIn()
  settle()
})

onBeforeUnmount(() => {
  clearTimeout(timer)
  flights.forEach((f) => f.cancel())
})
</script>

<template>
  <Transition name="gate" @after-leave="emit('done')">
    <div
      v-if="open"
      class="gate"
      role="dialog"
      aria-modal="true"
      aria-label="Choose your Edukittas"
    >
      <!-- Colour glow behind the word, turning slowly -->
      <div class="aurora" :class="{ 'aurora--on': settled }" aria-hidden="true" />

      <button v-if="stage === 'flying'" type="button" class="skip" @click="skip">Skip</button>

      <!-- The word -->
      <h1 class="word" :class="{ 'word--settled': settled }" aria-label="Edukittas">
        <span
          v-for="(letter, i) in word"
          :key="i"
          :ref="(el) => el && (letterEls[i] = el)"
          class="letter"
          aria-hidden="true"
        >
          <span class="glyph" :style="{ '--glow': `var(${glowTokens[i]})`, '--i': i }">{{ letter }}</span>
        </span>
      </h1>

      <!-- The choice -->
      <Transition name="rise">
        <div v-if="stage === 'choose'" class="boxes">
          <button ref="firstBox" type="button" class="box" @click="pick('edu')">
            <span class="box-title">Edu</span>
            <span class="box-text">Edukittas for schools</span>
          </button>
          <button type="button" class="box" @click="pick('uni')">
            <span class="box-title">Uni</span>
            <span class="box-text">Edukittas for universities</span>
          </button>
        </div>

        <div v-else-if="stage === 'uni-soon'" class="soon">
          <p class="soon-text">Edukittas for universities is on the way.</p>
          <div class="soon-actions">
            <button type="button" class="soon-btn" @click="stage = 'choose'">Back</button>
            <button type="button" class="soon-btn soon-btn--main" @click="pick('edu')">Continue to schools</button>
          </div>
        </div>
      </Transition>
    </div>
  </Transition>
</template>

<style scoped>
.gate {
  position: fixed;
  inset: 0;
  z-index: 60;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: clamp(1.5rem, 5vh, 3rem);
  padding: 1.5rem;
  overflow: hidden;
  background: var(--neutral-900);
}

/* ── Glow behind the word ───────────────────────────────────────────────── */
.aurora {
  position: absolute;
  width: min(90vw, 42rem);
  aspect-ratio: 1;
  border-radius: 50%;
  background: conic-gradient(
    from 0deg,
    var(--primary-500),
    var(--accent-500),
    var(--secondary-500),
    var(--primary-300),
    var(--primary-500)
  );
  filter: blur(70px);
  opacity: 0;
  transition: opacity 1.6s ease;
  animation: turn 16s linear infinite;
  pointer-events: none;
}
.aurora--on {
  opacity: 0.3;
}
@keyframes turn {
  to { transform: rotate(360deg); }
}

/* ── The word ───────────────────────────────────────────────────────────── */
.word {
  position: relative;
  display: flex;
  gap: 0.04em;
  margin: 0;
  font-size: clamp(1.9rem, 10.5vw, 6rem);
  font-weight: 900;
  line-height: 1;
}
.letter {
  display: inline-block;
  opacity: 0;               /* hidden until its flight starts */
  will-change: transform, opacity;
}
.glyph {
  display: inline-block;
  color: var(--primary-300);
  text-shadow: 0 0 8px var(--glow);
}

/* Breathing: each letter pulses a little after the one before it, in its own colour. */
.word--settled .glyph {
  animation: breathe 3.4s ease-in-out infinite;
  animation-delay: calc(var(--i) * 0.22s);
}
@keyframes breathe {
  0%, 100% {
    color: var(--primary-300);
    transform: scale(1);
    text-shadow: 0 0 6px var(--glow), 0 0 18px var(--glow);
  }
  50% {
    color: var(--glow);
    transform: scale(1.06);
    text-shadow: 0 0 14px var(--glow), 0 0 42px var(--glow), 0 0 80px var(--glow);
  }
}

/* ── Skip ───────────────────────────────────────────────────────────────── */
.skip {
  position: absolute;
  top: 1rem;
  right: 1rem;
  padding: 0.4rem 0.9rem;
  border: 1px solid var(--neutral-500);
  border-radius: 999px;
  background: transparent;
  color: var(--neutral-300);
  font-size: 0.8rem;
  cursor: pointer;
}
.skip:hover {
  border-color: var(--primary-300);
  color: var(--primary-300);
}

/* ── Edu / Uni boxes ────────────────────────────────────────────────────── */
.boxes {
  position: relative;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 1rem;
}
.box {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
  width: min(42vw, 11rem);
  padding: 1.4rem 1rem;
  border: 1px solid var(--primary-500);
  border-radius: 1.1rem;
  background: var(--glass-primary);
  backdrop-filter: blur(10px);
  color: var(--neutral-300);
  cursor: pointer;
  transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
}
.box:hover,
.box:focus-visible {
  transform: translateY(-5px);
  border-color: var(--accent-500);
  box-shadow: var(--shadow-primary);
  outline: none;
}
.box-title {
  font-size: clamp(1.6rem, 6vw, 2.2rem);
  font-weight: 900;
  color: var(--primary-300);
}
.box-text {
  font-size: 0.8rem;
  text-align: center;
}

/* ── "Uni is coming" note ───────────────────────────────────────────────── */
.soon {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  text-align: center;
  color: var(--neutral-300);
}
.soon-actions {
  display: flex;
  gap: 0.75rem;
}
.soon-btn {
  padding: 0.6rem 1.2rem;
  border: 1px solid var(--primary-500);
  border-radius: 0.8rem;
  background: transparent;
  color: var(--neutral-300);
  cursor: pointer;
}
.soon-btn--main {
  background: var(--primary-700);
  color: var(--primary-300);
}

/* ── Transitions ────────────────────────────────────────────────────────── */
.gate-leave-active { transition: opacity 0.6s ease, transform 0.6s ease; }
.gate-leave-to { opacity: 0; transform: scale(1.04); }

.rise-enter-active { transition: opacity 0.7s ease, transform 0.7s cubic-bezier(0.2, 0.9, 0.25, 1); }
.rise-enter-from { opacity: 0; transform: translateY(24px); }
.rise-leave-active { transition: opacity 0.2s ease; position: absolute; }
.rise-leave-to { opacity: 0; }

/* ── Reduced motion: still, with a steady glow ─────────────────────────── */
@media (prefers-reduced-motion: reduce) {
  .aurora { animation: none; }
  .word--settled .glyph { animation: none; }
  .gate-leave-active, .rise-enter-active { transition: none; }
}
</style>
