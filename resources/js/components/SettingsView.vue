<script setup>
/**
 * Settings screen built on MyAccordion.
 *   Desktop: list of settings on the left, the chosen one opens on the right.
 *   Phone:   the chosen one opens under its row.
 * To add a setting: add an item below and one line in the <component :is> map.
 * Open it from anywhere: a MainView nav item (<template #Settings><SettingsView /></template>),
 * a modal, or a route. It reads who is logged in by itself.
 */
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import MyAccordion from './MyAccordion.vue'
import BaseCard from './BaseCard.vue'
import SettingsPanel from './SettingsPanel.vue'
import AccordionPrefs from './AccordionPrefs.vue'

const LEADERS = ['principal', 'admin', 'vice principal', 'director']   // same as the backend gate
const auth = useAuthStore()
const isLeader = computed(() => auth.isStaff && LEADERS.includes(auth.role))

const items = computed(() => [
  {
    id: 'appearance', icon: '🎨', title: 'Theme',
    subtitle: isLeader.value ? 'School colour and my style' : 'My style',
  },
  { id: 'lists', icon: '✨', title: 'Lists and motion', subtitle: 'Row height and stagger effect' },
])

const displayConfig = { avatar: 'icon', title: 'title', subtitle: 'subtitle' }
const panels = { appearance: SettingsPanel }
</script>

<template>
  <!-- :store="false" -> settings never turns on selection checkboxes, even if a parent provides a store -->
  <MyAccordion :items="items" :display-config="displayConfig" key-field="id" :store="false">

    <template #nav-header>
      <div class="px-4 py-3 border-b border-neutral-300 bg-surface-300">
        <h1 class="font-semibold text-neutral-900">Settings</h1>
        <p class="text-xs text-neutral-700">Pick one to change it</p>
      </div>
    </template>

    <!-- One slot serves both the phone (under the row) and the desktop (right side) -->
    <template #detail="{ item }">
      <div v-if="item" class="p-3 sm:p-4 overflow-y-auto h-full">
        <component :is="panels[item.id]" v-if="panels[item.id]" />
        <BaseCard v-else-if="item.id === 'lists'" padding="md">
          <h2 class="text-lg font-bold mb-3">Lists and motion</h2>
          <AccordionPrefs />
        </BaseCard>
      </div>
    </template>

    <template #right-empty>
      <p class="p-6 text-neutral-700">Pick a setting on the left.</p>
    </template>

  </MyAccordion>
</template>
