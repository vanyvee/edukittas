<template>
  <CustomizedCarouselLite
      :slides="cbtComponents"
      carouselId="testComponents"
    />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import CustomizedCarouselLite from "@/components/TakeTest.vue";

const cbtComponents = [
{ component: TakeAssignment, props: { }, name: "Take CA Test" },
{ component: TakeTest, props: { }, name: "Take CA Test" },
{ component: TakeExam, props: { }, name: "Take Exam"},
  
]


