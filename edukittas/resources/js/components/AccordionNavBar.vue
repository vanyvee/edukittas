<script setup>
import { CheckIcon, ClipboardDocumentCheckIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  store:               { type: Object,  required: true },
  allSelected:         { type: Boolean, default: false },
  viewType:            { type: String,  default: 'list' },
  activeTab:           { type: String,  default: null },
  tabs:                { type: Array,   default: () => [] },
})

const emit = defineEmits([
  'select-all',
  'toggle-attendance',
  'set-view',
  'set-tab',
])
</script>

<template>
  <div
    class="flex items-center justify-between px-3 py-2 sm:px-4 sm:py-3 shrink-0 z-10
    transition-colors duration-300 h-[56px] sm:h-[64px]"
    :class="store.attendanceMode ? 'bg-[#b91c1c]' : 'bg-[#075e54]'"
  >

    <!-- LEFT: Select All + count -->
    <div class="flex items-center gap-2 min-w-[80px]">
      <Transition name="fade">
        <button
          v-if="store.selectionMode"
          @click="emit('select-all')"
          class="flex items-center gap-2 bg-transparent border-none text-white text-sm
          cursor-pointer px-2 py-1 rounded active:bg-white/20 transition-colors
          touch-manipulation min-h-[44px]"
        >
          <span
            class="w-5 h-5 sm:w-6 sm:h-6 border-2 border-white rounded-full flex items-center
            justify-center transition-all shrink-0"
            :class="allSelected ? 'bg-white text-[#075e54]' : ''"
          >
            <CheckIcon v-if="allSelected" class="w-3 h-3 sm:w-4 sm:h-4 stroke-[3]" />
          </span>
          <span class="hidden sm:inline text-white/90 text-xs font-medium">
            {{ allSelected ? 'None' : 'All' }}
          </span>
        </button>
      </Transition>

      <Transition name="fade">
        <span
          v-if="store.selectionMode && store.selectedItems.size > 0"
          class="text-xs sm:text-sm font-semibold text-white/90 whitespace-nowrap"
        >
          {{ store.selectedItems.size }}{{ store.attendanceMode ? ' Absent' : '' }}
        </span>
      </Transition>
    </div>

    <!-- CENTER: Full View / Mini View -->
    <div class="min-w-[80px] flex justify-center">
      <button
        @click="emit('set-view', viewType === 'list' ? 'fullview' : 'list')"
        class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs sm:text-sm
        font-medium transition-all touch-manipulation min-h-[36px] border"
        :class="store.attendanceMode
          ? 'bg-white text-[#b91c1c] border-white'
          : 'bg-white/15 text-white border-white/30 hover:bg-white/25'"
      >
        {{ viewType === 'list' ? 'Full View' : 'Mini View' }}
      </button>
    </div>

    <!-- RIGHT: Take Attendance -->
    <div class="min-w-[80px] flex justify-end">
      <button
        @click="emit('toggle-attendance')"
        class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs sm:text-sm
        font-medium transition-all touch-manipulation min-h-[36px] border"
        :class="store.attendanceMode
          ? 'bg-white text-[#b91c1c] border-white'
          : 'bg-white/15 text-white border-white/30 hover:bg-white/25'"
      >
        <ClipboardDocumentCheckIcon class="w-4 h-4 shrink-0" />
        <span class="hidden sm:inline">
          {{ store.attendanceMode ? 'Cancel' : 'Take Attendance' }}
        </span>
      </button>
    </div>

  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from,  .fade-leave-to      { opacity: 0; }
</style>
