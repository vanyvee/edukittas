<template>
  <Transition name="fade" @after-leave="handleAfterLeave">
    <div v-if="internalVisible" class="fade-wrapper h-full">
      <slot />
    </div>
  </Transition>
</template>

<script setup>
import { ref, watch, onUnmounted } from 'vue'

const props = defineProps({
  show: { type: Boolean, default: true },
  linger: { type: Number, default: 2500 }
})

const internalVisible = ref(props.show)
let lingerTimer = null

// When parent says hide, start fade out
watch(() => props.show, (newVal) => {
  if (newVal) {
    // Parent wants to show → clear any pending destruction
    clearTimeout(lingerTimer)
    internalVisible.value = true
  } else {
    // Parent wants to hide → just fade out (don't destroy yet)
    internalVisible.value = false
  }
}, { immediate: true })

// After fade completes, wait `linger` ms before unmounting
const handleAfterLeave = () => {
  clearTimeout(lingerTimer)
  lingerTimer = setTimeout(() => {
    // Component stays hidden after linger period
  }, props.linger)
}

onUnmounted(() => {
  clearTimeout(lingerTimer)
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.fade-wrapper {
  transition: opacity 0.3s ease;
}
</style>























<!-- /// this snippet is used to wrap view 
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
 -->
