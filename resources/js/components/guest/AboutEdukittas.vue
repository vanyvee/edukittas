<script setup>
import { provide } from 'vue'
import MyAccordion from '@/components/MyAccordion.vue'

// Draft copy: edit the topics freely.
const topics = [
  { id: '1', icon: '📝', title: 'Computer-based tests', summary: 'Exams, tests, practice and quizzes',
    body: 'Teachers set up timed exams, tests, practice and quizzes. Students take them on any device, and answers are graded automatically the moment they submit.' },
  { id: '2', icon: '📊', title: 'Scores and results', summary: 'Score sheets, totals and report data',
    body: 'Teachers record first test, second test, other tests and exam scores on one sheet. Scores from computer-based tests can be pushed into the sheet in a click, and results are compiled when final.' },
  { id: '3', icon: '👩‍🏫', title: 'Classes and teachers', summary: 'Who teaches what, and where',
    body: 'Assign teachers to the classes and subjects they teach. Teachers see and manage only their own classes.' },
  { id: '4', icon: '👨‍👩‍👧', title: 'Parents and guardians', summary: 'Follow your child at school',
    body: 'Guardians sign in to follow their children, including those in different classes, and see what the school shares with them.' },
  { id: '5', icon: '🎮', title: 'Quiz game hall', summary: 'Learning that feels like play',
    body: 'Live quiz games built from the school question bank, for classes to play together.' },
  { id: '6', icon: '🏫', title: 'For school owners', summary: 'Register and get started',
    body: 'Register your school, add your staff and classes, and run tests, scores and results from one place.' },
]

const displayConfig = {
  avatar: 'icon',
  title: 'title',
  subtitle: 'summary',
  timestamp: () => '',
}

// Guests only read: this keeps selection and attendance modes switched off inside the accordion.
provide('accordionStore', {
  get selectionMode() { return false }, set selectionMode(_) {},
  get attendanceMode() { return false }, set attendanceMode(_) {},
  selectedItems: new Map(),
  toggleSelection() {},
})
</script>

<template>
  <div class="h-full pt-14 pb-14">
    <MyAccordion
      :items="topics"
      :display-config="displayConfig"
      key-field="id"
      :tabs="[{ key: 'details', label: 'Details' }]"
    >
      <template #nav-header>
        <div class="px-4 py-3 border-b border-neutral-300 bg-surface-300">
          <h1 class="font-semibold text-neutral-900">About Edukittas</h1>
          <p class="text-xs text-neutral-700">Pick a topic to read more</p>
        </div>
      </template>

      <!-- Phone: opens under the row -->
      <template #accordion-body="{ item }">
        <p class="p-4 text-sm text-neutral-900">{{ item.body }}</p>
      </template>

      <!-- Desktop: opens at the right -->
      <template #details="{ item }">
        <div v-if="item" class="p-6 overflow-y-auto text-neutral-900">
          <p class="text-4xl">{{ item.icon }}</p>
          <h2 class="text-xl font-bold mt-2">{{ item.title }}</h2>
          <p class="mt-3 text-neutral-900">{{ item.body }}</p>
        </div>
        <p v-else class="p-6 text-neutral-700">Pick a topic on the left.</p>
      </template>
    </MyAccordion>
  </div>
</template>
