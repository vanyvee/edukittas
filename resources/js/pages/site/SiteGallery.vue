<script setup>
import { ref, inject } from 'vue'

const school = inject('school')
const opened = ref(null)   // the photo shown large
</script>

<template>
  <div v-if="school" class="h-full overflow-y-auto pb-20 px-4 pt-6 bg-surface-300 text-neutral-900">
    <div class="max-w-3xl mx-auto">
      <h1 class="text-xl font-bold mb-3">Gallery</h1>

      <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
        <button
          v-for="(url, i) in school.photos"
          :key="i"
          type="button"
          class="block rounded-lg overflow-hidden"
          @click="opened = url"
        >
          <img :src="url" :alt="`${school.name} photo ${i + 1}`" loading="lazy" class="w-full aspect-video object-cover" />
        </button>
      </div>
    </div>

    <!-- Large view -->
    <div
      v-if="opened"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[var(--overlay-hard)]"
      @click="opened = null"
    >
      <img :src="opened" alt="" class="max-w-full max-h-full rounded-lg" />
    </div>
  </div>
</template>
