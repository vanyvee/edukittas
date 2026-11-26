<script setup>
import { useThemeStore } from '@/stores/theme'

const theme = useThemeStore()
</script>

<template>
  <section class="space-y-3" aria-labelledby="theme-picker-title">
    <div>
      <h2 id="theme-picker-title" class="text-sm font-semibold text-neutral-900">Colour theme</h2>
      <p class="text-xs text-neutral-500">Choose the palette used across Edukittas.</p>
    </div>

    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
      <button
        v-for="item in theme.themes"
        :key="item.id || 'default'"
        type="button"
        class="flex min-w-0 items-center gap-3 rounded-xl border p-3 text-left transition-colors"
        :class="theme.active === item.id
          ? 'border-primary-500 bg-primary-300/20 ring-2 ring-primary-500/20'
          : 'border-neutral-300 bg-white hover:border-primary-300'"
        :aria-pressed="theme.active === item.id"
        @click="theme.apply(item.id)"
      >
        <span class="flex shrink-0 -space-x-2" :data-theme="item.id || 'default'" aria-hidden="true">
          <i class="size-6 rounded-full border border-white" style="background:var(--primary-500)"></i>
          <i class="size-6 rounded-full border border-white" style="background:var(--secondary-300)"></i>
          <i class="size-6 rounded-full border border-white" style="background:var(--accent-300)"></i>
        </span>
        <span class="min-w-0 flex-1">
          <span class="block truncate text-sm font-semibold text-neutral-900">{{ item.name }}</span>
          <span class="block truncate text-xs text-neutral-500">{{ item.description }}</span>
        </span>
        <span v-if="theme.active === item.id" class="text-xs font-semibold text-primary-700">Active</span>
      </button>
    </div>
  </section>
</template>
