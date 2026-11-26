<script setup>
/**
 * Pick one image (or several with `multiple`), see it straight away, get told at once if it is
 * the wrong type, too big or too small. The server checks again; this only saves a round trip.
 * v-model is a File, or File[] when `multiple`.
 */
import { ref, watch, onBeforeUnmount } from 'vue'

const props = defineProps({
  modelValue: { default: null },
  label:    { type: String, required: true },
  hint:     { type: String, default: '' },
  multiple: Boolean,
  max:      { type: Number, default: 4 },
  maxMb:    { type: Number, default: 2 },
  minW:     { type: Number, default: 0 },
  minH:     { type: Number, default: 0 },
  aspect:   { type: String, default: 'aspect-square' },   // shape of the preview box (a Tailwind class)
  required: Boolean,
  error:    { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue'])

const OK = ['image/jpeg', 'image/png', 'image/webp']
const items = ref([])            // [{ file, url }]
const localError = ref('')
const input = ref(null)

// coming back to a step: rebuild previews from what the parent already holds
const initial = props.modelValue ? [].concat(props.modelValue) : []
items.value = initial.filter(Boolean).map((file) => ({ file, url: URL.createObjectURL(file) }))

const emitValue = () =>
  emit('update:modelValue', props.multiple ? items.value.map((i) => i.file) : (items.value[0]?.file ?? null))

const sizeOf = (url) => new Promise((resolve, reject) => {
  const img = new Image()
  img.onload  = () => resolve({ w: img.naturalWidth, h: img.naturalHeight })
  img.onerror = reject
  img.src = url
})

async function onPick(e) {
  localError.value = ''
  const picked = [...e.target.files]
  e.target.value = ''                         // lets the same file be chosen again

  for (const f of picked) {
    if (!OK.includes(f.type))                    { localError.value = `${f.name}: use a JPG, PNG or WebP image.`; continue }
    if (f.size > props.maxMb * 1024 * 1024)      { localError.value = `${f.name}: larger than ${props.maxMb} MB.`; continue }
    if (props.multiple && items.value.length >= props.max) { localError.value = `Up to ${props.max} images.`; break }

    const url = URL.createObjectURL(f)
    try {
      const { w, h } = await sizeOf(url)
      if (w < props.minW || h < props.minH) {
        URL.revokeObjectURL(url)
        localError.value = `${f.name}: needs at least ${props.minW}×${props.minH}px (this one is ${w}×${h}).`
        continue
      }
    } catch {
      URL.revokeObjectURL(url)
      localError.value = `${f.name}: could not be read as an image.`
      continue
    }

    if (!props.multiple) { items.value.forEach((i) => URL.revokeObjectURL(i.url)); items.value = [] }
    items.value.push({ file: f, url })
  }
  emitValue()
}

function remove(i) {
  URL.revokeObjectURL(items.value[i].url)
  items.value.splice(i, 1)
  localError.value = ''
  emitValue()
}

// the parent cleared the value (form reset)
watch(() => props.modelValue, (v) => {
  const empty = !v || (Array.isArray(v) && !v.length)
  if (empty && items.value.length) {
    items.value.forEach((i) => URL.revokeObjectURL(i.url))
    items.value = []
  }
})
onBeforeUnmount(() => items.value.forEach((i) => URL.revokeObjectURL(i.url)))

const canAdd = () => props.multiple ? items.value.length < props.max : true
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <label class="text-xs font-semibold tracking-wider uppercase opacity-80">
      {{ label }}<span v-if="required" style="color: var(--danger-500)"> *</span>
    </label>

    <div class="grid gap-2" :class="multiple ? 'grid-cols-2 sm:grid-cols-4' : 'grid-cols-1'">
      <div
        v-for="(it, i) in items" :key="it.url"
        class="relative overflow-hidden rounded-xl border" :class="aspect"
        style="border-color: color-mix(in oklch, currentColor 25%, transparent)"
      >
        <img :src="it.url" alt="" class="w-full h-full object-cover" />
        <button
          type="button" aria-label="Remove image"
          class="absolute top-1 right-1 w-7 h-7 rounded-full text-sm leading-none flex items-center justify-center"
          style="background: #000a; color: #fff" @click="remove(i)"
        >✕</button>
      </div>

      <button
        v-if="canAdd() && (multiple || !items.length)"
        type="button" :class="aspect"
        class="rounded-xl border-2 border-dashed flex flex-col items-center justify-center gap-1 text-sm
        opacity-80 hover:opacity-100 transition-opacity touch-manipulation"
        style="border-color: color-mix(in oklch, currentColor 35%, transparent)"
        @click="input.click()"
      >
        <span class="text-2xl leading-none">＋</span>
        <span>{{ multiple ? 'Add image' : 'Choose image' }}</span>
      </button>
    </div>

    <input ref="input" type="file" class="hidden" accept="image/jpeg,image/png,image/webp" :multiple="multiple" @change="onPick" />

    <p v-if="localError || error" class="text-xs" style="color: var(--danger-500)" role="alert">{{ localError || error }}</p>
    <p v-else-if="hint" class="text-xs opacity-60">{{ hint }}</p>
  </div>
</template>
