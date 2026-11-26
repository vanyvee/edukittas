<script setup>
// Drop into Settings. Changes apply to every accordion straight away and stay in this browser.
import { useAccordionPrefsStore } from '@/stores/accordionPrefs'
const prefs = useAccordionPrefsStore()
</script>

<template>
  <div class="flex flex-col gap-4">
    <section>
      <h3 class="text-sm font-bold mb-2">Row height</h3>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="h in prefs.heights" :key="h.id" type="button" class="ap-chip"
          :aria-pressed="prefs.rowHeight === h.id" @click="prefs.rowHeight = h.id"
        >{{ h.label }}</button>
      </div>
    </section>

    <section>
      <h3 class="text-sm font-bold mb-1">Stagger effect</h3>
      <p class="text-xs opacity-70 mb-2">Rows appear one after another. Ignored if your phone is set to reduce motion.</p>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="s in prefs.staggers" :key="s.ms" type="button" class="ap-chip"
          :aria-pressed="prefs.stagger === s.ms" @click="prefs.stagger = s.ms"
        >{{ s.label }}</button>
      </div>
    </section>
  </div>
</template>

<style scoped>
.ap-chip {
  padding: .45rem .9rem; border-radius: 999px; font-size: .8rem; font-weight: 600;
  border: 1px solid color-mix(in oklch, currentColor 25%, transparent);
  background: transparent; cursor: pointer; -webkit-tap-highlight-color: transparent;
}
.ap-chip[aria-pressed="true"] { background: var(--primary-500); color: var(--on-primary); border-color: transparent; }
</style>
