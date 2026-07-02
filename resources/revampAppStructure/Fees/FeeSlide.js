<template>
    <CustomizedCarouselLite
      :slides="feeComponents"
      carouselId="feeCarousel"
    />
</template>

<script setup>
import OnDeskPayment from '@/components/OnDeskPayment.vue'
import FeeStoryPanel from '@/components/FeeStoryPanel.vue'
import FeeAnalytics from '@/components/FeeAnalytics.vue'

const feeComponents = [
  { component: OnDeskPanel},
  { component: FeeStoryPanel, props: { } },
  { component: FeeAnalytics, props: { }, name: "Qui"},
  
]
</script>






