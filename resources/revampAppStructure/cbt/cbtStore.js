import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useRouter } from 'vue-router'

export const useQuestionStore = defineStore('question', () => {
  /* ---------- STATE ---------- */
  const questions = ref([])
  const examSubmitted = ref(false)
  const router = useRouter()
const showQuizSettingPanel = ref('false')

  /* ---------- ACTIONS ---------- */
  async function fetchQuestions(api = '/api/questions') {
    try {
      const res = await fetch(api)
      questions.value = await res.json()
    } catch (err) {
      console.error('Failed to fetch questions:', err)
    }
  }

  function getSelectedAnswers() {
    return questions.value.flatMap(q => {
      if (q.type === 'regular') {
        return { id: q.id, selected: q.selected || null }
      }
      if (q.type === 'gap_group') {
        return q.questions.map(sub => ({
          id: sub.id,
          selected: sub.selected || null,
        }))
      }
      return []
    })
  }

  function clearQuestions() {
    questions.value = []
  }

  async function submitExamAnswers(api = '/api/submit-answers') {
    try {
      await fetch(api, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ answers: getSelectedAnswers() }),
      })
      examSubmitted.value = true
      clearQuestions()
      localStorage.removeItem('questions')
      setTimeout(() => router.push('/'), 1500)
    } catch (error) {
      console.error('Submission failed:', error)
    }
  }
  
  // used mainly in smartaccordion to select items into external variable
  
  function toggleSelection(type, key, single = false) {
  const target = getSelection(type);

  if (single) {
    if (target.length === 1 && target[0] === key) target.length = 0;
    else target.splice(0, target.length, key);
    return;
  }

  const index = target.indexOf(key);
  if (index >= 0) target.splice(index, 1);
  else target.push(key);
}

  /* ---------- RETURN ---------- */
  return {
    questions,
    examSubmitted,
    fetchQuestions,
    getSelectedAnswers,
    clearQuestions,
    submitExamAnswers,
  }
})