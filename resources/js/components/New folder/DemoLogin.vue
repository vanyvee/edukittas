<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import LoginForm from '@/components/LoginForm.vue'

// The demo screen: the normal login form, with the demo accounts listed beside it.
// Nothing special happens on login. The demo is an ordinary school, and the server refuses
// every change made inside it.
const demo    = ref(null)
const failed  = ref(false)
const copied  = ref('')

onMounted(async () => {
  try {
    demo.value = (await axios.get('/public/demo')).data
  } catch {
    failed.value = true
  }
})

/**
 * WHAT IT DOES: Copies a username or password so the visitor can paste it into the form, and shows
 *    "Copied" next to it for a moment.
 *
 * USED WHEN: When a copy button is pressed.
 *
 * GIVES BACK: Nothing.
 */
const copy = async (key, text) => {
  try {
    await navigator.clipboard.writeText(text)
    copied.value = key
    setTimeout(() => (copied.value === key ? (copied.value = '') : null), 1500)
  } catch {
    // clipboard blocked: the text is still shown on screen to type by hand
  }
}

const btn = 'h-8 px-3 rounded-md border border-neutral-300 text-xs text-neutral-900 hover:bg-surface-500'
</script>

<template>
  <div class="h-full overflow-y-auto bg-surface-300 text-neutral-900">
    <div class="max-w-5xl mx-auto p-4 pt-6 grid gap-8 lg:grid-cols-2">

      <!-- Accounts -->
      <section class="lg:order-2 space-y-4">
        <header>
          <h1 class="text-xl font-bold">Try the demo</h1>
          <p class="text-neutral-700">
            Pick a role, type its username and password into the form, and explore a working school.
            <span v-if="demo?.school">It is <strong>{{ demo.school }}</strong>.</span>
            Changes are switched off in the demo.
          </p>
        </header>

        <p v-if="failed" class="rounded-xl bg-surface-500 p-4 text-neutral-700">
          The demo is not available right now.
        </p>

        <ul v-else-if="demo" class="space-y-2">
          <li v-for="a in demo.accounts" :key="a.username" class="rounded-xl bg-surface-500 p-3">
            <p class="text-sm font-semibold">{{ a.label }}</p>

            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm">
              <span class="flex items-center gap-2">
                <span class="text-neutral-700">Username</span>
                <code class="font-mono">{{ a.username }}</code>
                <button type="button" :class="btn" @click="copy(a.username, a.username)">
                  {{ copied === a.username ? 'Copied' : 'Copy' }}
                </button>
              </span>

              <span class="flex items-center gap-2">
                <span class="text-neutral-700">Password</span>
                <code class="font-mono">{{ a.password }}</code>
                <button type="button" :class="btn" @click="copy('pw-' + a.username, a.password)">
                  {{ copied === 'pw-' + a.username ? 'Copied' : 'Copy' }}
                </button>
              </span>
            </div>
          </li>
        </ul>

        <div v-else class="space-y-2 animate-pulse">
          <div v-for="n in 5" :key="n" class="h-20 rounded-xl bg-surface-500" />
        </div>
      </section>

      <!-- The normal login form -->
      <section class="lg:order-1">
        <LoginForm />
      </section>
    </div>
  </div>
</template>
