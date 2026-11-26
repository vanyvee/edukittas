<script setup>
defineProps({
  variant:  { type: String, default: 'primary' },   // primary | secondary | danger | success | ghost
  size:     { type: String, default: 'md' },        // sm | md | lg
  block:    Boolean,
  loading:  Boolean,
  disabled: Boolean,
  type:     { type: String, default: 'button' },
})
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :aria-busy="loading || undefined"
    :class="['ek-btn', `ek-btn--${variant}`, `ek-btn--${size}`, { 'ek-btn--block': block }]"
  >
    <span v-if="loading" class="ek-spinner" aria-hidden="true"></span>
    <slot />
  </button>
</template>
