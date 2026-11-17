<script setup>
import { computed } from 'vue'
import { useGuestStore } from '@/stores/guest'

const guest = useGuestStore()
const p = computed(() => guest.profile)

// Contact lines the school has filled in.
const contacts = computed(() => {
  const c = p.value?.contacts ?? {}
  return [
    ['Phone', c.phone],
    ['Email', c.email],
    ['Address', c.address],
    ['Website', c.website],
  ].filter(([, value]) => value)
})
</script>

<template>
  <div class="h-full overflow-y-auto pt-16 pb-20 px-4 bg-surface-300 text-neutral-900">

    <!-- Loading -->
    <div v-if="guest.profileLoading" class="max-w-3xl mx-auto space-y-4 animate-pulse">
      <div class="h-20 rounded-xl bg-surface-500" />
      <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        <div v-for="n in 6" :key="n" class="h-16 rounded-xl bg-surface-500" />
      </div>
      <div class="h-32 rounded-xl bg-surface-500" />
    </div>

    <!-- Error -->
    <div v-else-if="guest.profileError" class="max-w-3xl mx-auto text-center py-16">
      <p class="text-neutral-700">{{ guest.profileError }}</p>
      <button
        type="button"
        class="mt-4 h-10 px-5 rounded-lg bg-primary text-surface-300 text-sm"
        @click="guest.selectSchool(guest.selectedId)"
      >Try again</button>
    </div>

    <!-- The picked school -->
    <article v-else-if="p" class="max-w-3xl mx-auto space-y-6">
      <header class="flex items-center gap-4">
        <img v-if="p.logo" :src="p.logo" :alt="p.name" class="w-16 h-16 rounded-xl object-cover shrink-0" />
        <div class="min-w-0">
          <h1 class="text-2xl font-bold truncate">{{ p.name }}</h1>
          <p v-if="p.motto" class="text-neutral-700">{{ p.motto }}</p>
          <p v-if="p.location" class="text-sm text-neutral-700">{{ p.location }}</p>
        </div>
      </header>

      <dl v-if="p.facts?.length" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        <div v-for="fact in p.facts" :key="fact.label" class="rounded-xl bg-surface-500 p-3">
          <dt class="text-xs text-neutral-700">{{ fact.label }}</dt>
          <dd class="font-semibold">{{ fact.value }}</dd>
        </div>
      </dl>

      <section v-if="p.about">
        <h2 class="font-semibold mb-1">About</h2>
        <p class="text-neutral-900 whitespace-pre-line">{{ p.about }}</p>
      </section>

      <section v-if="contacts.length">
        <h2 class="font-semibold mb-1">Contact</h2>
        <ul class="space-y-1 text-sm">
          <li v-for="[label, value] in contacts" :key="label">
            <span class="text-neutral-700">{{ label }}: </span>{{ value }}
          </li>
        </ul>
      </section>

      <section v-if="p.photos?.length">
        <h2 class="font-semibold mb-2">Gallery</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
          <img
            v-for="(url, i) in p.photos"
            :key="i"
            :src="url"
            :alt="`${p.name} photo ${i + 1}`"
            loading="lazy"
            class="w-full aspect-video object-cover rounded-lg"
          />
        </div>
      </section>
    </article>

    <!-- Nothing picked yet -->
    <div v-else class="max-w-3xl mx-auto py-8">
      <h1 class="text-xl font-bold">Pick a school</h1>
      <p class="text-neutral-700 mb-4">Choose one from the menu at the top, or tap one below.</p>

      <p v-if="guest.featuredLoading" class="text-sm text-neutral-700">Loading…</p>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <button
          v-for="school in guest.featured"
          :key="school.id"
          type="button"
          class="flex items-center gap-3 p-3 rounded-xl border border-neutral-300 text-left hover:bg-surface-500"
          @click="guest.selectSchool(school.id)"
        >
          <img v-if="school.logo" :src="school.logo" :alt="school.name" loading="lazy" class="w-10 h-10 rounded-full object-cover" />
          <span class="min-w-0">
            <span class="block truncate font-medium">{{ school.name }}</span>
            <span v-if="school.location" class="block truncate text-xs text-neutral-700">{{ school.location }}</span>
          </span>
        </button>
      </div>
    </div>
  </div>
</template>
