<template>
  <div class="card mt-4 p-3">
    <!-- ✏️ Question Text -->
    <textarea
      v-model="newQuestion.qtext"
      placeholder="Enter question text"
      class="form-control mb-2"
      rows="3"
    />

    <!-- ✅ Subjective checkbox -->
    <div class="form-check mb-3">
      <input
        type="checkbox"
        class="form-check-input"
        v-model="newQuestion.subjective"
        id="subjFlag"
      />
      <label class="form-check-label" for="subjFlag">Mark as Subjective</label>
    </div>

    <!-- 🎯 Options Section -->
    <div
      v-if="!newQuestion.subjective"
      class="row g-2 mb-3"
    >
      <div
        class="col-12 col-md-6"
        v-for="(opt, index) in newQuestion.options"
        :key="index"
      >
        <div class="input-group">
          <input
            v-model="newQuestion.options[index]"
            class="form-control"
            :placeholder="`Option ${String.fromCharCode(97 + index).toUpperCase()}`"
          />
          <button
            v-if="index > 1"
            class="btn btn-outline-danger"
            @click="removeOption(index)"
            type="button"
          >✖</button>
        </div>
      </div>
      <!-- ➕ Add More Option -->
      <div class="col-12">
        <button
          class="btn btn-outline-primary btn-sm"
          @click="addOption"
          :disabled="newQuestion.options.length >= 5"
        >
          + Add Option
        </button>
      </div>
    </div>

    <!-- ✅ Correct Answer Field -->
    <input
      v-model="newQuestion.correct"
      class="form-control mb-2"
      placeholder="Enter correct answer"
    />

    <!-- ➕ Add Question -->
    <button class="btn btn-success btn-sm" @click="addToQuestionArray">
      Add Question
    </button>
  </div>
</template>

<script setup>
import { reactive } from 'vue'
import { useQuestionStore } from '@/stores/questionStore'

const questionStore = useQuestionStore()

// 🆕 Local object for adding a question
const newQuestion = reactive({
  qid: Date.now(),
  qtext: '',
  options: ['', ''], // Start with 2 options
  correct: '',
  subjective: false,
  selected: ''
})

function addOption() {
  newQuestion.options.push('')
}

function removeOption(index) {
  newQuestion.options.splice(index, 1)
}

function addToQuestionArray() {
  const q = {
    ...newQuestion,
    a: newQuestion.options[0] || '',
    b: newQuestion.options[1] || '',
    c: newQuestion.options[2] || '',
    d: newQuestion.options[3] || '',
    e: newQuestion.options[4] || '',
  }

  if (q.subjective) {
    q.a = q.b = q.c = q.d = q.e = ''
  }

  questionStore.questions.push(q)

  // Reset the form
  newQuestion.qid = Date.now()
  newQuestion.qtext = ''
  newQuestion.options = ['', '']
  newQuestion.correct = ''
  newQuestion.subjective = false
  newQuestion.selected = ''
}
</script>



