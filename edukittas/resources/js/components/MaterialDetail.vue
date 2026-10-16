<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  item: { type: Object, default: null },
})

const emit = defineEmits(['restock'])

const qty = ref(1)

// reset the input whenever a different material is selected
watch(() => props.item?.id, () => { qty.value = 1 })

function submit() {
  if (!qty.value || qty.value < 1) return
  emit('restock', { material: props.item, qty: Number(qty.value) })
  qty.value = 1
}
</script>

<template>
  <div v-if="item" class="flex flex-col h-full p-4 sm:p-6 gap-6 overflow-y-auto">

    <!-- Material header -->
    <div class="flex items-center gap-3">
      <div
        class="w-12 h-12 rounded-full flex items-center justify-center text-2xl shrink-0"
        :class="item.stock <= item.threshold ? 'bg-danger/15' : 'bg-primary/10'"
      >
        {{ item.stock <= item.threshold ? '⚠️' : '📦' }}
      </div>
      <div>
        <h2 class="text-lg sm:text-xl font-semibold text-neutral-900 m-0">{{ item.name }}</h2>
        <p class="text-sm text-neutral-500 m-0">
          <span :class="item.stock <= item.threshold ? 'text-danger font-semibold' : ''">
            {{ item.stock }} {{ item.unit }}
          </span>
          in stock
          <span v-if="item.stock <= item.threshold" class="text-danger font-medium"> · Low stock</span>
        </p>
      </div>
    </div>

    <!-- Restock history (was m.log in the original modal, now just shown inline) -->
    <div v-if="item.log?.length" class="flex-1 min-h-0 overflow-y-auto">
      <h3 class="text-sm font-semibold text-neutral-700 mb-2">Restock History</h3>
      <ul class="flex flex-col gap-1.5">
        <li
          v-for="(entry, i) in [...item.log].reverse()"
          :key="i"
          class="flex justify-between text-sm text-neutral-600 border-b border-neutral-200 pb-1.5"
        >
          <span>{{ entry.date }}</span>
          <span class="font-medium text-neutral-900">+{{ entry.qty }} {{ item.unit }}</span>
        </li>
      </ul>
    </div>
    <div v-else class="flex-1 flex items-center justify-center text-sm text-neutral-400">
      No restock history yet
    </div>

    <!-- Restock form (was the modal body/foot) -->
    <div class="border-t border-neutral-200 pt-4 flex flex-col gap-2 shrink-0">
      <label class="text-sm font-medium text-neutral-700">Add Quantity</label>
      <div class="flex gap-2">
        <input
          v-model.number="qty"
          type="number"
          min="1"
          placeholder="e.g. 24"
          class="flex-1 border border-neutral-300 rounded-lg px-3 py-2 text-sm"
          @keyup.enter="submit"
        />
        <button
          @click="submit"
          class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium
          hover:bg-primary/90 transition-colors shrink-0"
        >
          + Add Stock
        </button>
      </div>
    </div>

  </div>

  <div v-else class="flex-1 flex items-center justify-center text-neutral-400 text-sm">
    Select a material to view details
  </div>
</template>