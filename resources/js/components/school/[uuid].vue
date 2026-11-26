<script setup>
/**
 * Opened from the email. The link carries `expires` and `signature`; they go back to the server with the
 * username and password, and the server decides whether the link is still good.
 */
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import BaseButton from '@/components/BaseButton.vue'
import BaseCard from '@/components/BaseCard.vue'
import RegField from '@/components/register/RegField.vue'

const router = useRouter()
const form = reactive({ username: '', password: '', password_confirmation: '' })
const errors = ref({})
const banner = ref('')
const submitting = ref(false)
const doneAs = ref('')
const hasLink = new URLSearchParams(window.location.search).has('signature')

const e = (k) => (Array.isArray(errors.value[k]) ? errors.value[k][0] : (errors.value[k] ?? ''))

function validate() {
  const er = {}
  if (!/^[A-Za-z0-9_-]{1,20}$/.test(form.username)) er.username = ['Letters, numbers, - and _ only (max 20)']
  if (form.password.length < 8) er.password = ['At least 8 characters']
  else if (form.password !== form.password_confirmation) er.password = ['The two passwords do not match']
  return er
}

async function submit() {
  banner.value = ''
  errors.value = validate()
  if (Object.keys(errors.value).length) return

  submitting.value = true
  try {
    // same path and query as the emailed link, so the signature still matches
    const { data } = await axios.post(window.location.pathname + window.location.search, form)
    doneAs.value = data.username
  } catch (err) {
    const r = err.response
    if (r?.status === 422) errors.value = r.data.errors ?? {}
    else if (r?.status === 403) banner.value = 'This link has expired or is not valid. Ask your school to send you a new one.'
    else if (r?.status === 409) banner.value = 'This account has already been activated. You can log in.'
    else if (r?.status === 429) banner.value = 'Too many attempts. Please wait a few minutes and try again.'
    else banner.value = 'Could not reach the server. Check your connection and try again.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="w-full max-w-md mx-auto p-4 pt-10">
    <BaseCard padding="md">

      <div v-if="doneAs" class="flex flex-col items-center text-center gap-3 py-4">
        <div class="text-5xl">✅</div>
        <h1 class="text-xl font-bold">Your account is ready</h1>
        <p class="text-sm opacity-80">Log in as <strong>{{ doneAs }}</strong> with the password you just chose.</p>
        <BaseButton size="lg" @click="router.push('/login')">Go to login</BaseButton>
      </div>

      <div v-else-if="!hasLink" class="py-6 text-center">
        <h1 class="text-xl font-bold mb-2">This link is not complete</h1>
        <p class="text-sm opacity-80">Open the link from your invitation email again.</p>
      </div>

      <form v-else novalidate class="flex flex-col gap-4" @submit.prevent="submit">
        <div>
          <h1 class="text-xl font-bold">Set up your account</h1>
          <p class="text-sm opacity-70">Choose how you will log in.</p>
        </div>

        <RegField label="Username" required :error="e('username')" hint="Letters, numbers, - and _">
          <input v-model="form.username" class="in" :class="{ bad: e('username') }" maxlength="20" autocapitalize="none" autocomplete="username" />
        </RegField>
        <RegField label="Password" required :error="e('password')" hint="At least 8 characters">
          <input v-model="form.password" type="password" class="in" :class="{ bad: e('password') }" autocomplete="new-password" />
        </RegField>
        <RegField label="Repeat password" required>
          <input v-model="form.password_confirmation" type="password" class="in" :class="{ bad: e('password') }" autocomplete="new-password" />
        </RegField>

        <p v-if="banner" class="text-sm" style="color: var(--danger-500)" role="alert">{{ banner }}</p>

        <BaseButton type="submit" size="lg" block :loading="submitting">Activate my account</BaseButton>
      </form>

    </BaseCard>
  </div>
</template>

<style scoped>
.in {
  width: 100%; padding: .65rem .8rem; border-radius: .6rem; font-size: .9rem; color: inherit; outline: none;
  background: color-mix(in oklch, currentColor 7%, transparent);
  border: 1px solid color-mix(in oklch, currentColor 25%, transparent);
}
.in:focus { border-color: var(--primary-500); box-shadow: 0 0 0 1px var(--primary-500); }
.in.bad   { border-color: var(--danger-500); }
</style>
