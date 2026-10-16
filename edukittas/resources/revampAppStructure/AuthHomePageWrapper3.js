
<template>
  <div>
    <CustomizedCarousel2
      :slides="slides"
      :pcDisplay="1"
      :mobileDisplay="1"
      :transitionType="'slide'"
      :enableSwipe="true"
      :showIndicators="true"
      :showControls="false"
      :autoplay="false"
      :persistActive="true"
      :preloadAdjacent="true"
      carouselId="mySchoolCarousel"
      :transitionDuration="500"
      :initialSlide = "1"
    >
      <template #header>
        <div class="d-flex sticky-top justify-content-between align-items-center p-2 bg-primary text-white">
          <Ribbon />
          <KebabMenu />
        </div>
      </template>
    </CustomizedCarousel2>
  </div>
</template>

<script setup>
import { useRouter,onBeforeRouteLeave, onBeforeRouteEnter } from 'vue-router';
import { ref, computed, onMounted } from "vue";
import { useArrayMap, useArrayUnique,useArrayGroupBy} from '@vueuse/core'
//import {Carousel} from 'bootstrap';
// Import slide components
import myStudents   from "./StudentSlide.vue";
import myTeachers from "./TeacherSlide.vue";
import mySubjects from "./SubjectSlide.vue";
import  from "./registrationForm.vue";

// Slide data
const students = [
  { id: 1, name: "Alice" },
  { id: 2, name: "Bob" },
];
const teachers = [
  { id: 1, name: "Mr. Smith" },
  { id: 2, name: "Ms. Johnson" },
];
const subjects = [
  { id: 1, name: "Math" },
  { id: 2, name: "Science" },
];
const quizzes = [
  { id: 1, title: "Quiz 1" },
  { id: 2, title: "Quiz 2" },
];

// Array of slide components with props
const slides = [
  { component: mySchool,   props: {} , name: "Students"},
  { component: myClass, props: { students } , name: "Class"},
  { component: myTeachers, props: { teachers }, name: "Staff"},
  { component: mySubjects, props: { subjects }, name: "Subjects"},
  { component: registrationForm, props: { registration}, name: "Admission"},
];

const selectStudentsByClass = (class) => {
      selectedClass.value = class;
      selectedStudents.value = groupedStudentsData.value[class] || [];
    };

    const selectTeachersByClass = (class) => {
      selectedClass.value = class;
      selectedTeachers.value = groupedTeachersData.value[class] || [];
    };

const filteredQuestions = computed(() =>
  questions.value.filter(
    q => q.type === selectedType.value && q.subjectid === selectedSubject.value
  )
)


// try use vueuse to achieve it
//const groupedByClass = useArrayGroupBy(students, "className"); // Group by className

    const groupedStudentData = computed(() => {
      return students.value.reduce((groups, student) => {
        if (!groups[student.className]) {
          groups[student.className] = [];
        }
        groups[student.className].push(student);
        return groups;
      }, {});
    });

onMounted(() => {
  const carousel = document.getElementById('carouselWrapper');
  if (carousel) {
      //  new Carousel(carousel,{interval: false})  to prevent auto slidding
    carousel.addEventListener('slide.bs.carousel', () => {
      myStore.selectedItem = null;
      myStore.selectedItems = null;
      myStore.editMode.value = false;
      myStore.selectionMode.value = false;
    });
  }
      
      
});

// Save slide index before leaving the route
onBeforeRouteLeave(() => {
  sessionStorage.setItem('activeSlide', activeSlide.value);
});
// Restore slide index when entering the route
onBeforeRouteEnter((to, from, next) => {
  next((vm) => {
    const savedIndex = sessionStorage.getItem('activeSlide');
    if (savedIndex !== null) {
      vm.activeSlide = parseInt(savedIndex, 10);
    }
  });
});
</script>

  
<style scoped>
/* Sticky Header */
.header {
  background-color: #075e54;
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 15px;
}

/* Tabs Below Header */
.tabs {
  display: flex;
  justify-content: space-around;
  background-color: #075e54;
  padding: 10px 0;
}

.tabs button {
  flex: 1;
  color: white;
  background: none;
  border: none;
  font-size: 16px;
  padding: 10px 0;
  cursor: pointer;
  transition: background-color 0.3s;
}

.tabs button.active {
  background-color: #25d366;
  border-radius: 5px;
}


</style>




