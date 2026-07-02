<script setup>
import { useFormStore } from '@/stores/formStore'
import { useAuthStore }       from '@/stores/auth'
import { useRouter }          from 'vue-router'
import FormField              from '@/components/FormField.vue'
import { ref } from 'vue'

const formStore = useFormStore()
const auth      = useAuthStore()
const router    = useRouter()
const switchTo = inject('switchTo')
const successMessage = ref('')
const { handleSubmit, setErrors, resetForm } = formStore.buildForm([
  'first_name',
  'surname',
  'username',
    'sex',
  'phone',
  'address',
  'password',
  'password_confirmation',
  'role',
  'dob_day',
  'dob_month',
  'dob_year',
])

const onSubmit = handleSubmit(async (values) => {
  const uuid = localStorage.getItem('tb_customer_uuid')
  const result = await auth.register({ ...values })

  if (result.success) {
    // if (result.data?.user?.uuid) {
    //   localStorage.setItem('tb_customer_uuid', result.data.user.uuid)
    // }
    resetForm()
 successMessage.value = '🎉 Account created! Please sign in.'
    setTimeout(() => {
      successMessage.value = ''
      switchTo('Login')
    }, 1500)
  } else {
    formStore.applyServerErrors({ setErrors }, result.error)
  }
})
</script>

<template>
  <div class="w-full h-full flex items-center justify-center bg-neutral-900 py-4 overflow-y-auto">

    <div class="flex flex-col gap-4
                w-[calc(100%-10px)] md:w-full md:max-w-sm md:mx-auto my-auto">

      <!-- Brand -->
      <div class="text-center">
      
        <h1 class="text-4xl font-black uppercase tracking-tight text-primary-300"
            style="text-shadow: 0 0 20px var(--primary-500), 0 0 50px var(--primary-700);">
          sign up
        </h1>
      </div>

      <!-- Card -->
      <div class="bg-neutral-700 border border-neutral-500 rounded-2xl
                  p-7 py-8 md:py-7 flex flex-col gap-4"
           style="box-shadow: 0 8px 40px color-mix(in oklch, var(--primary-900) 60%, transparent);">

        <!-- Name row -->
        <div class="flex gap-3">
          <div class="flex-1">
            <FormField
              type="text"
              name="first_name"
              label="First Name"
              placeholder="Ada"
            />
          </div>
          <div class="flex-1">
            <FormField
              type="text"
              name="surname"
              label="Surname"
              placeholder="Okafor"
            />
          </div>
        </div>

        <div class="flex gap-3">
  <div class="flex-1">
    <FormField
      type="text"
      name="username"
      label="Username"
      placeholder="Choose a username"
    />
  </div>
  <div class="flex-1">
    <FormField
      type="select"
      name="sex"
      label="Gender"
      :options="[
        { label: 'Male',   value: 'male'   },
        { label: 'Female', value: 'female' },
      ]"
    />
  </div>
</div>



<div class="flex gap-3">
    <div class="flex-1 min-w-0">
      <FormField type="number" name="dob_day"   label="Day"  placeholder="DD" />
    </div>
    <div class="flex-1 min-w-0">
      <FormField type="number" name="dob_month" label="Month" placeholder="MM" />
    </div>
    <div class="flex-1 min-w-0">
      <FormField type="number" name="dob_year"  label="Year" placeholder="YYYY" />
    </div>
  </div>
       



        <div class="flex gap-3">
  <div class="flex-1">
    <FormField
      type="text"
      name="address"
      label="Address"
      placeholder="your address"
    />
  </div>
  <div class="flex-1">
    <FormField
          type="tel"
          name="phone"
          label="Phone"
          placeholder="08012345678"
        />
  </div>
</div>


<!-- Staff only fields -->
<template v-if="auth.canViewAnalytics || auth.isAdmin">
  <FormField
    type="select"
    name="role"
    label="Role"
    :options="[
      { value: 'ceo',        label: 'CEO'        },
      { value: 'supervisor', label: 'Supervisor' },
      { value: 'cook',       label: 'Cook'       },
      { value: 'cashier',    label: 'Cashier'    },
      { value: 'waiter',    label: 'Waiter'    },
    ]"
  />
</template>






        <FormField
          type="password"
          name="password"
          label="Password"
          placeholder="Create a password"
        />

        <FormField
          type="password"
          name="password_confirmation"
          label="Confirm Password"
          placeholder="Repeat your password"
        />

        <!-- Server error -->
        <p v-if="auth.error && typeof auth.error === 'string'"
           class="text-xs text-secondary-300 text-center -mt-2">
          {{ auth.error }}
        </p>
          <!-- Success message -->
          <p
            v-if="successMessage"
            class="text-xs text-success-500 text-center font-semibold animate-pulse -mt-1"
          >
            {{ successMessage }}
          </p>
        <!-- Submit -->
        <button
          @click="onSubmit"
          :disabled="auth.loading"
          class="w-full py-2.5 rounded-lg font-bold text-sm tracking-wide
                 bg-primary-500 text-neutral-900
                 hover:bg-primary-300 active:scale-95
                 disabled:opacity-40 disabled:cursor-not-allowed
                 transition-all duration-200 mt-1"
        >
          <span v-if="auth.loading">Creating account...</span>
          <span v-else>Create Account</span>
        </button>

      </div>

      <!-- Login link -->
      <p class="text-center text-[0.65rem] text-neutral-500 tracking-wide pb-4">
        Already have an account?
        <button
          @click="router.push('/login')"
          class="text-primary-300 font-bold hover:text-primary-500 transition-colors duration-200"
        >
          Sign In
        </button>
      </p>

    </div>
  </div>
</template>