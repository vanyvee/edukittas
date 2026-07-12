<script setup>
import { ref } from 'vue'
import MyAccordion from './MyAccordion.vue'
import MaterialDetail from './MaterialDetail.vue'

// Replace with your real fetch, e.g. GET /staff/materials
// const rawMaterials = ref([
//   // { id, name, unit, stock, threshold, log: [{date, qty}] }
// ])

const rawMaterials = ref([
  {
    id: 1,
    name: 'Rice',
    unit: 'kg',
    stock: 45,
    threshold: 20,
    log: [
      { date: '2026-07-01', qty: 20 },
      { date: '2026-07-05', qty: 25 }
    ]
  },
  {
    id: 2,
    name: 'Chicken',
    unit: 'kg',
    stock: 12,
    threshold: 15,
    log: [
      { date: '2026-07-03', qty: 10 }
    ]
  },
  {
    id: 3,
    name: 'Groundnut Oil',
    unit: 'Litres',
    stock: 30,
    threshold: 10,
    log: [
      { date: '2026-07-02', qty: 15 },
      { date: '2026-07-07', qty: 15 }
    ]
  },
  {
    id: 4,
    name: 'Tomatoes',
    unit: 'Crates',
    stock: 4,
    threshold: 8,
    log: [
      { date: '2026-07-06', qty: 4 }
    ]
  },
  {
    id: 5,
    name: 'Onions',
    unit: 'Bags',
    stock: 18,
    threshold: 5,
    log: [
      { date: '2026-07-04', qty: 8 },
      { date: '2026-07-08', qty: 10 }
    ]
  }
])
// Left-list rows: name + total in stock, exactly as you asked
const displayConfig = {
  avatar:    (m) => (m.stock <= m.threshold ? '⚠️' : '📦'),
  title:     'name',
  subtitle:  (m) => `${m.stock} ${m.unit} in stock`,
  timestamp: (m) => (m.stock <= m.threshold ? 'Low stock' : ''),
}

function handleRestock({ material, qty }) {
  // TODO: swap for a real API call, e.g.
  // await axios.post(`/staff/materials/${material.id}/restock`, { qty })
  material.stock += qty
  if (!material.log) material.log = []
  material.log.push({ date: new Date().toLocaleDateString(), qty })
}
</script>

<template>
  <MyAccordion
    :items="rawMaterials"
    :display-config="displayConfig"
    key-field="id"
    :tabs="[{ key: 'details', label: 'Details' }]"
  >
    <!-- Custom header: no select-all / attendance / full-view — materials don't need them -->
    
      <!-- <div class="flex items-center justify-between px-3 py-2 sm:px-4 sm:py-3 bg-primary h-[56px] sm:h-[64px]">
        <span class="text-white font-medium">Raw Materials</span>
        <span class="text-white/70 text-xs">Live stock levels</span>
      </div>-->

<template #nav-header="{ store, allSelected, toggleSelectAll, selectedItem }">
  <div class="flex items-center justify-between gap-2 px-3 py-2 sm:px-4 sm:py-3 bg-primary h-[56px] sm:h-[64px]">

    <!-- Select all (kept as-is) -->
    <button
      v-if="store.selectionMode"
      @click="toggleSelectAll"
      class="flex items-center gap-2 text-white text-sm px-2 py-1 rounded"
    >
      <span
        class="w-5 h-5 border-2 border-white rounded-full flex items-center justify-center"
        :class="allSelected ? 'bg-white text-primary' : ''"
      >
        <CheckIcon v-if="allSelected" class="w-3 h-3 stroke-[3]" />
      </span>
      <span class="hidden sm:inline text-white/90 text-xs">{{ allSelected ? 'None' : 'All' }}</span>
    </button>

    <!-- Quick-add: small input + OK, tied directly to store -->
    <div class="flex items-center gap-1 flex-1 max-w-[160px] sm:max-w-[200px]">
      <input
        v-model="newEntryValue"
        @keyup.enter="submitNewEntry(store)"
        type="text"
        placeholder="New entry..."
        class="w-full px-2 py-1.5 rounded-md text-xs sm:text-sm border-none bg-white/20
        text-white placeholder-white/60 focus:bg-white/30 focus:outline-none"
      />
      <button
        @click="submitNewEntry(store)"
        class="shrink-0 px-2.5 py-1.5 rounded-md text-xs sm:text-sm font-medium
        bg-white/20 text-white hover:bg-white/30 transition-colors"
      >
        OK
      </button>
    </div>

    <!-- Delete — only once a tab has been clicked -->
    <button
      v-if="tabClicked"
      @click="store.deleteSelected()"
      class="px-2.5 py-1.5 rounded-lg text-xs sm:text-sm font-medium
      bg-danger text-white hover:bg-danger/90 transition-colors"
    >
      Delete
    </button>

  </div>      
    </template> 

    <!-- Mobile: expands inline under the tapped row -->
    <template #accordion-body="{ item }">
      <MaterialDetail :item="item" @restock="handleRestock" />
    </template>

    <!-- Desktop: right panel, single "Details" tab -->
    <template #details="{ item }">
      <MaterialDetail :item="item" @restock="handleRestock" />
    </template>

    <!-- Desktop: covers the case where selecting a 2nd item while one's already
         open flips MyAccordion into "profile" mode instead of updating the tab -->
    <template #profile="{ item }">
      <MaterialDetail :item="item" @restock="handleRestock" />
    </template>
  </MyAccordion>
</template>