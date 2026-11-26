<script setup>
/**
 * Settings that know who is logged in.
 *   Leaders (director, principal, vice principal, school admin): two buttons
 *       "School colour"  -> picks the palette for the whole school (saved on the server)
 *       "My style"       -> picks Glass / Soft / Elastic / Classic for themselves (saved in this browser)
 *   Everyone else (teachers, students, guardians, ...): just "My style".
 * Every pick applies instantly.
 */
import { ref, computed } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useThemeStore } from '@/stores/theme'
import BaseTabs from './BaseTabs.vue'
import BaseCard from './BaseCard.vue'

// Same list as the backend gate 'manage-school-theme' (AppServiceProvider).
const LEADERS = ['principal', 'admin', 'vice principal', 'director']

const auth  = useAuthStore()
const theme = useThemeStore()

const isLeader = computed(() => auth.isStaff && LEADERS.includes(auth.role))
const tab      = ref(isLeader.value ? 'color' : 'style')
const tabs     = [
  { id: 'color', label: 'School colour', icon: '🎨' },
  { id: 'style', label: 'My style',      icon: '✨' },
]

const toCode = (id) => id || 'default'      // CSS id '' <-> database code 'default'
const schoolCode = computed(() => auth.activeSchool?.theme || 'default')
const schoolName = computed(() => auth.activeSchool?.name || 'your school')

const saving  = ref(false)
const message = ref('')

async function pickColor(item) {
  const code = toCode(item.id)
  if (saving.value || code === schoolCode.value) return

  const before = schoolCode.value
  const school = auth.schools.find((s) => s.id === auth.schoolId)
  message.value = ''
  theme.applyCode(code)                      // instant
  saving.value = true
  try {
    await axios.put('/school/theme', { theme: code })
    if (school) school.theme = code          // keeps the app-wide watcher in step
  } catch (e) {
    theme.applyCode(before)                  // put it back
    message.value = e.response?.data?.message ?? 'Could not save the school colour.'
  } finally {
    saving.value = false
  }
}

const pickStyle = (id) => theme.applyStyle(id)   // instant, remembered in this browser
</script>

<template>
  <BaseCard padding="md" class="settings-panel">
    <h2 class="text-lg font-bold">Appearance</h2>

    <!-- Leaders get two buttons; everyone else goes straight to My style -->
    <BaseTabs v-if="isLeader" v-model="tab" :tabs="tabs" :swipe="false" class="mt-3">
      <template #default="{ active }">
        <div class="mt-4">
          <section v-if="active === 'color'">
            <p class="sp-hint">Changes the colour for everyone at {{ schoolName }}.</p>
            <div class="sp-grid">
              <button
                v-for="item in theme.themes" :key="item.id || 'default'"
                type="button" class="sp-card"
                :aria-pressed="toCode(item.id) === schoolCode"
                :disabled="saving"
                @click="pickColor(item)"
              >
                <span class="sp-swatches" :data-theme="item.id || 'default'" aria-hidden="true">
                  <i style="background: var(--primary-500)"></i>
                  <i style="background: var(--secondary-500)"></i>
                  <i style="background: var(--accent-500)"></i>
                  <i style="background: var(--surface-300)"></i>
                  <i style="background: var(--surface-900)"></i>
                </span>
                <span class="sp-name">{{ item.name }}</span>
                <span class="sp-desc">{{ item.description }}</span>
              </button>
            </div>
            <p v-if="message" class="sp-error" role="alert">{{ message }}</p>
          </section>

          <section v-else>
            <p class="sp-hint">Only for you. Saved on this device.</p>
            <div class="sp-grid">
              <button
                v-for="s in theme.styles" :key="s.id"
                type="button" class="sp-card"
                :aria-pressed="theme.style === s.id"
                @click="pickStyle(s.id)"
              >
                <span class="thumb" :class="`thumb--${s.id}`" aria-hidden="true">
                  <b class="thumb-btn"></b>
                  <span class="thumb-tabs"><i class="on"></i><i></i><i></i></span>
                </span>
                <span class="sp-name">{{ s.label }}</span>
              </button>
            </div>
          </section>
        </div>
      </template>
    </BaseTabs>

    <section v-else class="mt-3">
      <p class="sp-hint">Pick how the app looks and feels for you. Saved on this device.</p>
      <div class="sp-grid">
        <button
          v-for="s in theme.styles" :key="s.id"
          type="button" class="sp-card"
          :aria-pressed="theme.style === s.id"
          @click="pickStyle(s.id)"
        >
          <span class="thumb" :class="`thumb--${s.id}`" aria-hidden="true">
            <b class="thumb-btn"></b>
            <span class="thumb-tabs"><i class="on"></i><i></i><i></i></span>
          </span>
          <span class="sp-name">{{ s.label }}</span>
        </button>
      </div>
    </section>
  </BaseCard>
</template>

<style scoped>
.sp-hint  { font-size: .8rem; opacity: .7; margin-bottom: .75rem; }
.sp-error { margin-top: .75rem; font-size: .85rem; color: var(--danger-500); }
.sp-grid  { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
@media (min-width: 640px) { .sp-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }

.sp-card {
  display: flex; flex-direction: column; gap: .4rem; text-align: left;
  padding: .6rem; border-radius: 1rem; cursor: pointer;
  border: 1px solid color-mix(in oklch, currentColor 18%, transparent);
  background: color-mix(in oklch, currentColor 4%, transparent);
  transition: transform .2s, outline-color .2s;
  -webkit-tap-highlight-color: transparent;
}
.sp-card:active { transform: scale(.98); }
.sp-card:disabled { opacity: .6; pointer-events: none; }
.sp-card[aria-pressed="true"] { outline: 2px solid var(--primary-500); outline-offset: 1px; }
.sp-name { font-size: .85rem; font-weight: 700; }
.sp-desc { font-size: .7rem; opacity: .65; line-height: 1.25; }

/* colour samples: each strip carries its own data-theme, so it shows ITS palette */
.sp-swatches { display: flex; height: 2.25rem; border-radius: .6rem; overflow: hidden; }
.sp-swatches i { flex: 1; }

/* style samples: tiny stand-ins for a button + tabs, drawn from the live theme tokens */
.thumb { display: flex; flex-direction: column; gap: .4rem; padding: .6rem; border-radius: .9rem; min-height: 4.75rem; }
.thumb-btn { display: block; height: 1.35rem; border-radius: .5rem; }
.thumb-tabs { display: flex; gap: 3px; padding: 3px; border-radius: .6rem; }
.thumb-tabs i { flex: 1; height: .9rem; border-radius: .4rem; }

.thumb--flat { background: var(--surface-900); }
.thumb--flat .thumb-btn { background: var(--primary-700); }
.thumb--flat .thumb-tabs { background: var(--surface-700); }
.thumb--flat .thumb-tabs i.on { background: var(--primary-500); }

.thumb--glass {
  background:
    radial-gradient(circle at 15% 20%, color-mix(in oklch, var(--primary-500) 60%, transparent), transparent 55%),
    radial-gradient(circle at 90% 35%, color-mix(in oklch, var(--accent-500) 45%, transparent), transparent 50%),
    var(--surface-900);
}
.thumb--glass .thumb-btn {
  background: linear-gradient(145deg, #ffffff55, #ffffff14);
  border: 1px solid #ffffff66; box-shadow: inset 0 1px 0 #ffffff80;
}
.thumb--glass .thumb-tabs { background: #0000004d; border: 1px solid #ffffff22; }
.thumb--glass .thumb-tabs i.on { background: linear-gradient(145deg, #ffffff66, #ffffff1a); border: 1px solid #ffffff88; }

.thumb--soft { background: var(--surface-300); }
.thumb--soft .thumb-btn {
  background: var(--surface-300);
  box-shadow: 4px 4px 8px color-mix(in oklch, var(--surface-300) 78%, black),
             -4px -4px 8px color-mix(in oklch, var(--surface-300) 50%, white);
}
.thumb--soft .thumb-tabs {
  background: var(--surface-300);
  box-shadow: inset 3px 3px 6px color-mix(in oklch, var(--surface-300) 78%, black),
              inset -3px -3px 6px color-mix(in oklch, var(--surface-300) 50%, white);
}
.thumb--soft .thumb-tabs i.on {
  background: var(--surface-300);
  box-shadow: 2px 2px 4px color-mix(in oklch, var(--surface-300) 78%, black),
             -2px -2px 4px color-mix(in oklch, var(--surface-300) 50%, white);
}

.thumb--elastic { background: var(--surface-300); }
.thumb--elastic .thumb-btn { background: var(--primary-500); border-radius: 999px; box-shadow: 0 6px 12px -6px var(--primary-500); }
.thumb--elastic .thumb-tabs { background: color-mix(in oklch, var(--surface-300) 88%, var(--primary-300)); border-radius: 999px; }
.thumb--elastic .thumb-tabs i { border-radius: 999px; }
.thumb--elastic .thumb-tabs i.on { background: var(--primary-500); flex: 1.4; }
</style>
