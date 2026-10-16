
<template>
  <div class="accordion-container" :id = "prob.id">
    <!-- LEFT SIDE: Accordion -->
    <div class="accordion-wrapper">
      <div v-for="item in items" :key="item[keyField]" class="accordion-item">
        <img
          :src="item.passport"
          class="rounded-circle thumbnail"
          @click.stop="animateZoom(item, $event)"
        />

        <button
          class="accordion-button"
          :class="{
            active: activeId === item[keyField],
            selected: myStore.selectedItems.has(item[keyField])
          }"
          @mousedown="startPress(item)"
          @mouseup="clearPress"
          @mouseleave="clearPress"
          @touchstart="startPress(item)"
          @touchend="clearPress"
          @click="handleClick(item)"
        >
          {{ item.surname }} {{ item.firstName }}
        </button>

        <!-- MOBILE INLINE DETAILS (only visible on mobile and when item is active) -->
        
          <SoftUnmount
            v-if="isMobile && activeId === item[keyField]"  :linger ="10000"
            class="accordion-body">

            <slot name="details" :item="item" />
          </SoftUnmount>
      </div>
    </div>

    <!-- RIGHT PANEL: Desktop-only panel (always mounted; shown only when !isMobile) -->

      <div v-if="!isMobile" class="details-panel">
          <slot v-if = "mainStore.selectedItems.size" name="details"
         :item="myStore.getSelectedArray()[0]"
         >
           
          </slot>

          <div v-show = "!mainStore.selectedItems.size">
            <ClassAnalytics v-if = "props.title === 'student'"  :classId = "mainStore.currentClass.value"/>
            <TeachersAnalytics v-if = "props.title === 'Staff'"  :classId = "mainStore.currentClass.value"/>
          </div>
        </div>

    <!-- ZOOM OVERLAY -->
    <div v-if="zoomedItem" class="zoom-overlay" @click.self="closeZoom">
      <img ref="zoomImage" :src="zoomedItem.passport" class="zoom-img" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, onBeforeUnmount } from "vue";
import gsap from "gsap";
import { useMainStore } from "@/stores/mainStore";
import { useMyStore } from "@/stores/myStore";
impory SoftUnmount from "@/components/SoftUnmount.vue";
import ClassAnalytics from "@/components/ClassAnalytics.vue";
import StudentDetails from "@/components/StudentDetails.vue";

const mainStore = useMainStore();
const myStore = useMyStore();

const props = defineProps({
  title: { type: String, required: true },
  id: { type: String, required: true },
  items: { type: Array, required: true },
  keyField: { type: String, default: "id" },
  enableSelection: { type: Boolean, default: false }
});

const activeId = ref(null);
const selectedItem = ref(null);

/* responsive */
const windowWidth = ref(window.innerWidth);
const isMobile = computed(() => windowWidth.value < 768);
const resizeHandler = () => (windowWidth.value = window.innerWidth);
window.addEventListener("resize", resizeHandler);


function handleClick(item) {
  clearPress();
  if (myStore.selectionMode) {
    myStore.toggleSelection(item, props.keyField);
    return;
  }
  toggleItem(item);
}

/* toggle */
function toggleItem(item) {
  const key = item[props.keyField];

  if (isMobile.value) {
    activeId.value = activeId.value === key ? null : key;
  } else {
    myStore.clearSelection();            // only one selected for PC click
    myStore.toggleSelection(item, props.keyField);
  }
  // unified profile update (always first of array)
  const selectedArray = myStore.getSelectedArray();
  myStore.setProfile(selectedArray[0] || null);
}

/* LONG-PRESS & MULTI-SELECTION */
let pressTimer = null;
function startPress(item) {
  if (!props.enableSelection) return;
  clearTimeout(pressTimer);
  pressTimer = setTimeout(() => {
    myStore.toggleSelection(item, props.keyField);
  }, 600);
}
function clearPress() {
  clearTimeout(pressTimer);
}



/* GSAP ZOOM */
const zoomedItem = ref(null);
const zoomImage = ref(null);
let sourceRect = null;

function animateZoom(item, event) {
  zoomedItem.value = item;
  sourceRect = event.target.getBoundingClientRect();

  const containerRect = document
    .querySelector(".accordion-wrapper")
    .getBoundingClientRect();

  nextTick(() => {
    const img = zoomImage.value;
    if (!img) return;

    // Calculate position relative to accordion container
    const topOffset = sourceRect.top - containerRect.top;
    const leftOffset = sourceRect.left - containerRect.left;

    gsap.set(img, {
      position: "absolute",
      top: topOffset,
      left: leftOffset,
      width: sourceRect.width,
      height: sourceRect.height,
      borderRadius: "50%",
      transformOrigin: "center"
    });

    gsap.to(img, {
      duration: 0.4,
      top: "50%",
      left: "50%",
      x: "-50%",
      y: "-50%",
      width: 200,
      height: 200,
      borderRadius: "15px",
      ease: "power2.out"
    });
  });
}

function closeZoom() {
  if (!zoomImage.value || !sourceRect) return;
  gsap.to(zoomImage.value, {
    duration: 0.4,
    top: sourceRect.top + window.scrollY,
    left: sourceRect.left + window.scrollX,
    x: 0,
    y: 0,
    width: sourceRect.width,
    height: sourceRect.height,
    borderRadius: "50%",
    ease: "power2.in",
    onComplete: () => (zoomedItem.value = null)
  });
}

onBeforeUnmount(() => {
  window.removeEventListener("resize", resizeHandler);
  clearTimeout(pressTimer);
});
</script>
<style scoped>
.accordion-container {
  display: flex;
  flex-direction: row;
  height: 100vh;
  overflow: hidden;
}

/* LEFT PANEL (Accordion list) */
.accordion-wrapper {
  flex: 0 0 100%; /* full width on mobile by default */
  overflow-y: auto;
  position: relative;
  overflow: hidden;
  border-right: 1px solid #ddd;
  padding: 1rem;
  background: #f8f9fa;
  transition: flex 0.3s ease;
}

.accordion-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  border-bottom: 1px solid #eee;
  padding: 0.5rem 0;
}

.thumbnail {
  width: 50px;
  height: 50px;
  object-fit: cover;
  border-radius: 50%;
  cursor: pointer;
}

/* BUTTON STYLES */
.accordion-button {
  flex: 1;
  text-align: left;
  background: none;
  border: none;
  padding: 0.5rem;
  cursor: pointer;
  font-weight: 500;
  transition: background 0.2s;
}
.accordion-button.active {
  background: #007bff;
  color: #fff;
}
.accordion-button.selected {
  background: #ffc107;
  color: #fff;
}

/* RIGHT PANEL (Details for Desktop) */
.details-panel {
  flex: 1; /* takes remaining width */
  background: white;
  height: 100vh; /* full viewport height */
  overflow: hidden; /* no scroll unless child content exceeds */
  padding: 1rem;
  box-shadow: -2px 0 10px rgba(0, 0, 0, 0.06);
}

/* TRANSITION EFFECT (Mobile inline) */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.25s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

/* ZOOM OVERLAY */
.zoom-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.65);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Ensure the zoom image doesn't exceed accordion width */
.zoom-img {
  z-index: 1001;
  position: absolute;
  max-width: 90%;
  max-height: 90%;
  border-radius: 10px;
  object-fit: cover;
  transition: transform 0.3s ease;
  pointer-event: none;
}

/* MOBILE: full screen zoom (like WhatsApp) */
@media (max-width: 767px) {
  .zoom-overlay {
    position: fixed;
    inset: 0;
    z-index: 2000;
  }
}
/* ========== RESPONSIVE LAYOUT ========== */
@media (min-width: 768px) {
  .accordion-wrapper {
    flex: 0 0 20%; /* 20% width on desktop */
  }
  .details-panel {
    flex: 0 0 80%; /* 80% width on desktop */
  }
}
</style>