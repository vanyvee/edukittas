<template>
  <div class="accordion-wrapper">
    <div>{{ label }}</div>

    <div
      v-for="item in items"
      :key="item[keyField]"
      class="accordion-item"
    >
      <img :src="item.passport" class="thumbnail" />

      <button
        class="accordion-button"
        :class="{
          active: activeId === item[keyField],
          selected: isSelected(item)
        }"
        @click="handleClick(item)"
        @dblclick="activateSelection(item)"
        @mousedown="startPress(item)"
        @mouseup="cancelPress"
        @mouseleave="cancelPress"
      >
        {{ item.surname ? item.surname + ' ' + item.firstName : item.name }}
      </button>

      <transition name="fade">
        <div v-if="activeId === item[keyField]" class="accordion-body">
          <slot name="details" :item="item" />
        </div>
      </transition>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, defineExpose } from "vue";
import { useRouter } from "vue-router";
import { useMainStore } from '@/stores/useMainStore'
const mainStore = useMainStore()

const props = defineProps({
  label: { type: String, default: "" },
  items: { type: Array, required: true },
  keyField: { type: String, default: "id" },
  goto: { type: [String, null], default: null },
  attendanceMode: { type: Boolean, default: false }, // 👈 global flag
  extraQuery: { type: Object, default: () => ({}) }, //
  singleSelection: { type: Boolean, default: false },
});

const router = useRouter();
const activeId = ref(null);
const selectedItems = ref([]);
// computed view: if singleSelection is true, only show the first selected item
const displayedSelected = computed(() =>
  props.singleSelection ? selectedItems.value.slice(0, 1) : selectedItems.value
);
const selectionMode = ref(false);
let pressTimer = null;

// check if item is selected
const isSelected = (item) =>
  displayedSelected.value.some((s) => s[props.keyField] === item[props.keyField]);

// handle normal click
function handleClick(item) {
  const key = item[props.keyField];
  if (selectionMode.value || mainStore.attendanceMode) {
    toggleSelection(item);
    return;
  }
  // open/close accordion
  activeId.value = activeId.value === key ? null : key;
  // optional navigation
  if (props.goto) router.push({ path: props.goto, query: { ...item, ...props.extraQuery } });
}

// toggle selection
function toggleSelection(item) {
  const key = item[props.keyField];
  const index = selectedItems.value.findIndex(
    (s) => s[props.keyField] === key
  );

  if (props.singleSelection) {
    // Single-select mode: either replace or clear
    if (index >= 0) selectedItems.value = [];
    else selectedItems.value = [item];
  } else {
    // Multi-select mode (normal)
    if (index >= 0) selectedItems.value.splice(index, 1);
    else selectedItems.value.push(item);
  }

  if (selectedItems.value.length === 0 && !props.attendanceMode)
    selectionMode.value = false;
}

// activate selection mode by double click or long press
function activateSelection(item) {
  if (!selectionMode.value) selectionMode.value = true;
  toggleSelection(item);
}

// long press logic
function startPress(item) {
  cancelPress();
  pressTimer = setTimeout(() => {
    activateSelection(item);
  }, 500); // hold 0.5s to trigger
}

function cancelPress() {
  if (pressTimer) {
    clearTimeout(pressTimer);
    pressTimer = null;
  }
}


defineExpose({ selectedItems: displayedSelected, selectionMode });</script>

<style scoped>
.accordion-wrapper {
  width: 100%;
  overflow-y: auto;
  padding: 1rem;
  background: #f8f9fa;
  border: 1px solid #ddd;
  border-radius: 8px;
}

.accordion-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  border-bottom: 1px solid #eee;
  padding: 0.5rem 0;
}

.thumbnail {
  width: 45px;
  height: 45px;
  object-fit: cover;
  border-radius: 50%;
}

.accordion-button {
  flex: 1;
  text-align: left;
  background: none;
  border: none;
  padding: 0.5rem;
  cursor: pointer;
  font-weight: 500;
}
.accordion-button.active {
  background: #007bff;
  color: #fff;
}
.accordion-button.selected {
  background: #ffc107;
  color: #fff;
}

.accordion-body {
  padding: 0.5rem 0.5rem 0.5rem 3.5rem;
  font-size: 0.9rem;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>






usage


<template>
  <div class="container mt-4">
    <h3>🎓 Student List</h3>

    <SmartAccordionLite
      label="Students"
      :items="students"
      key-field="id"
      :goto="studentRoute"
      :extra-query="{ class: 'SS3', term: 'First' }"
    >
      <template #details="{ item }">
        <div>
          <p><strong>Age:</strong> {{ item.age }}</p>
          <p><strong>Class:</strong> {{ item.className }}</p>
          <p><strong>Score:</strong> {{ item.score }}</p>
        </div>
      </template>
    </SmartAccordionLite>

    <hr />

    <h3>🟢 Multi-selection Example</h3>
    <button class="btn btn-primary mb-2" @click="logSelected">
      Log Selected Students
    </button>

    <SmartAccordionLite
      label="Mark Attendance"
      :items="students"
      key-field="id"
      attendance-mode
      ref="attendanceRef"
    >
      <template #details="{ item }">
        <div>
          <p>Status: {{ isSelected(item) ? 'Present' : 'Absent' }}</p>
        </div>
      </template>
    </SmartAccordionLite>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import SmartAccordionLite from "@/components/SmartAccordionLite.vue";

const studentRoute = "/student/profile";

const students = ref([
  {
    id: 1,
    name: "Mary Jane",
    passport: "https://randomuser.me/api/portraits/women/68.jpg",
    className: "SS3A",
    score: 87,
    age: 17,
  },
  {
    id: 2,
    name: "John Doe",
    passport: "https://randomuser.me/api/portraits/men/12.jpg",
    className: "SS3A",
    score: 92,
    age: 18,
  },
  {
    id: 3,
    name: "Sarah Lee",
    passport: "https://randomuser.me/api/portraits/women/48.jpg",
    className: "SS3B",
    score: 78,
    age: 17,
  },
]);

const attendanceRef = ref(null);

function logSelected() {
  console.log("Selected students:", attendanceRef.value.selectedItems);
}

function isSelected(item) {
  return attendanceRef.value.selectedItems.some(
    (s) => s.id === item.id
  );
}
</script>

<style scoped>
.container {
  max-width: 600px;
}
</style>