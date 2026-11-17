<script setup>
import { ref, reactive } from 'vue'
import axios from 'axios'

const steps = [
  { icon: '📝', title: 'Apply', text: 'Tell us a little about yourself and how you will share Edukittas.' },
  { icon: '🔗', title: 'Share your link', text: 'Once approved you get a personal link to give to schools.' },
  { icon: '💰', title: 'Earn', text: 'You earn a commission when a school you introduced joins.' },
]

const form = reactive({ name: '', phone: '', email: '', message: '' })
const status = ref('idle') // idle | sending | done | error

const apply = async () => {
  status.value = 'sending'
  try {
    await axios.post('/public/affiliates', { ...form })
    status.value = 'done'
  } catch {
    status.value = 'error'
  }
}

const field = 'w-full h-11 px-3 rounded-lg border border-neutral-300 bg-surface-300 text-neutral-900 text-sm'
</script>

<template>
  <div class="h-full overflow-y-auto pt-16 pb-20 px-4 bg-surface-300 text-neutral-900">
    <div class="max-w-3xl mx-auto space-y-8">

      <header>
        <h1 class="text-2xl font-bold">Become an Edukittas affiliate</h1>
        <p class="text-neutral-700 mt-1">
          Introduce schools to Edukittas and earn a commission when they join. Commission terms are shared when your application is approved.
        </p>
      </header>

      <ol class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <li v-for="(s, i) in steps" :key="s.title" class="rounded-xl bg-surface-500 p-4">
          <p class="text-2xl">{{ s.icon }}</p>
          <p class="font-semibold mt-1">{{ i + 1 }}. {{ s.title }}</p>
          <p class="text-sm text-neutral-700">{{ s.text }}</p>
        </li>
      </ol>

      <section>
        <h2 class="font-semibold mb-3">Apply now</h2>

        <p v-if="status === 'done'" class="rounded-xl bg-surface-500 p-4 text-neutral-900">
          Thank you. We received your application and will contact you.
        </p>

        <form v-else class="space-y-3" @submit.prevent="apply">
          <input v-model="form.name" required placeholder="Full name" :class="field" />
          <input v-model="form.phone" required placeholder="Phone number" inputmode="tel" :class="field" />
          <input v-model="form.email" type="email" placeholder="Email (optional)" :class="field" />
          <textarea
            v-model="form.message"
            rows="3"
            placeholder="How will you share Edukittas?"
            class="w-full px-3 py-2 rounded-lg border border-neutral-300 bg-surface-300 text-neutral-900 text-sm"
          />
          <p v-if="status === 'error'" class="text-sm text-danger-500">Could not send. Please try again.</p>
          <button
            type="submit"
            :disabled="status === 'sending'"
            class="h-11 px-6 rounded-lg bg-primary text-surface-300 font-medium disabled:opacity-60"
          >{{ status === 'sending' ? 'Sending…' : 'Apply' }}</button>
        </form>
      </section>
    </div>
  </div>
</template>
