<script setup>
import { computed } from 'vue'
const props = defineProps({
  item: {
    type: Object,
    required: true,
    default: ()=>({}),
    // Expected shape:
    // {
    //   name: 'John Doe',
    //   avatar: '👤',
    //   studentId: 'STU/2024/0042',
    //   class: 'JSS 3',
    //   stream: 'Science',
    //   gender: 'Male',
    //   dob: '14 Mar 2010',
    //   guardian: 'Mr. James Doe',
    //   phone: '+234 801 234 5678',
    //   attendance: 87,
    //   gpa: 3.8,
    //   subjects: 12,
    //   performance: 78,
    //   term: '2nd Term · 2024/25',
    //   status: 'Active'
    // }
  }
})

 const infoRows = computed(() => [
      { label: 'Gender',       value: props.item?.gender },
      { label: 'Date of Birth', value: props.item?.dob },
      { label: 'Guardian',     value: props.item?.guardian },
      { label: 'Phone',        value: props.item?.phone },
    ])


</script>

<template>
  <div class="w-full bg-white rounded-tl-2xl overflow-hidden shadow-xl font-sans">

    <!-- SECTION 1: Header -->
    <div class="relative bg-[#075e54] px-5 pt-6 pb-14">

      <!-- top row -->
      <div class="flex items-center justify-between mb-4">
        <span class="text-[10px] font-semibold tracking-widest text-white/50 uppercase">
          Student Profile
        </span>
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-semibold
          bg-white/15 text-white border border-white/20">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 inline-block"></span>
          {{ item?.status ?? 'Active' }}
        </span>
      </div>

      <!-- avatar + name -->
      <div class="flex items-center gap-4">

        <!-- avatar with conic ring showing attendance -->
        <div class="shrink-0" :style="{
          background: `conic-gradient(#25d366 0% ${item?.attendance ?? 0}%, rgba(255,255,255,0.15) ${item?.attendance ?? 0}% 100%)`,
          padding: '3px',
          borderRadius: '50%'
        }">
          <div class="bg-[#075e54] p-0.5 rounded-full">
            <div class="w-16 h-16 rounded-full bg-white/10 border border-white/20
              flex items-center justify-center text-3xl">
              {{ item?.avatar ?? '👤' }}
            </div>
          </div>
        </div>

        <div>
          <h2 class="text-white text-xl font-bold leading-tight tracking-tight">
            {{ item?.name }}
          </h2>
          <p class="text-white/60 text-xs mt-0.5">
            {{ item?.class }} · {{ item?.stream }}
          </p>
          <p class="text-white/35 text-[10px] mt-0.5 font-mono">
            {{ item?.studentId }}
          </p>
        </div>
      </div>

      <!-- decorative circles -->
      <div class="absolute -bottom-8 -right-8 w-32 h-32 rounded-full bg-white/5 pointer-events-none"></div>
      <div class="absolute -bottom-12 -right-2 w-20 h-20 rounded-full bg-white/5 pointer-events-none"></div>
    </div>

    <!-- Floating stat strip -->
    <div class="mx-4 -mt-7 relative z-10 bg-white rounded-xl shadow-md border border-gray-100
      px-4 py-3 flex items-center justify-between">

      <div class="text-center">
        <p class="text-[#075e54] text-lg font-bold leading-none">{{ item?.attendance }}%</p>
        <p class="text-[10px] text-gray-400 mt-1">Attendance</p>
      </div>

      <div class="w-px h-8 bg-gray-100"></div>

      <div class="text-center">
        <p class="text-[#075e54] text-lg font-bold leading-none">{{ item?.gpa }}</p>
        <p class="text-[10px] text-gray-400 mt-1">GPA</p>
      </div>

      <div class="w-px h-8 bg-gray-100"></div>

      <div class="text-center">
        <p class="text-[#075e54] text-lg font-bold leading-none">{{ item?.subjects }}</p>
        <p class="text-[10px] text-gray-400 mt-1">Subjects</p>
      </div>

    </div>

    <!-- SECTION 2: Details -->
    <div class="px-5 pt-5 pb-6 space-y-3">

      <!-- Info rows -->
      <div class="space-y-0">
        <div v-for="row in infoRows" :key="row.label"
          class="flex items-center justify-between py-2.5 border-b border-gray-50">
          <span class="text-[11px] text-gray-400 uppercase tracking-wider font-medium">
            {{ row.label }}
          </span>
          <span class="text-sm text-gray-700 font-medium">{{ row.value }}</span>
        </div>
      </div>

      <!-- Performance bar -->
      <div class="pt-2">
        <div class="flex items-center justify-between mb-2">
          <span class="text-[11px] text-gray-400 uppercase tracking-wider font-medium">
            Overall Performance
          </span>
          <span class="text-xs font-semibold text-[#075e54]">{{ item?.performance }}%</span>
        </div>
        <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden">
          <div
            class="h-full rounded-full transition-all duration-1000"
            style="background: linear-gradient(90deg, #075e54, #25d366)"
            :style="{ width: item?.performance + '%' }"
          ></div>
        </div>
      </div>

      <!-- Term badge -->
      <div class="flex items-center justify-between pt-1">
        <span class="text-[11px] text-gray-400 uppercase tracking-wider font-medium">
          Current Term
        </span>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-semibold
          bg-emerald-50 text-emerald-700 border border-emerald-100">
          {{ item?.term }}
        </span>
      </div>

    </div>
  </div>
</template>


<style scoped>
/* @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap');
* { font-family: 'DM Sans', sans-serif; } */
</style>
