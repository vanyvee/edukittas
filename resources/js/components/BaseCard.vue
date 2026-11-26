<script setup>
defineProps({
  inset:   Boolean,                                  // pressed-in surface (good for content panels)
  padding: { type: String, default: 'md' },          // none | sm | md | lg
})
</script>

<template>
  <div :class="['ek-card', `ek-card--p-${padding}`, { 'ek-card--inset': inset }]">
    <slot />
  </div>
</template>
