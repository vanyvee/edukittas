<script setup>
// Label + any input (slot) + hint + error, so every field in the form looks and reads the same.
defineProps({
  label: { type: String, required: true },
  error: { type: String, default: '' },
  hint:  { type: String, default: '' },
  required: Boolean,
})
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <label class="text-xs font-semibold tracking-wider uppercase opacity-80">
      {{ label }}<span v-if="required" class="text-danger-500"> *</span>
    </label>
    <slot />
    <p v-if="error" class="text-xs" style="color: var(--danger-500)" role="alert">{{ error }}</p>
    <p v-else-if="hint" class="text-xs opacity-60">{{ hint }}</p>
  </div>
</template>
