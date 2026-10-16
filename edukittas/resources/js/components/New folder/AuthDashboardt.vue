<script setup>
import { ref, nextTick } from 'vue'
import { gsap } from 'gsap'

// ── State ──────────────────────────────────────────
const activeDetail = ref(null)   // null | 'spotlight' | 'analytics' | 'news'
const dashboardEl  = ref(null)
const detailEl     = ref(null)

// ── Mock Data ──────────────────────────────────────
const topStudents = [
  { name: 'Amara Okafor',  cls: 'SS3 Science', rank: 1, score: 98, avatar: '👸' },
  { name: 'David Nwosu',   cls: 'SS2 Arts',    rank: 2, score: 95, avatar: '👦' },
  { name: 'Fatima Bello',  cls: 'SS3 Science', rank: 3, score: 93, avatar: '👩' },
  { name: 'Emeka Eze',     cls: 'JSS3 Basic',  rank: 4, score: 91, avatar: '🧑' },
]

const analytics = {
  enrollment:  { total: 1240, boys: 680, girls: 560 },
  attendance:  { avg: 87, present: 1079, absent: 161 },
  performance: { a: 34, b: 41, c: 18, d: 7 },
  subjects:    [
    { name: 'Mathematics', avg: 72 },
    { name: 'English',     avg: 81 },
    { name: 'Physics',     avg: 68 },
    { name: 'Chemistry',   avg: 74 },
    { name: 'Biology',     avg: 79 },
    { name: 'Economics',   avg: 83 },
  ],
  terms: [
    { term: '1st',  score: 71 },
    { term: '2nd',  score: 76 },
    { term: '3rd',  score: 82 },
  ]
}

const news = [
  {
    id: 1,
    tag: 'Announcement',
    tagColor: 'bg-blue-100 text-blue-700',
    title: 'Third Term Examination Timetable Released',
    summary: 'The academic board has approved the final exam schedule. All students should collect their timetable from the admin office.',
    date: 'Today, 9:00 AM',
    icon: '📋',
  },
  {
    id: 2,
    tag: 'Achievement',
    tagColor: 'bg-emerald-100 text-emerald-700',
    title: 'School Wins State Science Olympiad',
    summary: 'Our SS3 team claimed first place at the Rivers State Science Olympiad held in Port Harcourt last weekend.',
    date: 'Yesterday',
    icon: '🏆',
  },
  {
    id: 3,
    tag: 'Event',
    tagColor: 'bg-amber-100 text-amber-700',
    title: 'Inter-House Sports Day — Saturday 12th April',
    summary: 'All students are expected to participate. House captains should submit their team lists by Wednesday.',
    date: '2 days ago',
    icon: '⚽',
  },
  {
    id: 4,
    tag: 'Notice',
    tagColor: 'bg-rose-100 text-rose-700',
    title: 'School Fees Deadline — End of April',
    summary: 'Parents are reminded that outstanding school fees must be cleared before the third term exams commence.',
    date: '3 days ago',
    icon: '💰',
  },
  {
    id: 5,
    tag: 'Academic',
    tagColor: 'bg-purple-100 text-purple-700',
    title: 'New Computer Lab Inaugurated',
    summary: '30 brand new desktops with fibre internet access have been installed. Timetabling for lab sessions starts next week.',
    date: '4 days ago',
    icon: '💻',
  },
]

// ── Detail panel transition ────────────────────────
const openDetail = async (section) => {
  if (dashboardEl.value) {
    await gsap.to(dashboardEl.value, {
      opacity: 0, scale: 0.97, duration: 0.22, ease: 'power2.in'
    })
  }
  activeDetail.value = section
  await nextTick()
  if (detailEl.value) {
    gsap.fromTo(detailEl.value,
      { opacity: 0, y: 16 },
      { opacity: 1, y: 0, duration: 0.3, ease: 'power3.out' }
    )
  }
}

const closeDetail = async () => {
  if (detailEl.value) {
    await gsap.to(detailEl.value, {
      opacity: 0, y: 12, duration: 0.2, ease: 'power2.in'
    })
  }
  activeDetail.value = null
  await nextTick()
  if (dashboardEl.value) {
    gsap.fromTo(dashboardEl.value,
      { opacity: 0, scale: 0.97 },
      { opacity: 1, scale: 1, duration: 0.3, ease: 'power3.out' }
    )
  }
}
</script>

<template>
  <div class="relative w-full h-full overflow-hidden bg-[#f4f6f8]" style="font-family: 'DM Sans', sans-serif">

    <!-- ══════════════════════════════════════
         MAIN DASHBOARD — three columns
    ══════════════════════════════════════ -->
    <div
      v-show="!activeDetail"
      ref="dashboardEl"
      class="w-full h-full flex gap-2 p-2 pt-0 pb-0 pl-1 overflow-hidden"
    >

      <!-- ── CARD 1: School Spotlight (35%) ─────────────── -->
      <div class="flex flex-col rounded-2xl overflow-hidden shadow-md bg-white shrink-0"
        style="width: 35%">

        <!-- Image area -->
        <div class="relative flex-1 overflow-hidden"
          style="background: linear-gradient(160deg, #075e54 0%, #0d9488 50%, #134e4a 100%)">

          <!-- Decorative blobs -->
          <div class="absolute top-0 right-0 w-48 h-48 rounded-full opacity-10 bg-white"
            style="transform: translate(30%, -30%)"></div>
          <div class="absolute bottom-0 left-0 w-36 h-36 rounded-full opacity-10 bg-white"
            style="transform: translate(-30%, 30%)"></div>

          <!-- Content -->
          <div class="relative z-10 flex flex-col items-center justify-center h-full p-6 text-center">
            <div class="text-7xl mb-3">🏫</div>
            <h2 class="text-white text-lg font-bold leading-tight">Greenfield</h2>
            <p class="text-white/60 text-xs mt-1">Secondary School, Rivers State</p>
            <div class="mt-4 flex gap-2">
              <span class="px-3 py-1 rounded-full text-[10px] font-semibold bg-white/15 text-white border border-white/20">
                Est. 1998
              </span>
              <span class="px-3 py-1 rounded-full text-[10px] font-semibold bg-emerald-400/20 text-emerald-200 border border-emerald-400/30">
                ★ Top Rated
              </span>
            </div>
          </div>

          <!-- Student of the Year badge -->
          <div class="absolute top-3 left-3 flex items-center gap-1.5 px-2.5 py-1 rounded-full
            bg-amber-400/90 backdrop-blur-sm">
            <span class="text-xs">🏅</span>
            <span class="text-[10px] font-bold text-amber-900">Student of the Year</span>
          </div>
        </div>

        <!-- Top 4 students footer -->
        <div class="bg-white px-4 pt-3 pb-2 border-t border-gray-100">
          <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-2">
            Top Students
          </p>
          <div class="flex gap-2">
            <div
              v-for="student in topStudents"
              :key="student.rank"
              class="flex-1 flex flex-col items-center text-center"
            >
              <div class="relative">
                <div class="w-9 h-9 rounded-full bg-[#efeae2] flex items-center justify-center text-lg
                  border-2 border-white shadow-sm">
                  {{ student.avatar }}
                </div>
                <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-[#075e54] text-white
                  text-[8px] font-bold flex items-center justify-center">
                  {{ student.rank }}
                </span>
              </div>
              <p class="text-[9px] font-semibold text-gray-700 mt-1 leading-tight">
                {{ student.name.split(' ')[0] }}
              </p>
              <p class="text-[8px] text-gray-400">{{ student.cls }}</p>
            </div>
          </div>

          <!-- View Detail -->
          <div class="flex justify-end mt-3 pb-1">
            <button @click="openDetail('spotlight')"
              class="flex items-center gap-1 text-[10px] font-semibold text-[#075e54]
              hover:text-[#064840] transition-colors">
              View Details
              <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- ── CARD 2: Analytics (40%) ─────────────────────── -->
      <div class="flex flex-col rounded-2xl overflow-hidden shadow-md bg-white"
        style="width: 40%">

        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50 shrink-0">
          <div>
            <h3 class="text-sm font-bold text-gray-800">School Analytics</h3>
            <p class="text-[10px] text-gray-400 mt-0.5">2024/25 Academic Session · 2nd Term</p>
          </div>
          <button @click="openDetail('analytics')"
            class="flex items-center gap-1 text-[10px] font-semibold text-[#075e54] hover:text-[#064840] transition-colors">
            View Details
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-4">

          <!-- Stat chips -->
          <div class="grid grid-cols-3 gap-2">
            <div class="rounded-xl p-3 text-center" style="background:#f0fdf4">
              <p class="text-lg font-bold text-[#075e54]">{{ analytics.enrollment.total }}</p>
              <p class="text-[9px] text-gray-400 mt-0.5 uppercase tracking-wide">Enrolled</p>
            </div>
            <div class="rounded-xl p-3 text-center" style="background:#eff6ff">
              <p class="text-lg font-bold text-blue-600">{{ analytics.attendance.avg }}%</p>
              <p class="text-[9px] text-gray-400 mt-0.5 uppercase tracking-wide">Attendance</p>
            </div>
            <div class="rounded-xl p-3 text-center" style="background:#fefce8">
              <p class="text-lg font-bold text-amber-500">{{ analytics.terms[2].score }}%</p>
              <p class="text-[9px] text-gray-400 mt-0.5 uppercase tracking-wide">Avg Score</p>
            </div>
          </div>

          <!-- Subject performance bars -->
          <div>
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-2">
              Subject Averages
            </p>
            <div class="space-y-2">
              <div v-for="subject in analytics.subjects" :key="subject.name">
                <div class="flex items-center justify-between mb-0.5">
                  <span class="text-[10px] text-gray-600">{{ subject.name }}</span>
                  <span class="text-[10px] font-semibold text-[#075e54]">{{ subject.avg }}%</span>
                </div>
                <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden">
                  <div class="h-full rounded-full transition-all duration-700"
                    style="background: linear-gradient(90deg, #075e54, #25d366)"
                    :style="{ width: subject.avg + '%' }">
                  </div>
                </div>
              </div>
            </div>

            
          </div>

          <!-- Grade distribution -->
          <div>
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-2">
              Grade Distribution
            </p>
            <div class="flex items-end gap-2 h-16">
              <div v-for="(val, grade) in { A: analytics.performance.a, B: analytics.performance.b, C: analytics.performance.c, D: analytics.performance.d }"
                :key="grade"
                class="flex-1 flex flex-col items-center gap-1"
              >
                <span class="text-[9px] font-bold text-gray-500">{{ val }}%</span>
                <div class="w-full rounded-t-md transition-all duration-700"
                  :style="{
                    height: val + '%',
                    background: grade === 'A' ? '#075e54' : grade === 'B' ? '#25d366' : grade === 'C' ? '#fbbf24' : '#f87171'
                  }">
                </div>
                <span class="text-[9px] font-semibold text-gray-400">{{ grade }}</span>
              </div>
            </div>
            
          </div>

          <!-- Term trend -->
          <div>
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-2">
              Term Performance Trend
            </p>
            <div class="flex items-end gap-3 h-12">
              <div v-for="t in analytics.terms" :key="t.term"
                class="flex-1 flex flex-col items-center gap-1">
                <span class="text-[9px] font-bold text-[#075e54]">{{ t.score }}%</span>
                <div class="w-full rounded-t-lg"
                  style="background: linear-gradient(to top, #075e54, #25d366)"
                  :style="{ height: (t.score / 100 * 36) + 'px' }">
                </div>
                <span class="text-[9px] text-gray-400">{{ t.term }}</span>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- ── CARD 3: News & Announcements (remaining) ──── -->
      <div class="flex flex-col rounded-2xl overflow-hidden shadow-md bg-white flex-1">

        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50 shrink-0">
          <div>
            <h3 class="text-sm font-bold text-gray-800">News & Announcements</h3>
            <p class="text-[10px] text-gray-400 mt-0.5">Latest from the school board</p>
          </div>
          <button @click="openDetail('news')"
            class="flex items-center gap-1 text-[10px] font-semibold text-[#075e54] hover:text-[#064840] transition-colors">
            View All
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
        </div>

        <!-- News list -->
        <div class="flex-1 overflow-y-auto divide-y divide-gray-50">
          <div
            v-for="item in news"
            :key="item.id"
            class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 transition-colors cursor-default"
          >
            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-lg shrink-0 bg-gray-50">
              {{ item.icon }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-1.5 mb-0.5">
                <span class="text-[9px] font-semibold px-1.5 py-0.5 rounded-full" :class="item.tagColor">
                  {{ item.tag }}
                </span>
                <span class="text-[9px] text-gray-300">·</span>
                <span class="text-[9px] text-gray-400">{{ item.date }}</span>
              </div>
              <p class="text-xs font-semibold text-gray-800 leading-tight">{{ item.title }}</p>
              <p class="text-[10px] text-gray-400 mt-0.5 leading-relaxed line-clamp-2">{{ item.summary }}</p>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- ══════════════════════════════════════
         DETAIL PANEL — replaces dashboard
    ══════════════════════════════════════ -->
    <div
      v-if="activeDetail"
      ref="detailEl"
      class="w-full h-full overflow-y-auto p-6"
    >

      <!-- ── Spotlight Detail ── -->
      <div v-if="activeDetail === 'spotlight'">
        <h2 class="text-xl font-bold text-gray-800 mb-1">School Spotlight</h2>
        <p class="text-xs text-gray-400 mb-6">Top performing students · 2024/25 Session</p>
        <div class="grid grid-cols-2 gap-4">
          <div v-for="student in topStudents" :key="student.rank"
            class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="relative shrink-0">
              <div class="w-14 h-14 rounded-full bg-[#efeae2] flex items-center justify-center text-3xl">
                {{ student.avatar }}
              </div>
              <span class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-[#075e54] text-white
                text-[9px] font-bold flex items-center justify-center">
                {{ student.rank }}
              </span>
            </div>
            <div>
              <p class="font-bold text-gray-800 text-sm">{{ student.name }}</p>
              <p class="text-xs text-gray-400 mt-0.5">{{ student.cls }}</p>
              <div class="mt-2 flex items-center gap-1.5">
                <div class="h-1.5 w-20 rounded-full bg-gray-100 overflow-hidden">
                  <div class="h-full rounded-full bg-[#075e54]" :style="{ width: student.score + '%' }"></div>
                </div>
                <span class="text-[10px] font-bold text-[#075e54]">{{ student.score }}%</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Analytics Detail ── -->
      <div v-if="activeDetail === 'analytics'">
        <h2 class="text-xl font-bold text-gray-800 mb-1">School Analytics</h2>
        <p class="text-xs text-gray-400 mb-6">Full breakdown · 2024/25 Session</p>
        <div class="grid grid-cols-3 gap-4 mb-6">
          <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 text-center">
            <p class="text-3xl font-bold text-[#075e54]">{{ analytics.enrollment.total }}</p>
            <p class="text-xs text-gray-400 mt-1">Total Enrolled</p>
            <div class="flex justify-center gap-3 mt-3">
              <span class="text-[10px] text-blue-500 font-medium">👦 {{ analytics.enrollment.boys }} Boys</span>
              <span class="text-[10px] text-pink-500 font-medium">👧 {{ analytics.enrollment.girls }} Girls</span>
            </div>
          </div>
          <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 text-center">
            <p class="text-3xl font-bold text-emerald-600">{{ analytics.attendance.avg }}%</p>
            <p class="text-xs text-gray-400 mt-1">Avg Attendance</p>
            <div class="flex justify-center gap-3 mt-3">
              <span class="text-[10px] text-emerald-500 font-medium">✓ {{ analytics.attendance.present }} Present</span>
              <span class="text-[10px] text-rose-500 font-medium">✗ {{ analytics.attendance.absent }} Absent</span>
            </div>
          </div>
          <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 text-center">
            <p class="text-3xl font-bold text-amber-500">{{ analytics.terms[2].score }}%</p>
            <p class="text-xs text-gray-400 mt-1">Current Term Avg</p>
            <div class="flex justify-center gap-1 mt-3">
              <span v-for="t in analytics.terms" :key="t.term"
                class="text-[10px] text-gray-400 font-medium">
                {{ t.term }}: {{ t.score }}%
                <span v-if="t.term !== '3rd'" class="mx-1 text-gray-200">·</span>
              </span>
            </div>
          </div>
        </div>
        <!-- Full subject bars -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
          <p class="text-xs font-semibold text-gray-500 mb-4">All Subject Averages</p>
          <div class="space-y-3">
            <div v-for="subject in analytics.subjects" :key="subject.name">
              <div class="flex items-center justify-between mb-1">
                <span class="text-xs text-gray-600 font-medium">{{ subject.name }}</span>
                <span class="text-xs font-bold text-[#075e54]">{{ subject.avg }}%</span>
              </div>
              <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                <div class="h-full rounded-full transition-all duration-700"
                  style="background: linear-gradient(90deg, #075e54, #25d366)"
                  :style="{ width: subject.avg + '%' }"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── News Detail ── -->
      <div v-if="activeDetail === 'news'">
        <h2 class="text-xl font-bold text-gray-800 mb-1">News & Announcements</h2>
        <p class="text-xs text-gray-400 mb-6">All school updates and notices</p>
        <div class="space-y-3">
          <div v-for="item in news" :key="item.id"
            class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex gap-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl shrink-0 bg-gray-50">
              {{ item.icon }}
            </div>
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-1">
                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full" :class="item.tagColor">
                  {{ item.tag }}
                </span>
                <span class="text-[10px] text-gray-400">{{ item.date }}</span>
              </div>
              <p class="text-sm font-bold text-gray-800 mb-1">{{ item.title }}</p>
              <p class="text-xs text-gray-500 leading-relaxed">{{ item.summary }}</p>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- ══════════════════════════════════════
         FLOATING CLOSE BUTTON
    ══════════════════════════════════════ -->
    <Transition name="pop">
      <button
        v-if="activeDetail"
        @click="closeDetail"
        class="absolute bottom-6 right-6 w-11 h-11 rounded-full shadow-xl flex items-center
        justify-center text-white text-lg transition-transform hover:scale-110 active:scale-95 z-30"
        style="background: linear-gradient(135deg, #075e54, #25d366)"
      >
        ✕
      </button>
    </Transition>

  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap');

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Scrollbar */
.overflow-y-auto::-webkit-scrollbar { width: 3px; }
.overflow-y-auto::-webkit-scrollbar-track { background: transparent; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #c4c4c4; border-radius: 2px; }

/* Close button pop transition */
.pop-enter-active { transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
.pop-leave-active { transition: all 0.2s ease-in; }
.pop-enter-from, .pop-leave-to { opacity: 0; transform: scale(0.5); }
</style>
