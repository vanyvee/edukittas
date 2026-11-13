<script setup>
import { useFormStore } from '@/stores/formStore'
import { useAuthStore }       from '@/stores/auth'
import { useRouter }          from 'vue-router'
import FormField              from '@/components/FormField.vue'

const formStore = useFormStore()
const auth      = useAuthStore()
const router    = useRouter()
//const switchTo = inject('switchTo')
// Build form with only the fields we need
const { handleSubmit, setErrors } = formStore.buildForm([
  'username',
  'password',
])

const onSubmit = handleSubmit(async (values) => {
  const result = await auth.login(values.username, values.password)
  if (!result.success) {
    // on success App.vue moves the person to their own world
  formStore.applyServerErrors({ setErrors }, result.error)
  } else {
    // Apply server errors back into the form
    formStore.applyServerErrors({ setErrors }, result.error)
  }
})
</script>

<template>
  <div class="w-full h-full flex items-center justify-center bg-neutral-900">

    <div class="flex flex-col gap-5
                w-[calc(100%-10px)] md:w-full md:max-w-sm md:mx-auto">

      <!-- Brand -->
      <div class="text-center">
      
        <h1 class="text-4xl font-black uppercase tracking-tight text-primary-300"
            style="text-shadow: 0 0 20px var(--primary-500), 0 0 50px var(--primary-700);">
          login
        </h1>
      </div>

      <!-- Card -->
      <div class="bg-neutral-700 border border-neutral-500 rounded-2xl
                  p-7 py-10 md:py-7 flex flex-col gap-5"
           style="box-shadow: 0 8px 40px color-mix(in oklch, var(--primary-900) 60%, transparent);">

        <FormField
          type="text"
          name="username"
          label="Username"
          placeholder="Enter your username"
        />

        <FormField
          type="password"
          name="password"
          label="Password"
          placeholder="Enter your password"
        />

        <!-- Server error -->
        <p v-if="auth.error && typeof auth.error === 'string'"
           class="text-xs text-secondary-300 text-center -mt-2">
          {{ auth.error }}
        </p>

        <!-- Submit -->
        <button
          @click="onSubmit"
          :disabled="auth.loading"
          class="w-full py-2.5 rounded-lg font-bold text-sm tracking-wide
                 bg-primary-500 text-neutral-900
                 hover:bg-primary-300 active:scale-95
                 disabled:opacity-40 disabled:cursor-not-allowed
                 transition-all duration-200"
        >
          <span v-if="auth.loading">Signing in...</span>
          <span v-else>Sign In</span>
        </button>

      </div>


    </div>
  </div>
</template>