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
          selected: isSelected(item[keyField])
        }"
        @click="handleClick(item)"
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
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useCbtStore } from "@/stores/useCbtStore"; // if using store

const router = useRouter();
const cbtStore = useCbtStore();

const props = defineProps({
  label: { type: String, default: "" },
  type: { type: String, default: "student" }, // for store separation
  extraQuery: { type: Object, default: () => ({}) },
  goto: { type: [String, null], default: null },
  items: { type: Array, required: true },
  keyField: { type: String, default: "id" },
  enableSelection: { type: Boolean, default: true },
  singleSelection: { type: Boolean, default: false }, // 👈 new prop
});


const activeId = ref(null);
function isSelected(key) {
  const target = cbtStore.getSelection(props.type)
  return target?.includes(key)
}
function handleClick(item) {
  const key = item[props.keyField]
  // Navigation case
  if (props.goto) {
    router.push({ path: props.goto, query: { ...item } })
    return
  }
  // Accordion toggle
  activeId.value = activeId.value === key ? null : key
  // If selection disabled, exit early
  if (!props.enableSelection) return
  // Use store helper — no more manual push/splice
  cbtStore.toggleSelection(props.type, key, props.singleSelection)
}
</script>

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

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.25s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
