<template>
  <div class="card my-2">
    <div class="card-header d-flex justify-content-between">
      <div>{{ question.text }}</div>
      <div class="form-check">
        <input type="checkbox" class="form-check-input" v-model="question.subjective" />
      </div>
    </div>

    <ul v-if="!question.subjective" class="list-group list-group-flush">
      <li v-for="(opt, i) in question.options" :key="i" class="list-group-item">
        {{ String.fromCharCode(97 + i).toUpperCase() }}. {{ opt }}
      </li>
    </ul>

    <div class="card-footer text-end">
      <button class="btn btn-sm btn-danger" @click="deleteMe">Delete</button>
    </div>
  </div>
</template>

<script setup>
defineProps(['question'])

import { useQuestionStore } from '@/stores/questionStore'
const questionStore = useQuestionStore()

function deleteMe() {
  const index = questionStore.questions.indexOf(question)
  if (index !== -1) questionStore.deleteQuestion(index)
}
</script>

