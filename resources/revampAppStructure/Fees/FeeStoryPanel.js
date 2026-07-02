<template>
 <SmartAccordionLite
      label="Fees Report"
      :items="feeReport"
      key-field="id"
    >
      <template #details="{ item }">
        <FeePaymentReport/>
      </template>
    </SmartAccordionLite>
</template>
<script setup>
import SmartAccordionLite from "@/components/SmartAccordionLite.vue";
const feeReports = 
</script>



