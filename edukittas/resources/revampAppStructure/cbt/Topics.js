<template>
  <SmartAccordionLite
    label="Topics"
    :items ="topics"
  />
</template>

<script setup>
import Accordion from "@/components/Accordion.vue";

const topics = [
  { id: 1, title: "Okafor"},
  { id: 2, title: "Adebayo" },
];
</script>

