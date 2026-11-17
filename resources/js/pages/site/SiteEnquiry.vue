<script setup>
import { ref, reactive, inject } from 'vue'
import axios from 'axios'

const school = inject('school')

const form   = reactive({ name: '', phone: '', email: '', message: '' })
const status = ref('idle')   // idle | sending | done | error

/**
 * WHAT IT DOES: Sends the enquiry to the school.
 *
 * USED WHEN: When the visitor presses Send.
 *
 * GIVES BACK: Nothing.
 */
const send = async () => {
  status.value = 'sending'
  try {
    await axios.post(`/public/schools/${school.value.id}/enquiries`, { ...form })
    status.value = 'done'
  } catch {
    status.value = 'error'
  }
}

const field = 'w-full h-11 px-3 rounded-lg border border-neutral-300 bg-surface-300 text-neutral-900 text-sm'
</script>

<template>
  <div v-if="school" class="h-full overflow-y-auto pb-20 px-4 pt-6 bg-surface-300 text-neutral-900">
    <div class="max-w-3xl mx-auto space-y-4">
      <header>
        <h1 class="text-xl font-bold">Admissions</h1>
        <p class="text-neutral-700">Ask {{ school.name }} about admission. The school will contact you.</p>
      </header>

      <p v-if="status === 'done'" class="rounded-xl bg-surface-500 p-4">
        Thank you. Your enquiry has been sent to the school.
      </p>

      <form v-else class="space-y-3" @submit.prevent="send">
        <input v-model="form.name" required placeholder="Your name" :class="field" />
        <input v-model="form.phone" required placeholder="Phone number" inputmode="tel" :class="field" />
        <input v-model="form.email" type="email" placeholder="Email (optional)" :class="field" />
        <textarea
          v-model="form.message"
          required
          rows="4"
          placeholder="Your question, or the class you are asking about"
          class="w-full px-3 py-2 rounded-lg border border-neutral-300 bg-surface-300 text-neutral-900 text-sm"
        />
        <p v-if="status === 'error'" class="text-sm text-danger-500">Could not send. Please try again.</p>
        <button
          type="submit"
          :disabled="status === 'sending'"
          class="h-11 px-6 rounded-lg bg-primary text-surface-300 font-medium disabled:opacity-60"
        >{{ status === 'sending' ? 'Sending…' : 'Send' }}</button>
      </form>
    </div>
  </div>
</template>
