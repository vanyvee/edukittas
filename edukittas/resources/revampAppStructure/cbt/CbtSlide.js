<template>
    <CustomizedCarouselLite
      :slides="cbtComponents"
      carouselId="cbtCarousel"
    />
</template>

<script setup>
import SetCBT from '@/components/SetCBT.vue'
import TakeCbt from '@/components/TakeCbt.vue'
import QuizLounge from '@/components/QuizLounge.vue'

const cbtComponents = [
  { component: SetCBT},
  { component: TakeCbt, props: { } },
  { component: QuizLounge, props: { }, name: "Quize Lounge"},
  
]
</script>


