<script setup>
import { CheckIcon } from '@heroicons/vue/24/outline'

defineProps({
  selectionMode:  { type: Boolean, default: false },
  selectedCount:  { type: Number,  default: 0 },
  allSelected:    { type: Boolean, default: false },
  attendanceMode: { type: Boolean, default: false },
  viewType:       { type: String,  default: 'list' },
})

defineEmits(['toggle-select-all', 'toggle-view', 'toggle-attendance'])
</script>

<template>
  <div
    class="flex items-center justify-between px-3 py-2 sm:px-4 sm:py-3 shrink-0 z-10
    transition-colors duration-300 h-[56px] sm:h-[64px]"
    :class="attendanceMode ? 'bg-danger' : 'bg-primary'"
  >

    <!-- LEFT: Select All -->
    <div class="flex items-center gap-2 min-w-[80px]">
      <Transition name="fade">
        <button
          v-if="selectionMode"
          @click="$emit('toggle-select-all')"
          class="flex items-center gap-2 bg-transparent border-none text-white text-sm
          cursor-pointer px-2 py-1 rounded active:bg-white/20 transition-colors
          touch-manipulation min-h-[44px]"
        >
          <span
            class="w-5 h-5 sm:w-6 sm:h-6 border-2 border-white rounded-full flex items-center
            justify-center transition-all shrink-0"
            :class="allSelected ? 'bg-white text-primary' : ''"
          >
            <CheckIcon v-if="allSelected" class="w-3 h-3 sm:w-4 sm:h-4 stroke-[3]" />
          </span>
          <span class="hidden sm:inline text-white/90 text-xs font-medium">
            {{ allSelected ? 'None' : 'All' }}
          </span>
        </button>
      </Transition>

      <!-- Count badge -->
      <Transition name="fade">
        <span
          v-if="selectionMode && selectedCount > 0"
          class="text-xs sm:text-sm font-semibold text-white/90 whitespace-nowrap"
        >
          {{ selectedCount }}{{ attendanceMode ? ' Absent' : '' }}
        </span>
      </Transition>
    </div>

    <!-- CENTER: View toggle -->
    <div class="min-w-[80px] flex justify-end">
      <button
        @click="$emit('toggle-view')"
        class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs sm:text-sm
        font-medium transition-all touch-manipulation min-h-[36px] border"
        :class="attendanceMode
          ? 'bg-white text-danger border-white'
          : 'bg-white/15 text-white border-white/30 hover:bg-white/25'"
      >
        <span>{{ viewType === 'list' ? 'Full View' : 'Mini View' }}</span>
      </button>
    </div>

    <!-- RIGHT: Attendance toggle -->
    <div class="min-w-[80px] flex justify-end">
      <button
        @click="$emit('toggle-attendance')"
        class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs sm:text-sm
        font-medium transition-all touch-manipulation min-h-[36px] border"
        :class="attendanceMode
          ? 'bg-white text-danger border-white'
          : 'bg-white/15 text-white border-white/30 hover:bg-white/25'"
      >
        <span class="hidden sm:inline">
          {{ attendanceMode ? 'Cancel' : 'Take Attendance' }}
        </span>
      </button>
    </div>

  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from,  .fade-leave-to      { opacity: 0; }
</style>