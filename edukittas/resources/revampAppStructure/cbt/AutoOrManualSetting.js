<template>
  <SmartAccordionLite
    label="Auto/manual setting"
    :items ="autoManual"
    :enableSelection="fals"
  />
</template>

<script setup>
import SmartAccordionLite from "@/components/SmartAccordionLite.vue";

const autoManual= [
  {  name: "automatic"},
  {  name: "manual" },
];
</script>





