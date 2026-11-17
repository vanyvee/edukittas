<script setup>
import { computed, inject } from 'vue'

const school = inject('school')

const c = computed(() => school.value?.contacts ?? {})
const mapLink = computed(() =>
  c.value.address
    ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(`${c.value.address}, ${school.value.location}`)}`
    : null
)
</script>

<template>
  <div v-if="school" class="h-full overflow-y-auto pb-20 px-4 pt-6 bg-surface-300 text-neutral-900">
    <div class="max-w-3xl mx-auto space-y-5">
      <h1 class="text-xl font-bold">Contact {{ school.name }}</h1>

      <a
        v-if="c.whatsapp"
        :href="c.whatsapp"
        target="_blank"
        rel="noopener"
        class="h-12 px-6 inline-flex items-center rounded-lg bg-primary text-surface-300 font-medium"
      >Chat on WhatsApp</a>

      <ul class="space-y-3">
        <li v-if="c.phone" class="rounded-xl bg-surface-500 p-3">
          <p class="text-xs text-neutral-700">Phone</p>
          <a :href="`tel:${c.phone}`" class="font-medium">{{ c.phone }}</a>
        </li>
        <li v-if="c.email" class="rounded-xl bg-surface-500 p-3">
          <p class="text-xs text-neutral-700">Email</p>
          <a :href="`mailto:${c.email}`" class="font-medium break-all">{{ c.email }}</a>
        </li>
        <li v-if="c.address" class="rounded-xl bg-surface-500 p-3">
          <p class="text-xs text-neutral-700">Address</p>
          <p class="font-medium">{{ c.address }}</p>
          <a v-if="mapLink" :href="mapLink" target="_blank" rel="noopener" class="text-sm underline">Open in maps</a>
        </li>
        <li v-if="c.website" class="rounded-xl bg-surface-500 p-3">
          <p class="text-xs text-neutral-700">Website</p>
          <a :href="c.website" target="_blank" rel="noopener" class="font-medium break-all">{{ c.website }}</a>
        </li>
      </ul>
    </div>
  </div>
</template>
