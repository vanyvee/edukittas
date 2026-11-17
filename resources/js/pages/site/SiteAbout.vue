<script setup>
import { inject } from 'vue'

const school = inject('school')
</script>

<template>
  <div v-if="school" class="h-full overflow-y-auto pb-20 px-4 pt-6 bg-surface-300 text-neutral-900">
    <article class="max-w-3xl mx-auto">
      <h1 class="text-xl font-bold mb-3">About {{ school.name }}</h1>
      <p class="whitespace-pre-line text-neutral-900 leading-relaxed">{{ school.about }}</p>
    </article>
  </div>
</template>
