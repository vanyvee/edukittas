<template>
  <div v-if="student" class=" bg-primary p-4 space-y-4">
    <!-- Header -->
    <div class="flex items-center gap-4 border-b pb-3">
      <img
        :src="student.passport"
        class="w-20 h-20 rounded-full object-cover border"
      />
      <div>
        <h2 class="text-xl font-semibold">
          {{ student.surname }} {{ student.first_name }}
        </h2>
        <p class="text-sm text-gray-500">
          Admission Date: {{ student.admission_date }}
        </p>
      </div>
    </div>

    <!-- Details -->
    <div class="grid grid-cols-2 gap-4 text-sm">
      <div>
        <span class="font-medium">Gender:</span>
        {{ student.gender }}
      </div>

      <div>
        <span class="font-medium">Age:</span>
        {{ student.age }}
      </div>

      <div>
        <span class="font-medium">Class:</span>
        {{ student.class }}
      </div>

      <div>
        <span class="font-medium">Guardian:</span>
        {{ student.guardian }}
      </div>

      <div class="col-span-2">
        <span class="font-medium">Phone:</span>
        {{ student.phone }}
      </div>
    </div>
  </div>

  <div v-else class="text-gray-400 text-center py-10">
    Select a student to view details
  </div>
</template>

<script setup>
defineProps({
  student: {
    type: Object,
    required: false
  }
});
</script>
   