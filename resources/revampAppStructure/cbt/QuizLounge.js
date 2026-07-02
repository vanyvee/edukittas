<template>
<div v-if="!cbtStore.showQuizSettingPanel">
    <SmartAccordionLite  :slides="joinQuizTab" carouselId="joinQuizTab">
       <template #details="{ item }">
       <MiniForm  :placeholders="Enter your Quiz Pin"   link="/quizPage"/>
       </template>
    </SmartAccordionLite>



    <SmartAccordionLite  :slides="manageQuizTab" carouselId="manageQuizTab">
       <template #details="{ item }">
       <MiniForm  :placeholders="Existing Quiz"   :readonly=true
        buttonLabel="Delete"/>
       </template>
    </SmartAccordionLite>

     

    <SmartAccordionLite :slides="createQuizTab"
     carouselId="createQuizTab"
     @click="cbtStore.showQuizSettingPanel = !cbtStore.showQuestionSettingPanel"/>
    </div>


     <SetCBT v-if="cbtStore.showQuizSettingPanel"/>
</div>
</template>

<script setup>
import MiniForm from '@/components/MiniForm.vue'
import SmartAccordionLite from '@/components/SmartAccordionLite.vue'
import SetCBT from '@/components/SetCBT.vue'


const joinQuizTab= [
  { name: "Join Quiz"},
]
const existingQuiz = ref([])
const manageQuizTab= [
  { name: "Manage Quiz" , existing: existingQuiz.value},
]
const CreateQuizTab= [
  { name: "Create Quiz"},
]
</script>




