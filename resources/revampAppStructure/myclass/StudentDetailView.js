
<template>
  <div>
    <CustomizedCarousel2
      :slides="slides"
      :enableSwipe="false"
      carouselId="studentDetailView" />
  </div>
</template>

<script setup>

import StudentProfile  from "./StudentSlide.vue";
import Result from "./.vue";
import StudentAnalytics from "./SubjectSlide.vue";

// Accept only the student object
const props = defineProps({
  student: { type: Object, required: true },
})

const slides = computed(() => [
  { component: StudentProfile, props: { student: props.student }, name: "Profile" },
   { component: Result, props: { student: props.student } , name: "Result"},
  { component: StudentAnalytics, props: { student: props.student}, name: "Analytics"},

])


</script>

  


