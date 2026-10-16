<template>
  <div>
    <client-only>
    <CustomizedCarouselLite1
      :slides="carouselSlides"
      :showControls="true"
      carouselId="mySchoolCarousel"
   
    >
      <template #header>
        <div class="d-flex sticky-top justify-content-between align-items-center p-2 bg-primary text-white">
          <!-- <Ribbon />
          <KebabMenu /> -->
          hi
        </div>
      </template>
    </CustomizedCarouselLite1>
    </client-only>
  </div>
</template>

<script setup>

const carouselSlides = [
  { component: AboutSlide,  name: "AboutSlide" },
  { component: ContactSlide,  name: "ContactSlide" },
  
]


</script>