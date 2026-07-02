<template>
  <div class="wrapper">
    <!-- MAIN CONTENT AREA -->
    <div class="content-area">
      
      <div v-show="active === 'A'" class="block block-a">
            <CBTtype/>
            <AutoOrManualSetting if="cbtType !== 'assignment'"/>
            <Topics v-if="cbtType !== 'assignment'"/>
            <Numbers v-if="cbtType !== 'assignment'"/>
            <QuizType v-if="cbtType==='quiz'"/>
            <TotalTornamentQuestionNum v-if="quizType==='tournament'"/>
            <TakePart v-if="setting === 'auto'"/>
      </div>

      
      <div v-show="active === 'B'" class="block block-b">

             <QuestionSettingPanel />
      </div>

    </div>

    <!-- FIXED BUTTON PANEL -->
    <div class="button-panel">
      <button
        v-if="active === 'A'"
        class="btn btn-primary"
        @click="toggleBlock('B')"
        @click="submit()"
      >
        Set
      </button>

      <div v-else class="dual-btns">
        <button class="btn btn-secondary" @click="toggleBlock('A')">
          Back
        </button>
        <button class="btn btn-success" @click="cbtStore.submit">
          Submit
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { useTestSetupStore } from '@/stores/testSetup'
import { useAuthStore } from '@/stores/auth'
import Numbers from '@/components/Numbera.vue'
import CBTtype from '@/components/CBTtype.vue'
import Topics from '@/components/Topics.vue'
import QuestionSettingPanel from '@/components/QuestionSettingPanel.vue'

const active = ref("A"); 

function toggleBlock(target) {
  active.value = target;
}

const store = useTestSetupStore()
const auth = useAuthStore()

// Form state
const selectedClassId = ref('')
const selectedTestType = ref('')
const selectedSubjectId = ref('')
const selectedTopics = ref([])
const selectedQuestionType = ref('')
const numberOfQuestions = ref(1)
const setting = ref('manual')
// Options
const classes = computed(() => auth.teacherClasses)
const testTypes = ['Assignment', 'CATest', 'Exam']
const questionTypes = ['Objective', 'Theory', 'Subjective']

// Fetch subject based on selected class
const subjects = computed(() => {
  return store.getSubjectsForClass(selectedClassId.value)
})

// Topics fetch logic
const topics = ref([])
let topicAbortController = null

const fetchTopics = async () => {
  if (!selectedSubjectId.value || !selectedClassId.value) return

  // Abort previous
  if (topicAbortController) {
    topicAbortController.abort()
  }

  topicAbortController = new AbortController()
  const signal = topicAbortController.signal

  try {
    const res = await fetch(`/api/topics?subject_id=${selectedSubjectId.value}&class_id=${selectedClassId.value}`, {
      signal
    })
    const data = await res.json()
    topics.value = data.topics
  } catch (error) {
    if (error.name !== 'AbortError') {
      console.error('Failed to fetch topics', error)
    }
  }
}

// Trigger topic fetch on subject select
const onSubjectChange = () => {
  selectedTopics.value = []
  fetchTopics()
}

// Submit everything
const submit = () => {
  if(testType === 'assingment'){
    toggle('B')
    return 
  }
  const payload = {
    class_id: selectedClassId.value,
    test_type: selectedTestType.value,
    subject_id: selectedSubjectId.value,
    topics: selectedTopics.value,
    question_type: selectedQuestionType.value,
    number_of_questions: numberOfQuestions.value
  }

  console.log('Payload to submit:', payload)

  // Send to backend
  fetch('/api/test/setup', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
}
</script>

<style scoped>
/* MAIN WRAPPER */
.wrapper {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: #f8f9fa;
}

/* MAIN CONTENT */
.content-area {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
}

/* ========== BLOCK STYLES ========== */

/* DivA → flexible inline container */
.block-a {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  gap: 1rem;
  background: #fff;
  padding: 1rem;
  border-radius: 8px;
  box-shadow: 0 0 5px rgba(0, 0, 0, 0.05);
}

/* Children inside divA appear inline */
.block-a > * {
  flex: 0 0 auto;
}

/* DivB → normal full content area */
.block-b {
  display: block;
  background: #fff;
  padding: 1rem;
  border-radius: 8px;
  box-shadow: 0 0 6px rgba(0, 0, 0, 0.08);
}

/* ========== BUTTON PANEL ========== */
.button-panel {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem;
  background: #fff;
  border-top: 1px solid #ddd;
  position: sticky;
  bottom: 0;
  z-index: 10;
}

.dual-btns {
  display: flex;
  gap: 1rem;
}

/* ========== BUTTON STYLES ========== */
.btn {
  padding: 0.5rem 1.2rem;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 500;
  transition: background 0.2s ease;
}

.btn-primary {
  background: #007bff;
  color: #fff;
}
.btn-secondary {
  background: #6c757d;
  color: #fff;
}
.btn-success {
  background: #28a745;
  color: #fff;
}

.btn:hover {
  opacity: 0.9;
}
</style>

