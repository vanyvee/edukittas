<template>
    <CustomizedCarouselLite
      :slides="gradeComponents"
      carouselId="gradeCarousel"
    />
</template>

<script setup>
import BroadSheet from '@/components/BroadSheet.vue'
import GradingPanel from '@/components/GradingPanel.vue'
import GradeAnalytics from '@/components/GradeAnalytics.vue'

const gradeComponents = [
  { component: BroadSheet},
  { component: GradingPanel, props: { } },
  { component: GradeAnalytics, props: { }, name: "Quize Lounge"},
  
]
</script>




