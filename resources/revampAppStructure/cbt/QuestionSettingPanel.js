<template>
  <div>
    <!-- 🔘 Master Subjective Checkbox -->
    <div class="form-check my-3">
      <input type="checkbox" class="form-check-input" v-model="markAllSubjective" @change="toggleAll" />
      <label class="form-check-label">Mark All Questions as Subjective</label>
    </div>

    <!-- 📚 Existing Questions -->
   <template if="cbtType !== 'assignment'">
    <SystemGeneratedQuestions
      v-for="(q, i) in questionStore.questions"
      :key="i"
      :question="q"
    />
    </template>

    <!-- ➕ New Question Form -->
    <AddUserDefinedQueations if="cbtType !== 'assignment'"/>
   <AssignmentSettingPanel if="cbtType === 'assignment'"/>
<!-- After all cards and newQuestionForm -->
<div class="text-end mt-4">
  <button class="btn btn-primary" @click="submitAllToBackend">
    Submit All Questions
  </button>
</div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useQuestionStore } from '@/stores/questionStore'
import ExistingQuestionCard from './ExistingQuestionCard.vue'
import NewQuestionForm from './NewQuestionForm.vue'
const {cbtType, quizType, subject, classId}= defineProps({
  cbtType: { type: String, required: true },
  quizType: { type: String, required: true, Default: ''},
   subjects: { type: String, Default: ''},
  classId: { type: String,  Default: ''},
  
});
const questionStore = useQuestionStore()
const markAllSubjective = ref(false)
const partTake = false

const payload = Object.fromEntries(
  Object.entries({ cbtType ,quizType, subject, classId, questions, partTake }).filter(([_, v]) => v !== '')
);

console.log(payload);
// 👉 { form1: 'Vany', form4: 23 }



function toggleAll() {
  questionStore.questions.forEach(q => q.subjective = markAllSubjective.value)
}
</script>

