<script setup>
import { CheckIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  item:            { type: Object,   required: true },
  isOpen:          { type: Boolean,  default: false },
  isSelected:      { type: Boolean,  default: false },
  isSelectionMode: { type: Boolean,  default: false },
  isAttendanceMode:{ type: Boolean,  default: false },
  getDisplayValue: { type: Function, required: true },
  itemRef:         { type: Function, required: true },
})

const emit = defineEmits(['accordion', 'select', 'checkbox'])
</script>

<template>
  <div
    class="flex items-center transition-colors duration-150"
    :class="[
      isSelected ? 'bg-[#d9fdd3]' : 'hover:bg-[#f5f6f6]',
      isAttendanceMode && isSelected ? 'bg-red-50' : ''
    ]"
  >

    <!-- Checkbox — slides in during selection mode -->
    <Transition
      enter-active-class="transition-all duration-200 ease-out"
      enter-from-class="opacity-0 -translate-x-2"
      enter-to-class="opacity-100 translate-x-0"
      leave-active-class="transition-all duration-150 ease-in"
      leave-from-class="opacity-100 translate-x-0"
      leave-to-class="opacity-0 -translate-x-2"
    >
      <div
        v-if="isSelectionMode"
        class="pl-3 pr-1 flex items-center cursor-pointer touch-manipulation min-h-[48px]"
        @click="emit('checkbox')"
      >
        <span
          class="w-5 h-5 sm:w-6 sm:h-6 rounded-full border-2 flex items-center justify-center
          transition-all duration-200 shrink-0"
          :class="isSelected
            ? isAttendanceMode
              ? 'bg-red-500 border-red-500'
              : 'bg-[#027eb5] border-[#027eb5]'
            : 'border-gray-300'"
        >
          <CheckIcon v-if="isSelected" class="w-3 h-3 text-white stroke-[3]" />
        </span>
      </div>
    </Transition>

    <!-- Avatar / Thumbnail -->
    <div
      class="w-11 h-11 sm:w-12 sm:h-12 md:w-14 md:h-14 rounded-full bg-[#dfe5e7]
      flex items-center justify-center text-lg sm:text-xl md:text-2xl
      my-2 mx-2 sm:mx-3 shrink-0 cursor-pointer select-none
      touch-manipulation active:scale-95 transition-transform duration-150
      relative"
      :ref="itemRef"
      @click="emit('select')"
    >
      {{ getDisplayValue('avatar') }}

      <!-- Online dot -->
      <span
        class="absolute bottom-0.5 right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-400
        border-2 border-white"
      ></span>
    </div>

    <!-- Main content button -->
    <button
      class="flex-1 flex items-center justify-between py-2 sm:py-3 pr-3 sm:pr-4
      bg-transparent border-none cursor-pointer text-left min-w-0
      touch-manipulation min-h-[48px]"
      @click="emit('accordion')"
    >
      <!-- Left: name + subtitle -->
      <div class="flex-1 min-w-0 pr-2">
        <div class="flex items-center gap-1.5 mb-0.5">
          <h3 class="text-sm sm:text-base font-semibold text-gray-900 leading-tight truncate">
            {{ getDisplayValue('title') }}
          </h3>
          <!-- Attendance badge — shows in attendance mode -->
          <span
            v-if="isAttendanceMode"
            class="shrink-0 text-[9px] font-bold px-1.5 py-0.5 rounded-full"
            :class="isSelected
              ? 'bg-red-100 text-red-600'
              : 'bg-emerald-100 text-emerald-600'"
          >
            {{ isSelected ? 'Absent' : 'Present' }}
          </span>
        </div>
        <p class="text-xs sm:text-sm text-[#667781] whitespace-nowrap overflow-hidden text-ellipsis leading-tight">
          {{ getDisplayValue('subtitle') }}
        </p>
      </div>

      <!-- Right: timestamp + chevron -->
      <div class="flex flex-col items-end gap-1 shrink-0">
        <span class="text-[10px] sm:text-xs text-[#667781] whitespace-nowrap">
          {{ getDisplayValue('timestamp') }}
        </span>
        <ChevronRightIcon
          class="w-4 h-4 text-[#667781] transition-transform duration-300 ease-out"
          :class="isOpen ? 'rotate-90' : ''"
        />
      </div>
    </button>

  </div>
</template>
