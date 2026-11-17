<script setup>
import { ref, computed, watch, inject } from 'vue'
import { useIntervalFn, useDocumentVisibility, usePreferredReducedMotion } from '@vueuse/core'
import { useGuestStore } from '@/stores/guest'

const guest = useGuestStore()
const switchTo = inject('switchTo')

// The platform showcase: the featured schools' cover photos, fading one into the next.
const covers = computed(() => guest.featured.filter((s) => s.cover))
const index = ref(0)
const current = computed(() => covers.value[index.value] ?? null)

const next = () => {
  if (covers.value.length > 1) index.value = (index.value + 1) % covers.value.length
}

// Stops fading while the tab is hidden or when the visitor prefers reduced motion.
const visibility = useDocumentVisibility()
const motion = usePreferredReducedMotion()
const { pause, resume } = useIntervalFn(next, 5000)
watch([visibility, motion], ([v, m]) => (v === 'hidden' || m === 'reduce' ? pause() : resume()), { immediate: true })
</script>

<template>
  <section class="relative w-full h-full overflow-hidden bg-neutral-900">

    <!-- Photos, stacked and cross-faded -->
    <img
      v-for="(school, i) in covers"
      :key="school.id"
      :src="school.cover"
      :alt="school.name"
      :loading="i === 0 ? 'eager' : 'lazy'"
      :fetchpriority="i === 0 ? 'high' : 'auto'"
      class="absolute inset-0 w-full h-full object-cover transition-opacity duration-1000"
      :class="i === index ? 'opacity-100' : 'opacity-0'"
    />

    <!-- Brand fallback while there are no photos -->
    <div v-if="!covers.length" class="absolute inset-0 bg-gradient-to-br from-primary-700 to-neutral-900" />

    <!-- Readability overlay -->
    <div class="absolute inset-0 bg-gradient-to-t from-[var(--overlay-hard)] via-[var(--overlay-soft)] to-transparent" />

    <div class="absolute inset-x-0 bottom-0 p-6 pb-20 sm:p-10 sm:pb-24 max-w-3xl text-neutral-300">
      <p v-if="current" class="text-sm opacity-80 mb-1">Featured school</p>
      <h1 class="text-3xl sm:text-5xl font-bold leading-tight">
        {{ current?.name ?? 'Edukittas' }}
      </h1>
      <p class="mt-3 text-base sm:text-lg opacity-90">
        {{ current?.motto ?? 'Tests, scores, classes and fees for your school, in one place.' }}
      </p>

      <div class="mt-6 flex flex-wrap gap-3">
        <button
          type="button"
          class="h-11 px-6 rounded-lg bg-primary text-surface-300 font-medium"
          @click="switchTo('Register')"
        >Register your school</button>
        <button
          type="button"
          class="h-11 px-6 rounded-lg border border-neutral-300 font-medium"
          @click="switchTo('Demo')"
        >Try the demo</button>
        <button
          type="button"
          class="h-11 px-6 rounded-lg border border-neutral-300 font-medium"
          @click="switchTo('Login')"
        >Login</button>
      </div>
    </div>

    <!-- Dots -->
    <div v-if="covers.length > 1" class="absolute top-16 right-4 flex gap-1.5">
      <button
        v-for="(school, i) in covers"
        :key="school.id"
        type="button"
        :aria-label="school.name"
        class="w-2 h-2 rounded-full transition-all"
        :class="i === index ? 'bg-accent w-5' : 'bg-neutral-300'"
        @click="index = i"
      />
    </div>
  </section>
</template>
