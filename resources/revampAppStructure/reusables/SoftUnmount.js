/// this snippet is used to wrap view 
//that you want to linger a bit in DOM 
//after unmount
<template>
  <Transition name="fade" @after-leave="startDelay">
    <KeepAlive>
      <div v-if="visible" class="fade-wrapper">
        <slot />
      </div>
    </KeepAlive>
  </Transition>
</template>

<script setup>
import { ref, watch, onUnmounted } from 'vue'

// Props: you can reuse this component anywhere
const props = defineProps({
  linger: { type: Number, default: 8000 } // milliseconds
})

const visible = ref(true)
let timer = null

// When the transition finishes (component faded out)
const startDelay = () => {
  clearTimeout(timer)
  timer = setTimeout(() => {
    visible.value = false // finally remove from DOM after delay
  }, props.linger)
}

// If user shows the component again quickly → cancel destruction
watch(visible, (newVal) => {
  if (newVal) clearTimeout(timer)
})

// Clean up timer when wrapper itself is really gone
onUnmounted(() => {
  clearTimeout(timer)
})
</script>

<style scoped>
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-leave-to {
  opacity: 0;
}
.fade-wrapper {
  transition: opacity 0.3s ease;
}
</style>

