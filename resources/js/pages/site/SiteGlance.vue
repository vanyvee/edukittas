<script setup>
import { inject } from 'vue'

const school   = inject('school')            // a ref holding the school
const goToName = inject('goToSlideByName')
</script>

<template>
  <div v-if="school" class="h-full overflow-y-auto pb-20 bg-surface-300 text-neutral-900">

    <!-- Cover -->
    <div class="relative h-44 sm:h-64 overflow-hidden bg-neutral-900">
      <img v-if="school.cover" :src="school.cover" :alt="school.name" class="absolute inset-0 w-full h-full object-cover" />
      <div v-else class="absolute inset-0 bg-gradient-to-br from-primary-700 to-neutral-900" />
      <div class="absolute inset-0 bg-gradient-to-t from-[var(--overlay-hard)] to-transparent" />
    </div>

    <div class="relative max-w-3xl mx-auto px-4 -mt-10 space-y-6">
      <!-- Name -->
      <header class="flex items-end gap-4">
        <img
          v-if="school.logo"
          :src="school.logo"
          :alt="school.name"
          class="w-20 h-20 rounded-2xl object-cover shrink-0 border border-neutral-300 bg-surface-300"
        />
        <div class="min-w-0 pb-1">
          <h1 class="text-2xl font-bold leading-tight">{{ school.name }}</h1>
          <p v-if="school.motto" class="text-neutral-700">{{ school.motto }}</p>
          <p v-if="school.location" class="text-sm text-neutral-700">{{ school.location }}</p>
        </div>
      </header>

      <!-- Actions -->
      <div class="flex flex-wrap gap-3">
        <a
          v-if="school.contacts?.whatsapp"
          :href="school.contacts.whatsapp"
          target="_blank"
          rel="noopener"
          class="h-11 px-5 inline-flex items-center rounded-lg bg-primary text-surface-300 font-medium"
        >Chat on WhatsApp</a>
        <a
          v-if="school.contacts?.phone"
          :href="`tel:${school.contacts.phone}`"
          class="h-11 px-5 inline-flex items-center rounded-lg border border-neutral-300 hover:bg-surface-500"
        >Call</a>
        <button
          type="button"
          class="h-11 px-5 rounded-lg border border-neutral-300 hover:bg-surface-500"
          @click="goToName('Admissions')"
        >Ask about admission</button>
      </div>

      <!-- Facts -->
      <dl v-if="school.facts?.length" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div v-for="fact in school.facts" :key="fact.label" class="rounded-xl bg-surface-500 p-3">
          <dt class="text-xs text-neutral-700">{{ fact.label }}</dt>
          <dd class="font-semibold">{{ fact.value }}</dd>
        </div>
      </dl>
    </div>
  </div>
</template>
