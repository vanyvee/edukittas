<template>
  <div class="mb-3">

    <!-- Label -->
    <label :for="name" class="form-label fw-bold">
      {{ label }}
    </label>

    <!-- Input Types -->
    <div v-if="isInput">
      <Field
        :id="name"
        :name="name"
        :type="type"
        class="form-control"
        :placeholder="placeholder"
      />
    </div>

    <!-- Textarea -->
    <div v-else-if="type === 'textarea'">
      <Field
        :id="name"
        :name="name"
        as="textarea"
        class="form-control"
        :placeholder="placeholder"
        rows="3"
      />
    </div>

    <!-- Select -->
    <div v-else-if="type === 'select'">
      <Field
        :id="name"
        :name="name"
        as="select"
        class="form-select"
      >
        <option value="">-- Select {{ label }} --</option>
        <option
          v-for="opt in options"
          :key="opt.value"
          :value="opt.value"
        >
          {{ opt.label }}
        </option>
      </Field>
    </div>

    <!-- Radio Group -->
    <div v-else-if="type === 'radio'">
      <div class="d-flex gap-3">
        <label
          v-for="opt in options"
          :key="opt.value"
          class="form-check-label"
        >
          <Field
            :name="name"
            type="radio"
            class="form-check-input me-1"
            :value="opt.value"
          />
          {{ opt.label }}
        </label>
      </div>
    </div>

    <!-- Error Message -->
    <ErrorMessage :name="name" class="text-danger small" />
  </div>
</template>

<script setup>
import { Field, ErrorMessage } from "vee-validate";
import { computed } from "vue";

const props = defineProps({
  type: { type: String, required: true }, // input, select, textarea, radio
  label: { type: String, required: true },
  name: { type: String, required: true },
  placeholder: { type: String, default: "" },
  options: { type: Array, default: () => [] }, // For select & radio
});

// Types that use <input />
const isInput = computed(() => {
  return [
    "text",
    "email",
    "password",
    "number",
    "tel",
    "date"
  ].includes(props.type);
});
</script>

<style scoped>
/* Keep clean Bootstrap style */
</style>

