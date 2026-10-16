<template>
  <SmartAccordion
    title="student"
    id="class-students"
    :items="students"
    keyField="id"
    :enableSelection="true"
  >
    <!-- Slot: details -->
    <template #details="{ item }">
       
           <StudentDetailView :student="item" />
       
    </template>
  </SmartAccordion>
</template>

<script setup>
import AccordionWrapper from "@/components/AccordionWrapper.vue";
import StudentDetailView from "@/components/StudentDetailView.vue";
import SoftUnmount from "@/component/SoftUnmount.vue";
import { ref, onMounted } from "vue";

const students = ref([]);
onMounted(async () => {
  // Example data fetch
  students.value = [
    {
      id: 1,
      surname: "Adebayo",
      firstName: "Funke",
      passport: "/images/funke.jpg",
      age: 15,
      class: "SS2A",
      address: "13, Allen Avenue",
    },
    {
      id: 2,
      surname: "Okafor",
      firstName: "Emeka",
      passport: "/images/emeka.jpg",
      age: 14,
      class: "SS2A",
      address: "9, Ogba Road",
    },
  ];
});
</script>

