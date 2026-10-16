<template>
  <div class="container py-4">
    <!-- Active Exam -->
    <div v-if="!examSubmitted">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <span class="badge bg-danger fs-6">Time Left: {{ countdown }}s</span>
        <span class="badge bg-success fs-6">{{ answeredCount }}/{{ totalQuestions }} Answered</span>
      </div>

      <h2 class="mb-4">{{ type.toUpperCase() }} - Subject {{ subjectId }}</h2>

      <!-- Questions -->
      <div v-if="questions.length">
        <div
          v-for="(question, index) in questions"
          :key="question.id || question.group_id"
          class="card mb-3"
        >
          <!-- Regular Question -->
          <div v-if="question.type === 'regular'" class="card-body">
            <h5 class="card-title">{{ indexCounter(index) }}. {{ question.text }}</h5>
            <ul class="list-group list-group-flush">
              <li
                v-for="(option, oIndex) in question.shuffledOptions"
                :key="oIndex"
                class="list-group-item"
              >
                <div class="form-check">
                  <label class="form-check-label">
                    <input
                      class="form-check-input"
                      type="radio"
                      :name="'q_' + question.id"
                      :value="option"
                      v-model="question.selected"
                    />
                    <strong>{{ letters[oIndex] }}.</strong> {{ option }}
                  </label>
                </div>
              </li>
            </ul>
          </div>

          <!-- Gap Fill Group -->
          <div v-else-if="question.type === 'gap_group'" class="card-body">
            <h5 class="card-title">Q{{ gapCounterStart(index) }}.</h5>
            <p v-html="generateGapText(question.passage, question.questions, gapCounterStart(index))"></p>

            <div
              v-for="(subQ, gapIndex) in question.questions"
              :key="subQ.id"
            >
              <p>({{ gapCounterStart(index) + gapIndex }})</p>
              <ul class="list-group list-group-flush">
                <li
                  v-for="(option, oIndex) in subQ.shuffledOptions"
                  :key="oIndex"
                  class="list-group-item"
                >
                  <div class="form-check">
                    <label class="form-check-label">
                      <input
                        class="form-check-input"
                        type="radio"
                        :name="'q_' + subQ.id"
                        :value="option"
                        v-model="subQ.selected"
                      />
                      <strong>{{ letters[oIndex] }}.</strong> {{ option }}
                    </label>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <div class="text-center">
          <button
            @click="submitAnswers"
            class="btn btn-primary px-4"
            :disabled="submissionInProgress"
          >
            {{ submissionInProgress ? 'Submitting...' : 'Submit Answers' }}
          </button>
        </div>
      </div>

      <div v-else>
        <p>No questions available.</p>
      </div>
    </div>

    <!-- Submitted Exam -->
    <div v-else class="text-center">
      <div class="alert alert-success">
        <h4 class="alert-heading">Congratulations!</h4>
        <p>You have completed the exam successfully.</p>
      </div>
      <button class="btn btn-outline-secondary" @click="emit('go-home')">
        Return to Dashboard
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import Echo from 'laravel-echo'
import { useCbtStore } from '@/stores/question'

/* ---------- Props & Emits ---------- */
const props = defineProps({
  subjectId: { type: [String, Number], required: true },
  questions: { type: array, required: true },
  type: { type: String, default: 'exam' },
  fetchApi: { type: String, default: '/api/questions' },
  submitApi: { type: String, default: '/api/submit-answers' },
  timerChannel: { type: String, default: 'exam.timer' }
})

/* ---------- State ---------- */
const cbtStore = useCbtStore()
const countdown = ref(0)
const examSubmitted = ref(false)
const submissionInProgress = ref(false)
const letters = ['A', 'B', 'C', 'D', 'E']
const questions = props.questions
/* ---------- Computed ---------- 
//const totalQuestions = computed(() =>
 // cbtStore.questions.reduce((acc, q) => acc + (q.type === 'gap_group' ? q.questions.length : 1), 0)
) */

const totalQuestions = questions.length
const answeredCount = computed(() =>
  questions.reduce((acc, q) => {
    if (q.type === 'regular') return acc + (q.selected ? 1 : 0)
    if (q.type === 'gap_group') return acc + q.questions.filter(gq => gq.selected).length
    return acc
  }, 0)
)

/* ---------- Utility Functions ---------- */
function indexCounter(index) {
  // Handles sequential numbering even with grouped questions
  let count = 1
  for (let i = 0; i < index; i++) {
    const q = questions[i]
    count += q.type === 'gap_group' ? q.questions.length : 1
  }
  return count
}

function gapCounterStart(index) {
  return indexCounter(index)
}

function generateGapText(passage, gaps, startIndex) {
  let counter = startIndex
  return passage.replace(/--/g, () => `(${counter++})`)
}

/* ---------- Actions ---------- */
async function submitAnswers() {
  if (submissionInProgress.value || examSubmitted.value) return
  submissionInProgress.value = true
  try {
    await cbtStore.submitExamAnswers(props.submitApi)
    examSubmitted.value = true
    stopListening()
  } finally {
    submissionInProgress.value = false
  }
}

/* ---------- Timer Listening ---------- */
function stopListening() {
  Echo.leave(props.timerChannel)
}

function initTimer() {
  Echo.channel(props.timerChannel).listen('TimerTicked', (e) => {
    countdown.value = e.seconds_left
    if (e.seconds_left <= 0 && !examSubmitted.value) {
      submitAnswers()
    }
  })
}

/* ---------- Lifecycle ---------- */
onMounted(async () => {
  await cbtStore.fetchQuestions(props.fetchApi)
  initTimer()
})

onUnmounted(() => {
  stopListening()
})
</script>





