<script setup>
import { CheckIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  item:             { type: Object,   required: true },
  isOpen:           { type: Boolean,  default: false },
  isSelected:       { type: Boolean,  default: false },
  isSelectionMode:  { type: Boolean,  default: false },
  getDisplayValue:  { type: Function, required: true },

  itemRef:          { type: Function, required: true },
})

const emit = defineEmits(['accordion', 'select', 'checkbox'])
</script>

<template>
  <div class="flex items-center hover:bg-[#f5f6f6] transition-colors">

    <!-- Checkbox -->
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
        class="px-2 sm:px-3 flex items-center cursor-pointer touch-manipulation min-h-[48px]"
        @click="emit('checkbox')"
      >
        <span
          class="w-5 h-5 sm:w-6 sm:h-6 border-2 border-[#027eb5] rounded-full flex items-center
          justify-center text-xs sm:text-sm text-white transition-all"
          :class="isSelected ? 'bg-[#027eb5]' : ''"
        >
          <CheckIcon v-if="isSelected" class="w-3 h-3 sm:h-4 stroke-[3]" />
        </span>
      </div>
    </Transition>

    <!-- Thumbnail -->
    <div
      class="w-11 h-11 sm:w-12 sm:h-12 md:w-14 md:h-14 rounded-full bg-[#dfe5e7]
      flex items-center justify-center text-lg sm:text-xl md:text-2xl my-2 mx-2
      sm:mx-3 shrink-0 cursor-pointer select-none touch-manipulation active:scale-95 transition-transform"
      :ref="itemRef"
      @click="emit('select')"
    >
      {{ getDisplayValue('avatar') }}
    </div>

    <!-- Main Button -->
    <button
      class="flex-1 flex items-center justify-between py-2 sm:py-3 pr-3 sm:pr-4 bg-transparent
      border-none cursor-pointer text-left min-w-0 touch-manipulation min-h-[48px]"
      @click="emit('accordion')"
    >
      <div class="flex-1 min-w-0 pr-2">
        <h3 class="text-sm sm:text-base md:text-lg font-medium m-0 mb-0.5 sm:mb-1 text-black leading-tight">
          {{ getDisplayValue('title') }}
        </h3>
        <p class="text-xs sm:text-sm md:text-base text-[#667781] m-0 whitespace-nowrap overflow-hidden text-ellipsis leading-tight">
          {{ getDisplayValue('subtitle') }}
        </p>
      </div>
      <div class="flex flex-col items-end gap-0.5 sm:gap-1 shrink-0">
        <span class="text-[10px] sm:text-xs md:text-sm text-[#667781] whitespace-nowrap">
          {{ getDisplayValue('timestamp') }}
        </span>
        <ChevronRightIcon
          class="w-4 h-4 sm:w-5 sm:h-5 text-[#667781] transition-transform duration-500 ease-out"
          :class="isOpen ? 'rotate-90' : ''"
        />
      </div>
    </button>

  </div>
</template>
