<script setup>
import { ref, inject } from 'vue'
import { onClickOutside } from '@vueuse/core'
import { ChevronDownIcon, CheckIcon } from '@heroicons/vue/24/outline'
import { useGuestStore } from '@/stores/guest'

const guest = useGuestStore()
const switchTo = inject('switchTo')

const open = ref(false)
const picker = ref(null)
onClickOutside(picker, () => (open.value = false))

const pick = (school) => {
  guest.selectSchool(school.id)
  open.value = false
}
</script>

<template>
  <header
    class="absolute top-0 inset-x-0 z-20 h-14 flex items-center justify-between gap-3 pl-3 pr-3
           bg-surface-300 border-b border-neutral-300"
  >
    <!-- Featured schools picker -->
    <div ref="picker" class="relative min-w-0">
      <button
        type="button"
        class="flex items-center gap-2 max-w-full h-9 px-3 rounded-lg border border-neutral-300
               text-neutral-900 text-sm hover:bg-surface-500"
        @click="open = !open"
      >
        <span class="truncate">{{ guest.selectedName ?? 'Featured schools' }}</span>
        <ChevronDownIcon class="w-4 h-4 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" />
      </button>

      <div
        v-if="open"
        class="absolute left-0 mt-2 w-72 max-w-[85vw] max-h-80 overflow-y-auto rounded-xl
               bg-surface-300 border border-neutral-300 shadow-[var(--shadow-neutral)]"
      >
        <p v-if="guest.featuredLoading" class="p-3 text-sm text-neutral-700">Loading…</p>
        <p v-else-if="!guest.featured.length" class="p-3 text-sm text-neutral-700">No featured schools yet.</p>

        <button
          v-for="school in guest.featured"
          :key="school.id"
          type="button"
          class="w-full flex items-center gap-3 px-3 py-2 text-left hover:bg-surface-500"
          @click="pick(school)"
        >
          <img
            v-if="school.logo"
            :src="school.logo"
            :alt="school.name"
            loading="lazy"
            class="w-8 h-8 rounded-full object-cover shrink-0"
          />
          <span
            v-else
            class="w-8 h-8 rounded-full shrink-0 flex items-center justify-center text-xs font-semibold bg-primary text-surface-300"
          >{{ school.name.charAt(0) }}</span>

          <span class="min-w-0 flex-1">
            <span class="block truncate text-sm text-neutral-900">{{ school.name }}</span>
            <span v-if="school.location" class="block truncate text-xs text-neutral-700">{{ school.location }}</span>
          </span>

          <CheckIcon v-if="school.id === guest.selectedId" class="w-4 h-4 shrink-0 text-accent" />
        </button>
      </div>
    </div>

    <!-- Same two actions as the side menu; hidden on phones where space is tight -->
    <div class="hidden sm:flex items-center gap-2 shrink-0">
      <button
        type="button"
        class="h-9 px-4 rounded-lg border border-neutral-300 text-neutral-900 text-sm hover:bg-surface-500"
        @click="switchTo('Login')"
      >Login</button>
      <button
        type="button"
        class="h-9 px-4 rounded-lg bg-primary text-surface-300 text-sm"
        @click="switchTo('Register')"
      >Register</button>
    </div>
  </header>
</template>
