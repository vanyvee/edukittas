<script setup>
// The header MyAccordion used to draw by itself, now opt-in. Use it like:
//   <template #nav-header="h"><AccordionDefaultHeader v-bind="h" /></template>
import AccordionHeader from './AccordionHeader.vue'

defineProps({
  store:                { type: Object,   default: null },
  allSelected:          { type: Boolean,  default: false },
  viewType:             { type: String,   default: 'list' },
  selectedCount:        { type: Number,   default: 0 },
  selectedItem:         { type: null,     default: null },
  toggleSelectAll:      { type: Function, default: () => {} },
  toggleAttendanceMode: { type: Function, default: () => {} },
  toggleView:           { type: Function, default: () => {} },
})
</script>

<template>
  <AccordionHeader
    :selection-mode="!!store?.selectionMode"
    :selected-count="selectedCount"
    :all-selected="allSelected"
    :attendance-mode="!!store?.attendanceMode"
    :view-type="viewType"
    @toggle-select-all="toggleSelectAll"
    @toggle-view="toggleView"
    @toggle-attendance="toggleAttendanceMode"
  />
</template>
