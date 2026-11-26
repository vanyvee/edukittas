import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

/**
 * How every MyAccordion looks and moves for THIS person. Saved in the browser (localStorage has no
 * expiry). A prop passed straight to one accordion (:row-height / :stagger) overrides it there.
 */
const KEY = 'accordion-prefs'

export const ROW_HEIGHTS = [
  { id: 'compact',     label: 'Compact' },
  { id: 'comfortable', label: 'Comfortable' },
  { id: 'spacious',    label: 'Spacious' },
]
// stagger = milliseconds between one row appearing and the next. 0 = off.
export const STAGGERS = [
  { ms: 0,   label: 'Off' },
  { ms: 50,  label: 'Quick' },
  { ms: 100, label: 'Smooth' },
  { ms: 180, label: 'Relaxed' },
]

function load() {
  try {
    const o = JSON.parse(localStorage.getItem(KEY) || '{}')
    return {
      rowHeight: ROW_HEIGHTS.some((r) => r.id === o.rowHeight) ? o.rowHeight : 'comfortable',
      stagger: STAGGERS.some((s) => s.ms === o.stagger) ? o.stagger : 0,
    }
  } catch {
    return { rowHeight: 'comfortable', stagger: 0 }
  }
}

export const useAccordionPrefsStore = defineStore('accordionPrefs', () => {
  const saved = load()
  const rowHeight = ref(saved.rowHeight)
  const stagger   = ref(saved.stagger)

  watch([rowHeight, stagger], () => {
    try { localStorage.setItem(KEY, JSON.stringify({ rowHeight: rowHeight.value, stagger: stagger.value })) } catch { /* private mode */ }
  })

  return { rowHeight, stagger, heights: ROW_HEIGHTS, staggers: STAGGERS }
})
