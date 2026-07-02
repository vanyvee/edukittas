<template>
  <SmartAccordionLite
    label="Number"
    :items ="numbers"
    :enableSelection = "false"
  />
</template>

<script setup>
import Accordion from "@/components/Accordion.vue";

const numbers = Array.from({ length: 100 }, (_, i) => i + 1);
</script>



