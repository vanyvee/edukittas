<script setup>
// State, then Local Government Area. The form only stores the LGA id (lg_id).
import { ref, computed, watch } from 'vue'

const props = defineProps({
  lgas:       { type: Array, default: () => [] },     // [{ id, name, state }]
  modelValue: { type: [Number, String], default: '' },
  invalid:    Boolean,
})
const emit = defineEmits(['update:modelValue'])

const state  = ref('')
const states = computed(() => [...new Set(props.lgas.map((l) => l.state))].sort())
const options = computed(() => props.lgas.filter((l) => l.state === state.value))

// keep the state box in step when the id is set from outside
watch(() => [props.modelValue, props.lgas], () => {
  const hit = props.lgas.find((l) => String(l.id) === String(props.modelValue))
  if (hit) state.value = hit.state
}, { immediate: true })

const pickState = (e) => { state.value = e.target.value; emit('update:modelValue', '') }
</script>

<template>
  <div class="grid grid-cols-2 gap-2">
    <select class="in" :class="{ bad: invalid && !state }" :value="state" @change="pickState">
      <option value="" disabled>State</option>
      <option v-for="s in states" :key="s" :value="s">{{ s }}</option>
    </select>
    <select
      class="in" :class="{ bad: invalid }" :disabled="!state"
      :value="modelValue" @change="emit('update:modelValue', Number($event.target.value))"
    >
      <option value="" disabled>LGA</option>
      <option v-for="l in options" :key="l.id" :value="l.id">{{ l.name }}</option>
    </select>
  </div>
</template>

<style scoped>
.in {
  width: 100%; padding: .65rem .8rem; border-radius: .6rem; font-size: .9rem; color: inherit; outline: none;
  background: color-mix(in oklch, currentColor 7%, transparent);
  border: 1px solid color-mix(in oklch, currentColor 25%, transparent);
}
.in:focus { border-color: var(--primary-500); box-shadow: 0 0 0 1px var(--primary-500); }
.in.bad   { border-color: var(--danger-500); }
.in option { color: var(--surface-900); background: var(--surface-300); }
</style>
