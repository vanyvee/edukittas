<script setup>
import { ref, computed, onMounted } from 'vue'
import { onClickOutside } from '@vueuse/core'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

// The demo screen. One drop-down of people to log in as. Choosing one sends that person's username and
// password to the real login, exactly as if they had been typed. The demo schools are ordinary schools,
// and the server refuses every change made inside them.
const auth   = useAuthStore()
const demo   = ref(null)
const failed = ref(false)
const open   = ref(false)
const busy   = ref(null)     // the username being logged in
const error  = ref('')
const root   = ref(null)

onClickOutside(root, () => (open.value = false))

onMounted(async () => {
  try {
    demo.value = (await axios.get('/public/demo')).data
  } catch {
    failed.value = true
  }
})

// The accounts, kept in groups for the list: Directors, Teachers, Guardians...
const groups = computed(() => {
  const byGroup = new Map()

  for (const account of demo.value?.accounts ?? []) {
    if (!byGroup.has(account.group)) byGroup.set(account.group, [])
    byGroup.get(account.group).push(account)
  }

  return [...byGroup].map(([name, accounts]) => ({ name, accounts }))
})

const busyLabel = computed(() => demo.value?.accounts.find((a) => a.username === busy.value)?.label)

/**
 * WHAT IT DOES: Logs in as the chosen person through the real login. When it works, App.vue moves the
 *    visitor to their own home page.
 *
 * USED WHEN: When a person is picked from the list.
 *
 * GIVES BACK: Nothing.
 */
const choose = async (account) => {
  if (busy.value) return

  open.value = false
  error.value = ''
  busy.value = account.username

  const result = await auth.login(account.username, account.password)

  if (!result.success) {
    error.value = auth.error ?? 'Could not log in. Please try again.'
    busy.value = null
  }
}
</script>

<template>
  <div class="min-h-full flex items-center justify-center p-4 bg-surface-300 text-neutral-900">
    <div class="w-full max-w-md space-y-5 text-center">

      <header class="space-y-1">
        <h1 class="text-2xl font-bold">Try the demo</h1>
        <p class="text-neutral-700">
          Pick a person and see Edukittas through their eyes.
          <template v-if="demo?.schools?.length">
            The demo schools are {{ demo.schools.join(', ') }}.
          </template>
          Nothing can be changed in the demo.
        </p>
      </header>

      <p v-if="failed" class="rounded-xl bg-surface-500 p-4 text-neutral-700">
        The demo is not available right now.
      </p>

      <div v-else ref="root" class="relative text-left">
        <button
          type="button"
          :disabled="!demo || !!busy"
          aria-haspopup="listbox"
          :aria-expanded="open"
          class="w-full h-12 px-4 flex items-center justify-between gap-3 rounded-xl border border-neutral-300
                 bg-surface-300 text-neutral-900 hover:bg-surface-500 disabled:opacity-60"
          @click="open = !open"
        >
          <span class="truncate">{{ busy ? 'Logging in…' : 'Pick a role' }}</span>
          <ChevronDownIcon class="w-5 h-5 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" />
        </button>

        <ul
          v-if="open"
          role="listbox"
          class="absolute left-0 right-0 mt-2 max-h-[60vh] overflow-y-auto rounded-xl bg-surface-300
                 border border-neutral-300 shadow-[var(--shadow-neutral)] z-30"
        >
          <template v-for="group in groups" :key="group.name">
            <li class="px-4 pt-3 pb-1 text-[10px] uppercase tracking-wide text-neutral-700" role="presentation">
              {{ group.name }}
            </li>
            <li v-for="account in group.accounts" :key="account.username" role="option">
              <button
                type="button"
                class="w-full px-4 py-2 text-left text-sm text-neutral-900 hover:bg-surface-500"
                @click="choose(account)"
              >{{ account.label }}</button>
            </li>
          </template>
        </ul>
      </div>

      <p v-if="busy" class="text-sm text-neutral-700">{{ busyLabel }}</p>
      <p v-if="error" class="text-sm text-danger-500">{{ error }}</p>
    </div>
  </div>
</template>
