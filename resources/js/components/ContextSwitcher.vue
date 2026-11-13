<script setup>
import { ref, computed } from 'vue'
import { onClickOutside } from '@vueuse/core'
import { ChevronDownIcon, CheckIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'

// Shows where the person is working and lets them change it:
//   staff     -> their schools
//   guardian  -> their children
//   student   -> their school (nothing to switch)
// One entry shows as a plain label, two or three as buttons, more than three as a dropdown.
const auth = useAuthStore()

const items = computed(() => {
  if (auth.isGuardian) {
    return auth.wards.map((w) => ({
      id: w.ward.id,
      label: [w.ward.first_name, w.ward.surname].filter(Boolean).join(' ') || w.ward.surname,
      sub: w.school?.name ?? 'Not enrolled in a school',
      active: w.ward.id === auth.wardId,
      disabled: !w.school,
      pick: () => auth.switchWard(w.ward.id),
    }))
  }

  if (auth.isStaff || auth.isStudent) {
    return auth.schools.map((s) => ({
      id: s.id,
      label: s.name,
      sub: null,
      active: s.id === auth.schoolId,
      disabled: false,
      pick: () => auth.switchSchool(s.id),
    }))
  }

  return []
})

const active = computed(() => items.value.find((i) => i.active) ?? items.value[0] ?? null)

const busy = ref(false)
const open = ref(false)
const root = ref(null)
onClickOutside(root, () => (open.value = false))

// The stores and slides still hold the previous school's data in memory, so after a successful
// switch the page is reloaded. That is the safest way to make sure nothing from the old school is
// left on screen.
const choose = async (item) => {
  if (busy.value || item.active || item.disabled) return

  busy.value = true
  const ok = await item.pick()
  busy.value = false
open.value = false
  if (ok && !auth.isGuardian) {
    window.location.reload()
  } else {
    busy.value = false
    open.value = false
  }
}






</script>

<template>
  <div v-if="items.length" class="flex items-center min-w-0">

    <!-- One: just say where you are -->
    <span v-if="items.length === 1" class="truncate text-sm font-medium text-neutral-900">
      {{ items[0].label }}
    </span>

    <!-- Two or three: buttons -->
    <div v-else-if="items.length <= 3" class="flex gap-2 overflow-x-auto">
      <button
        v-for="item in items"
        :key="item.id"
        type="button"
        :title="item.sub ?? ''"
        :disabled="busy || item.disabled"
        class="h-9 px-3 shrink-0 rounded-lg text-sm disabled:opacity-50"
        :class="item.active
          ? 'bg-primary text-surface-300'
          : 'border border-neutral-300 text-neutral-900 hover:bg-surface-500'"
        @click="choose(item)"
      >{{ item.label }}</button>
    </div>

    <!-- More than three: one dropdown -->
    <div v-else ref="root" class="relative min-w-0">
      <button
        type="button"
        :disabled="busy"
        class="flex items-center gap-2 max-w-full h-9 px-3 rounded-lg border border-neutral-300 text-sm text-neutral-900 hover:bg-surface-500"
        @click="open = !open"
      >
        <span class="truncate">{{ active?.label }}</span>
        <ChevronDownIcon class="w-4 h-4 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" />
      </button>

      <ul
        v-if="open"
        class="absolute left-0 mt-2 w-72 max-w-[85vw] max-h-80 overflow-y-auto rounded-xl
               bg-surface-300 border border-neutral-300 shadow-[var(--shadow-neutral)] z-30"
      >
        <li v-for="item in items" :key="item.id">
          <button
            type="button"
            :disabled="item.disabled"
            class="w-full flex items-center gap-3 px-3 py-2 text-left hover:bg-surface-500 disabled:opacity-50"
            @click="choose(item)"
          >
            <span class="min-w-0 flex-1">
              <span class="block truncate text-sm text-neutral-900">{{ item.label }}</span>
              <span v-if="item.sub" class="block truncate text-xs text-neutral-500">{{ item.sub }}</span>
            </span>
            <CheckIcon v-if="item.active" class="w-4 h-4 shrink-0 text-accent" />
          </button>
        </li>
      </ul>
    </div>
  </div>
</template>
