<template>
  <div>
    <div class="table-container" v-if="showGradeSheet">
      <!-- Buttons to activate columns -->
      <div class="buttons mb-2">
        <button @click="activateColumn('assignment')">Edit Assignment</button>
        <button @click="activateColumn('test1')">Edit CA1</button>
        <button @click="activateColumn('test2')">Edit CA2</button>
        <button @click="activateColumn('exam')">Edit Exam</button>
      </div>

      <!-- Table displaying fields -->
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Student</th>
            <th>Assignment</th>
            <th>CA1</th>
            <th>CA2</th>
            <th>Exam</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(grade, index) in tempStudentGrades" :key="grade.id">
            <!-- Student info -->
            <td>
              <img src="/image/grade.image" class="me-2" />
              <button class="btn btn-link p-0">
                {{ grade.surname + ' ' + grade.firstName }}
              </button>
            </td>

            <!-- Assignment -->
            <td>
              <input
                v-model="grade.assignment"
                :disabled="activeColumn !== 'assignment'"
                :class="{ 'edited-field': isEdited(grade.id, 'assignment') }"
                @change="markAsEdited(grade, 'assignment')"
                @keydown.space.prevent="moveToNextField(index, 'assignment')"
                @keydown.enter.prevent="moveToNextField(index, 'assignment')"
              />
            </td>

            <!-- CA1 -->
            <td>
              <input
                v-model="grade.test1"
                :disabled="activeColumn !== 'test1'"
                :class="{ 'edited-field': isEdited(grade.id, 'test1') }"
                @change="markAsEdited(grade, 'test1')"
                @keydown.space.prevent="moveToNextField(index, 'test1')"
                @keydown.enter.prevent="moveToNextField(index, 'test1')"
              />
            </td>

            <!-- CA2 -->
            <td>
              <input
                v-model="grade.test2"
                :disabled="activeColumn !== 'test2'"
                :class="{ 'edited-field': isEdited(grade.id, 'test2') }"
                @change="markAsEdited(grade, 'test2')"
                @keydown.space.prevent="moveToNextField(index, 'test2')"
                @keydown.enter.prevent="moveToNextField(index, 'test2')"
              />
            </td>

            <!-- Exam -->
            <td>
              <input
                v-model="grade.exam"
                :disabled="activeColumn !== 'exam'"
                :class="{ 'edited-field': isEdited(grade.id, 'exam') }"
                @change="markAsEdited(grade, 'exam')"
                @keydown.space.prevent="moveToNextField(index, 'exam')"
                @keydown.enter.prevent="moveToNextField(index, 'exam')"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Action buttons -->
    <div class="mt-3">
      <button
        :disabled="!myStore.editMode"
        v-show="showGradeSheet"
        @click="myStore.saveEdit(editedItems)"
      >
        Submit Grade
      </button>

      <button @click="reset">Reset</button>

      <button :disabled="!myStore.editMode" @click="showGradeSheet = true">
        View Stats
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import classStat from './classStat.vue';
import schoolStat from './schoolStat.vue';

// --- state ---
const showGradeSheet = ref(true);
const activeColumn = ref('');
const editedGrades = reactive(new Map());

// mimic store data
const myStore = {
  editMode: ref(false),
  grades: [
    { id: 1, surname: 'Doe', firstName: 'Jane', assignment: 10, test1: 15, test2: 20, exam: 50 },
    { id: 2, surname: 'Smith', firstName: 'John', assignment: '', test1: '', test2: '', exam: '' },
  ],
  saveEdit(data) {
    console.log('Submitting:', data);
  },
  editedItems: new Map(),
};

const tempStudentGrades = reactive([...myStore.grades]);

// --- logic ---
const moveToNextField = (currentIndex, column) => {
  const nextIndex = currentIndex + 1;
  if (nextIndex < tempStudentGrades.length) {
    const inputs = document.querySelectorAll(`input[v-model="${column}"]`);
    if (inputs[nextIndex]) inputs[nextIndex].focus();
  }
};

const markAsEdited = (grade, field) => {
  if (!editedGrades.has(grade.id)) {
    editedGrades.set(grade.id, {});
  }
  editedGrades.get(grade.id)[field] = grade[field];
};

const isEdited = (id, field) => {
  return editedGrades.has(id) && editedGrades.get(id)[field] !== undefined;
};

const activateColumn = (column) => {
  myStore.editMode.value = true;
  activeColumn.value = activeColumn.value === column ? '' : column;
};

const reset = () => {
  myStore.editMode.value = false;
  editedGrades.clear();
  activeColumn.value = '';
  showGradeSheet.value = true;
};

// computed structure to send to backend
const editedItems = computed(() =>
  Array.from(editedGrades, ([id, fields]) => ({ id, ...fields }))
);
</script>

<style scoped>
.table-container {
  position: relative;
  max-height: 400px;
  overflow: auto;
}

thead th {
  position: sticky;
  top: 0;
  background-color: #f8f9fa;
  z-index: 2;
}

tbody td:first-child,
thead th:first-child {
  position: sticky;
  left: 0;
  background-color: #fff;
  z-index: 1;
}

/* highlight edited inputs */
.edited-field {
  border: 2px solid red !important;
}

/* hide scrollbar on mobile */
@media (max-width: 768px) {
  .table-container::-webkit-scrollbar {
    display: none;
  }
  .table-container {
    -ms-overflow-style: none;
    scrollbar-width: none;
  }
}
</style>


class stats
1 top 3 ...based on teacher remarks, student remarks, grade
2 top 3 based on just grade
3 average age
4 troublesome set
5 top 3 per subject...english, math, bsc, biology, chem, phy, economic, govt, lit
6 class average trend for 4 year by term
7 class currennt total
7 subjectsqlllp average 
7 most pass/failed subj  
8 class population trend
9 percentage on owing fees list this term
10 amount owed this term
11 amount owed in areas previously
12 total payment made so far
13 top 5 debtors
14 


stat school

1 top 10 based on teacher remark, student and grade
2 top 10 base soley on grade
3 most competitive class based on mean deviation from class average
4 least competitive class
5 class wwith highest failuure rate
6 class with highest pass rate

7 most bbrilliant class....base on high cclass avcg
8 classs witrh highest good remarks
9 cclass wuth highest bad remmark
10 most populated
11 least populated
12 school to 5 school notorious
13 face of the school winner. boy/girl
14 class with h  ighest      debt
15 school population trend
16 school student brilliance trend
17 percentage owing
18 amount in areas
19 top 10 debtors...
20 teachers rating based on student remarks
21 teachers rating based on teachers remark


