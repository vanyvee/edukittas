<!-- <script setup>
import { ref, computed } from 'vue';

const passage = ref("The capital of Nigeria is --. It is located in the center of the country. The president lives in --.");
const gapQuestions = ref([
  { id: 101, correct_answer: "Abuja" },
  { id: 102, correct_answer: "Aso Rock" },
]);

// Assume gap group starts at question number 6
const startingDisplayNumber = 6;

const renderedPassage = computed(() => {
  let currentNumber = startingDisplayNumber;
  let gapIndex = 0;
  return passage.value.replace(/--/g, () => {
    const displayNumber = currentNumber++;
    return `<span class="gap-label">(${displayNumber})</span> <input data-id="${gapQuestions.value[gapIndex++].id}" />`;
  });
});
</script>

<template>
  <div v-html="renderedPassage" class="gap-paragraph"></div>
</template> -->



<script setup>
import { ref, computed } from 'vue'

const questions = ref([]) // This would come from backend

// Dynamically assign numbers
const numberedQuestions = computed(() => {
  let counter = 1
  return questions.value.flatMap(q => {
    if (q.type === 'gap_fill' && q.is_group_leader) {
      const dashCount = (q.content.match(/--/g) || []).length
      const gaps = Array.from({ length: dashCount }, (_, i) => counter + i)
      counter += dashCount
      return [{ ...q, gaps }]
    } else {
      return [{ ...q, number: counter++ }]
    }
  })
})
</script>

<template>
  <div v-for="(q, index) in numberedQuestions" :key="index">
    <div v-if="q.type === 'normal'">
      <p>{{ q.number }}. {{ q.content }}</p>
      <ul>
        <li v-for="(opt, i) in q.options" :key="i">
          <label><input type="radio" :name="'q' + q.id" :value="opt"> {{ opt }}</label>
        </li>
      </ul>
    </div>

    <div v-else-if="q.type === 'gap_fill' && q.is_group_leader">
      <p>
        <!-- Replace double dash with number -->
        <span v-html="replaceWithNumbers(q.content, q.gaps)"></span>
      </p>
      <div v-for="(gapNum, gapIndex) in q.gaps" :key="gapIndex">
        <p>{{ gapNum }}.
          <select :name="'gap_' + q.id + '_' + gapIndex">
            <option v-for="opt in q.options" :value="opt">{{ opt }}</option>
          </select>
        </p>
      </div>
    </div>
  </div>
</template>

<script>
function replaceWithNumbers(text, gaps) {
  let index = 0
  return text.replace(/--/g, () => `<strong>(${gaps[index++]})</strong>`)
}
</script>















<template>
  <div v-for="(q, index) in allQuestions" :key="index">
    <div v-if="q.type === 'regular'">
      <p><strong>Question {{ index + 1 }}:</strong> {{ q.text }}</p>
      <div v-for="(opt, i) in JSON.parse(q.options)" :key="i">
        <label><input type="radio" :name="'q' + q.id" :value="opt"> {{ opt }}</label>
      </div>
    </div>

    <div v-else-if="q.type === 'gap_group'">
      <p><strong>Passage:</strong></p>
      <p v-html="formatGapText(q.passage, q.questions, index + 1)"></p>
    </div>
  </div>
</template>

Script for gap-fill formatting:
<script setup>
import { ref } from 'vue'

const allQuestions = ref([]) // Fetched from backend

function formatGapText(passage, subQs, startNumber) {
  let counter = 0
  return passage.replace(/--/g, () => {
    const q = subQs[counter++]
    if (!q) return '[missing]'
    const opts = JSON.parse(q.options)
    const input = opts.map(opt =>
      `<label><input type="radio" name="q${q.id}" value="${opt}"> ${opt}</label>`).join(' ')
    return `<strong>(${startNumber + counter - 1})</strong> ${input}`
  })
}
</script>





-- Fetch regular questions
SELECT 
  q.id,
  q.text,
  'regular' AS type,
  NULL AS group_id,
  NULL AS passage,
  NULL AS questions,
  q.options,
  q.correct_answers,
  q.question_order
FROM questions q
WHERE q.type = 'regular'

UNION ALL

-- Fetch gap group questions with children bundled in JSON
SELECT 
  NULL AS id,
  NULL AS text,
  'gap_group' AS type,
  g.id AS group_id,
  g.passage,
  JSON_ARRAYAGG(
    JSON_OBJECT(
      'id', q.id,
      'position', q.position,
      'options', q.options,
      'correct_answers', q.correct_answers
    ) ORDER BY q.position
  ) AS questions,
  MIN(q.question_order) AS question_order,
  NULL AS options,
  NULL AS correct_answers
FROM gap_groups g
JOIN questions q ON q.group_id = g.id AND q.type = 'gap_child'
GROUP BY g.id, g.passage

ORDER BY question_order;




<template>
  <div v-for="(q, index) in questions" :key="q.id" class="mb-4">
    <div v-if="q.type === 'regular'">
      <p><strong>Q{{ index + 1 }}.</strong> {{ q.text }}</p>
      <ul>
        <li v-for="opt in q.options" :key="opt">
          <input type="radio" :name="'q' + q.id" :value="opt" /> {{ opt }}
        </li>
      </ul>
    </div>

    <div v-else-if="q.type === 'gap_group'">
      <p><strong>Q{{ index + 1 }}.</strong></p>
      <p>
        <span v-html="generateGapText(q.passage, q.questions, index + 1)"></span>
      </p>
      <div v-for="(gq, gi) in q.questions" :key="gq.id">
        <p>({{ index + gi + 1 }})</p>
        <ul>
          <li v-for="opt in gq.options" :key="opt">
            <input type="radio" :name="'q' + gq.id" :value="opt" /> {{ opt }}
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
const questions = ref([]); // fetched from backend

function generateGapText(passage, gaps, startIndex) {
  let counter = startIndex;
  return passage.replace(/--/g, () => `(${counter++})`);
}
</script>


(
  SELECT 
    id,
    'regular' AS type,
    NULL AS group_id,
    NULL AS passage,
    question_order,
    JSON_OBJECT(
      'id', id,
      'type', 'regular',
      'text', text,
      'options', options,
      'correct_answers', correct_answers
    ) AS question_data
  FROM questions
  WHERE type = 'regular'
)

UNION ALL

(
  SELECT 
    gg.id + 1000000 AS id,  -- Artificial ID for sorting, won't clash with real question IDs
    'gap_group' AS type,
    gg.id AS group_id,
    gg.passage,
    MIN(q.id) AS question_order,
    JSON_OBJECT(
      'type', 'gap_group',
      'group_id', gg.id,
      'passage', gg.passage,
      'questions', (
        SELECT JSON_ARRAYAGG(
          JSON_OBJECT(
            'id', q2.id,
            'options', q2.options,
            'correct_answers', q2.correct_answers
          )
        )
        FROM questions q2
        WHERE q2.gap_group_id = gg.id
        ORDER BY q2.id
      )
    ) AS question_data
  FROM gap_groups gg
  JOIN questions q ON q.gap_group_id = gg.id
  GROUP BY gg.id, gg.passage
)

ORDER BY question_order;


[
  {
    type: 'regular',
    id: 12,
    class_id: 3,
    subject_id: 7,
    topic_id: 2,
    text: "What's the capital of Nigeria?",
    options: ["Lagos", "Abuja", "Kano", "Port Harcourt"],
    correct_answers: ["Abuja"]
  },
  {
    type: 'regular',
    id: 15,
    class_id: 3,
    subject_id: 7,
    topic_id: 2,
    text: "Who discovered gravity?",
    options: ["Newton", "Einstein", "Edison", "Tesla"],
    correct_answers: ["Newton"]
  },
  {
    type: 'gap_group',
    group_id: 22,
    class_id: 3,
    subject_id: 7,
    topic_id: 2,
    passage: "He went to -- and then came --",
    questions: [
      {
        id: 60,
        text: "-- (Gap 1)",
        options: ["school", "market", "office"],
        correct_answers: ["school"]
      },
      {
        id: 61,
        text: "-- (Gap 2)",
        options: ["home", "hospital", "mosque"],
        correct_answers: ["home"]
      }
    ]
  },
  {
    type: 'gap_group',
    group_id: 28,
    class_id: 3,
    subject_id: 7,
    topic_id: 2,
    passage: "The cat -- the mouse and -- on the roof",
    questions: [
      {
        id: 81,
        text: "-- (Gap 1)",
        options: ["chased", "ate", "found"],
        correct_answers: ["chased"]
      },
      {
        id: 82,
        text: "-- (Gap 2)",
        options: ["slept", "sat", "hid"],
        correct_answers: ["sat"]
      }
    ]
  }
]



<template>
  <div v-for="(q, i) in questions" :key="q.id" class="mb-4">
    <div v-if="q.type === 'regular'">
      <p><strong>Q{{ displayCounter++ }}.</strong> {{ q.text }}</p>
      <ul>
        <li v-for="opt in q.options" :key="opt">
          <input type="radio" :name="'q' + q.id" :value="opt" /> {{ opt }}
        </li>
      </ul>
    </div>

    <div v-else-if="q.type === 'gap_group'">
      <p><strong>Q{{ displayCounter }}.</strong></p>
      <p>
        <span v-html="generateGapText(q.passage, q.questions, displayCounter)"></span>
      </p>
      <div v-for="gq in q.questions" :key="gq.id">
        <p>({{ displayCounter++ }})</p>
        <ul>
          <li v-for="opt in gq.options" :key="opt">
            <input type="radio" :name="'q' + gq.id" :value="opt" /> {{ opt }}
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const questions = ref([]); // fetched from backend
let displayCounter = 1; // controls numbering across all questions

function generateGapText(passage, gaps, startIndex) {
  let counter = startIndex;
  return passage.replace(/--/g, () => `(${counter++})`);
}
</script>


import { defineStore } from 'pinia'

export const useQuestionStore = defineStore('questions', () => {
  const questions = ref([])

  function setQuestions(data) {
    const formatted = []
    let counter = 1

    for (const item of data) {
      if (item.type === 'regular') {
        formatted.push({
          ...item,
          displayNumber: counter++,
          selected: null,
          options: shuffle([...item.options]),
        })
      }

      else if (item.type === 'gap_group') {
        const inner = []
        for (const gq of item.questions) {
          inner.push({
            ...gq,
            displayNumber: counter++,
            selected: null,
            options: shuffle([...gq.options]),
          })
        }

        formatted.push({
          id: item.id,
          type: 'gap_group',
          passage: item.passage,
          questions: inner,
        })
      }
    }

    questions.value = formatted
  }

  function shuffle(arr) {
    for (let i = arr.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1))
      ;[arr[i], arr[j]] = [arr[j], arr[i]]
    }
    return arr
  }

  return {
    questions,
    setQuestions,
  }
})



import { useQuestionStore } from '@/stores/questions'

const { questions, setQuestions } = useQuestionStore()

onMounted(async () => {
  const response = await fetch('/api/questions')
  const data = await response.json()
  setQuestions(data)
})


{
  id: 1,
  type: 'regular',
  displayNumber: 1,
  selected: null,
  options: [...shuffled],
}

{
  id: 22,
  type: 'gap_group',
  passage: "It is a -- day to -- outside.",
  questions: [
    {
      id: 301,
      displayNumber: 2,
      selected: null,
      options: [...shuffled],
    },
    {
      id: 302,
      displayNumber: 3,
      selected: null,
      options: [...shuffled],
    }
  ]
}


const payload = []

for (const q of questions.value) {
  if (q.type === 'regular') {
    payload.push({ id: q.id, selected: q.selected })
  } else if (q.type === 'gap_group') {
    for (const gq of q.questions) {
      payload.push({ id: gq.id, selected: gq.selected })
    }
  }
}


getFlattenedAnswers: function () {
  const answers = [];

  for (const q of this.questions) {
    if (q.type === 'regular') {
      answers.push({ id: q.id, selected: q.selected });
    } else if (q.type === 'gap_group') {
      for (const sub of q.questions) {
        answers.push({ id: sub.id, selected: sub.selected });
      }
    } else if (q.type === 'mixed') {
      // Optional: handle mixed the same way
      for (const part of q.questions) {
        answers.push({ id: part.id, selected: part.selected });
      }
    }
  }

  return answers;
}


const payload = examStore.getFlattenedAnswers();
await axios.post('/api/submit-answers', payload);


function getSelectedAnswers() {
  const answers = [];

  questions.value.forEach(q => {
    if (q.type === 'regular') {
      answers.push({ id: q.id, selected: q.selected ?? '' });
    } else if (q.type === 'gap_group') {
      q.questions.forEach(subQ => {
        answers.push({ id: subQ.id, selected: subQ.selected ?? '' });
      });
    }
  });

  return answers;
}
<style>

.gap-label {
  font-weight: bold;
  margin-right: 4px;
}
input {
  width: 120px;
  display: inline-block;
  margin-right: 12px;
}
</style>