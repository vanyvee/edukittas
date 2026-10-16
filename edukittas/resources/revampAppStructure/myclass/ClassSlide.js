<template>
    <CustomizedCarouselLite
      :slides="classComponents"
      carouselId="classCarousel"
    />
</template>

<script setup>
import DefaultClassView from '@/components/DefaultClassView.vue'
import ClassAnalytics from '@/components/ClassAnalytics.vue'
import FullClassView from '@/components/FullClassView.vue'

const classComponents = [
  { component: DefaultClassView },
  { component: FullClassView, props: { } },
  { component: ClassAnalytics, props: { }},
  
]

</script>
