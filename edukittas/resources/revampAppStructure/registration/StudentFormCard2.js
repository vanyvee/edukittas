<template>
  <div class="card shadow-sm mb-3">
    <div class="card-header bg-primary text-white fw-bold">
      Basic Information
    </div>

    <div class="card-body">
      <!-- Field 1 -->
      <FormField
        type="text"
        label="Full Name"
        name="full_name"
        placeholder="Enter full name"
      />

      <!-- Field 2 -->
      <FormField
        type="select"
        label="Gender"
        name="gender"
        :options="genderOptions"
      />

      <!-- Field 3 -->
      <FormField
        type="textarea"
        label="Address"
        name="address"
        placeholder="Enter address"
      />
    </div>
  </div>
</template>

<script setup>
import FormField from "@/components/FormField.vue";

const genderOptions = [
  { label: "Male", value: "male" },
  { label: "Female", value: "female" },
];
</script>

<style scoped>
.card {
  border-radius: 10px;
}
.card-header {
  font-size: 1rem;
}
</style>