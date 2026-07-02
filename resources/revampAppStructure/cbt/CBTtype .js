<template>
  <SmartAccordionLite
    label="CBTtype"
    :items ="cbtType"
  />
</template>

<script setup>
import SmartAccordionLite from "@/components/SmartAccordionLite.vue";

const cbtType= [
  {  name: "Exams"},
  {  name: "CA Test" },
  {  name: "Assignment" },
  {  name: "Quiz" },
];
</script>



