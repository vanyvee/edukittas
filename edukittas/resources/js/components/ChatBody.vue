<script setup>
const props = defineProps({
  item: {
    type: Object,
    required: true
  },
  close: {
    type: Function,
    required: true
  }
})
</script>

<template>
  <div class="overflow-hidden bg-[#efeae2]">
    <div class="p-3 sm:p-4 space-y-2 max-h-[50vh] sm:max-h-[60vh] overflow-y-auto">
      <!-- Chat Messages -->
      <div
        v-for="message in item.messages"
        :key="message.id"
        class="flex animate-fadeIn"
        :class="message.sent ? 'justify-end' : 'justify-start'"
      >
        <div
          class="max-w-[80%] sm:max-w-[75%] md:max-w-[70%] px-2.5 py-1.5 sm:px-3 sm:py-2 rounded-lg shadow-sm"
          :class="message.sent 
            ? 'bg-[#dcf8c6] text-black rounded-br-none' 
            : 'bg-white text-black rounded-bl-none'"
        >
          <p class="m-0 text-xs sm:text-sm md:text-base leading-relaxed break-words">{{ message.text }}</p>
          <span class="text-[9px] sm:text-[10px] text-[#667781] float-right ml-2 mt-1">
            {{ message.time }}
          </span>
        </div>
      </div>

      <!-- Message Input -->
      <div class="flex gap-2 pt-2 sm:pt-3 mt-3 sm:mt-4 border-t border-[#d1d7db] sticky bottom-0 bg-[#efeae2]">
        <input
          type="text"
          placeholder="Type a message..."
          class="flex-1 px-3 py-2 sm:px-4 sm:py-2.5 md:px-5 md:py-3 border border-[#d1d7db] rounded-full text-xs sm:text-sm md:text-base focus:outline-none focus:border-[#075e54] bg-white touch-manipulation min-h-[44px]"
        />
        <button class="w-11 h-11 sm:w-12 sm:h-12 shrink-0 bg-[#075e54] text-white rounded-full flex items-center justify-center active:bg-[#064e47] transition-colors touch-manipulation active:scale-95 text-base sm:text-lg">
          ➤ 
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fadeIn {
  animation: fadeIn 0.3s ease-out;
}
</style>