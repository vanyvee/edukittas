<template>
  <div class="flex flex-col gap-1.5">

    <!-- Label -->
    <label :for="name"
      class="text-xs font-semibold tracking-widest uppercase text-neutral-300">
      {{ label }}
    </label>

    <!-- Text / Email / Password / Number / Tel / Date -->
    <div v-if="isInput" class="relative">
      <Field
        :id="name"
        :name="name"
        :type="showPlain ? 'text' : type"
        :placeholder="placeholder"
        class="w-full bg-neutral-900 border border-neutral-500 rounded-lg px-4 py-2.5
               text-sm text-neutral-300 placeholder-neutral-500
               outline-none transition-all duration-200
               focus:border-primary-500 focus:ring-1 focus:ring-primary-700"
        :class="{ 'pr-16': type === 'password' }"
      />
      <!-- Show/Hide toggle for password -->
      <button
        v-if="type === 'password'"
        type="button"
        @click="showPlain = !showPlain"
        class="absolute right-3 top-1/2 -translate-y-1/2
               text-neutral-500 hover:text-primary-300
               transition-colors duration-200 text-xs font-semibold"
      >
        {{ showPlain ? 'HIDE' : 'SHOW' }}
      </button>
    </div>

    <!-- Textarea -->
    <Field
      v-else-if="type === 'textarea'"
      :id="name"
      :name="name"
      as="textarea"
      :placeholder="placeholder"
      rows="3"
      class="w-full bg-neutral-900 border border-neutral-500 rounded-lg px-4 py-2.5
             text-sm text-neutral-300 placeholder-neutral-500
             outline-none transition-all duration-200 resize-none
             focus:border-primary-500 focus:ring-1 focus:ring-primary-700"
    />

    <!-- Select -->
    <Field
      v-else-if="type === 'select'"
      :id="name"
      :name="name"
      as="select"
      class="w-full bg-neutral-900 border border-neutral-500 rounded-lg px-4 py-2.5
             text-sm text-neutral-300
             outline-none transition-all duration-200 cursor-pointer
             focus:border-primary-500 focus:ring-1 focus:ring-primary-700"
    >
      <option value="" disabled class="text-neutral-500">
        -- Select {{ label }} --
      </option>
      <option
        v-for="opt in options"
        :key="opt.value"
        :value="opt.value"
        class="bg-neutral-900 text-neutral-300"
      >
        {{ opt.label }}
      </option>
    </Field>

    <!-- Radio Group -->
    <div v-else-if="type === 'radio'" class="flex flex-wrap gap-4">
      <label
        v-for="opt in options"
        :key="opt.value"
        class="flex items-center gap-2 cursor-pointer text-sm text-neutral-300
               hover:text-primary-300 transition-colors duration-200"
      >
        <Field
          :name="name"
          type="radio"
          :value="opt.value"
          class="accent-primary-500 w-4 h-4 cursor-pointer"
        />
        {{ opt.label }}
      </label>
    </div>

    <!-- Error -->
    <ErrorMessage
      :name="name"
      class="text-xs text-secondary-300 mt-0.5"
    />

  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Field, ErrorMessage } from 'vee-validate'

const props = defineProps({
  type:        { type: String, required: true },
  label:       { type: String, required: true },
  name:        { type: String, required: true },
  placeholder: { type: String, default: '' },
  options:     { type: Array,  default: () => [] },
})

const showPlain = ref(false)

const isInput = computed(() =>
  ['text', 'email', 'password', 'number', 'tel', 'date'].includes(props.type)
)
</script>

<style scoped>
input:-webkit-autofill,
select:-webkit-autofill,
textarea:-webkit-autofill {
  -webkit-box-shadow: 0 0 0 100px var(--neutral-900) inset;
  -webkit-text-fill-color: var(--neutral-300);
  caret-color: var(--neutral-300);
}
</style>