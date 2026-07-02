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
  <!-- Contact Header -->
  <div class="flex items-center gap-3 px-4 py-3 bg-[#f0f2f5] border-b border-[#e9edef] shrink-0">
    <div class="w-10 h-10 rounded-full bg-[#dfe5e7] flex items-center justify-center text-xl shrink-0">
      {{ item.avatar }}
    </div>
    <div class="flex-1 min-w-0">
      <h3 class="text-base font-medium text-black">{{ item.name }}</h3>
      <p class="text-xs text-[#667781]">{{ item.lastSeen }}</p>
    </div>
    <button 
      @click="close"
      class="text-[#667781] hover:text-black transition-colors p-2"
    >
      ✕
    </button>
  </div>

  <!-- Messages Area -->
  <div class="flex-1 overflow-y-auto p-4 space-y-2">
    <div
      v-for="message in item.messages"
      :key="message.id"
      class="flex animate-fadeIn"
      :class="message.sent ? 'justify-end' : 'justify-start'"
    >
      <div
        class="max-w-[70%] px-3 py-2 rounded-lg shadow-sm"
        :class="message.sent 
          ? 'bg-[#dcf8c6] text-black rounded-br-none' 
          : 'bg-white text-black rounded-bl-none'"
      >
        <p class="m-0 text-sm leading-relaxed break-words">{{ message.text }}</p>
        <span class="text-[10px] text-[#667781] float-right ml-2 mt-1">
          {{ message.time }}
        </span>
      </div>
    </div>
  </div>

  <!-- Message Input -->
  <div class="flex gap-2 p-4 border-t border-[#d1d7db] bg-[#f0f2f5] shrink-0">
    <input
      type="text"
      placeholder="Type a message..."
      class="flex-1 px-4 py-3 border border-[#d1d7db] rounded-full text-sm focus:outline-none focus:border-[#075e54] bg-white"
    />
    <button class="w-12 h-12 shrink-0 bg-[#075e54] text-white rounded-full flex items-center justify-center hover:bg-[#064e47] transition-colors">
      ➤
    </button>
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