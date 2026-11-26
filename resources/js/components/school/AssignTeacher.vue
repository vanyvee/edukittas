<script setup>
import { ref, computed, watch } from 'vue'
import { useMainStore } from '@/stores/mainStore'
import { PlusIcon, XMarkIcon, CheckIcon, BookOpenIcon, AcademicCapIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  item: { type: Object, default: null }
})

const store = useMainStore()

// ─── Local State ──────────────────────────────────────────────────────────────
const saving          = ref(false)
const saved           = ref(false)
const selectedClass   = ref(null)
const showClassPicker = ref(false)
const showWarning     = ref(false)

// New assignments being built this session
// shape: [{ classId, className, subjects: [] }]
const assignments = ref([])

// Existing assignments loaded from teacher — for deassign panel
// shape: [{ classId, className, subjects: [] }]
const existingAssignments = ref([])

// Marked for removal — non blocking, just array ops
// shape: [{ classId, className, subject }]
const deassignList = ref([])

// ─── Load when teacher changes ────────────────────────────────────────────────
watch(() => props.item, (teacher) => {
  if (!teacher) return
  assignments.value     = []
  deassignList.value    = []
  selectedClass.value   = null
  showClassPicker.value = false
  showWarning.value     = false
  saved.value           = false
  existingAssignments.value = teacher.assignments
    ? JSON.parse(JSON.stringify(teacher.assignments))
    : []
}, { immediate: true })

// ─── Store data ───────────────────────────────────────────────────────────────
const allClasses  = computed(() => store.classes  ?? mockClasses)
const allSubjects = computed(() => store.subjects ?? mockSubjects)

// ─── Mock data ────────────────────────────────────────────────────────────────
const mockClasses = [
  { id: 'jss1a', name: 'JSS 1A' }, { id: 'jss1b', name: 'JSS 1B' },
  { id: 'jss2a', name: 'JSS 2A' }, { id: 'jss2b', name: 'JSS 2B' },
  { id: 'jss3a', name: 'JSS 3A' }, { id: 'jss3b', name: 'JSS 3B' },
  { id: 'sss1a', name: 'SSS 1A' }, { id: 'sss1b', name: 'SSS 1B' },
  { id: 'sss2a', name: 'SSS 2A' }, { id: 'sss3a', name: 'SSS 3A' },
]

const mockSubjects = [
  'Mathematics', 'English Language', 'Biology', 'Chemistry',
  'Physics', 'Geography', 'History', 'Economics',
  'Literature', 'Agricultural Science', 'Civic Education', 'Computer Science',
]

// ─── Assign computed ──────────────────────────────────────────────────────────
const assignedClassIds = computed(() => assignments.value.map(a => a.classId))

const availableClasses = computed(() =>
  allClasses.value.filter(c => !assignedClassIds.value.includes(c.id))
)

const activeAssignment = computed(() =>
  assignments.value.find(a => a.classId === selectedClass.value)
)

// ─── Deassign computed ────────────────────────────────────────────────────────
const remainingExistingSubjects = computed(() => {
  const removed = new Set(deassignList.value.map(d => `${d.classId}::${d.subject}`))
  return existingAssignments.value.flatMap(a =>
    a.subjects.filter(s => !removed.has(`${a.classId}::${s}`))
  )
})

const isMarkedForRemoval = (classId, subject) =>
  deassignList.value.some(d => d.classId === classId && d.subject === subject)

// ─── Overload check ───────────────────────────────────────────────────────────
const distinctSubjectCount = computed(() => {
  const newSubs  = assignments.value.flatMap(a => a.subjects)
  const combined = new Set([...newSubs, ...remainingExistingSubjects.value])
  return combined.size
})

const isOverloaded = computed(() => distinctSubjectCount.value > 3)

const loadLabel = computed(() => {
  const n = distinctSubjectCount.value
  if (n === 0) return { text: 'No load',    cls: 'text-white/25 border-white/10 bg-white/5' }
  if (n <= 3)  return { text: 'Manageable', cls: 'text-emerald-400 border-emerald-400/25 bg-emerald-400/10' }
  if (n <= 5)  return { text: 'Heavy load', cls: 'text-amber-400 border-amber-400/25 bg-amber-400/10' }
  return             { text: 'Overloaded',  cls: 'text-red-400 border-red-400/25 bg-red-400/10' }
})

// ─── Assign methods ───────────────────────────────────────────────────────────
const addClass = (cls) => {
  assignments.value.push({ classId: cls.id, className: cls.name, subjects: [] })
  selectedClass.value   = cls.id
  showClassPicker.value = false
}

const removeClass = (classId) => {
  assignments.value = assignments.value.filter(a => a.classId !== classId)
  if (selectedClass.value === classId) selectedClass.value = null
}

const toggleSubject = (subject) => {
  if (!activeAssignment.value) return
  const subs = activeAssignment.value.subjects
  const idx  = subs.indexOf(subject)
  idx === -1 ? subs.push(subject) : subs.splice(idx, 1)
}

const hasSubject = (subject) =>
  activeAssignment.value?.subjects.includes(subject) ?? false

// ─── Deassign methods — non blocking ─────────────────────────────────────────
const toggleDeassign = (classId, className, subject) => {
  const idx = deassignList.value.findIndex(
    d => d.classId === classId && d.subject === subject
  )
  idx === -1
    ? deassignList.value.push({ classId, className, subject })
    : deassignList.value.splice(idx, 1)
}

const clearDeassign = () => { deassignList.value = [] }

// ─── Save — blocking sequence ─────────────────────────────────────────────────
const attemptSave = () => {
  if (isOverloaded.value) { showWarning.value = true; return }
  executeSave()
}

const confirmOverload = () => {
  showWarning.value = false
  executeSave()
}

const executeSave = async () => {
  saving.value = true
  try {
    // STEP 1: deassign first — await before moving on
    if (deassignList.value.length > 0) {
      // await api.post('/assignments/deassign', {
      //   teacherId: props.item.id,
      //   removals: deassignList.value
      // })
      await new Promise(r => setTimeout(r, 800))
      deassignList.value = []
    }

    // STEP 2: assign — only runs after deassign resolves
    if (assignments.value.length > 0) {
      // await api.post('/assignments', {
      //   teacherId: props.item.id,
      //   assignments: assignments.value
      // })
      await new Promise(r => setTimeout(r, 800))
      assignments.value.forEach(a => {
        const existing = existingAssignments.value.find(e => e.classId === a.classId)
        existing
          ? existing.subjects.push(...a.subjects.filter(s => !existing.subjects.includes(s)))
          : existingAssignments.value.push({ ...a })
      })
      assignments.value = []
    }

    saved.value = true
    setTimeout(() => saved.value = false, 3000)
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="assignment-panel flex flex-col h-full overflow-hidden relative">

    <!-- ── Empty state ─────────────────────────────────────────── -->
    <div v-if="!item" class="flex-1 flex flex-col items-center justify-center p-8 text-center">
      <div class="empty-orb mb-6">
        <AcademicCapIcon class="w-8 h-8 text-white/60" />
      </div>
      <p class="text-sm font-medium text-white/40 tracking-wide">
        Select a teacher to manage assignments
      </p>
    </div>

    <!-- ── Main panel ──────────────────────────────────────────── -->
    <template v-else>

      <!-- HEADER -->
      <div class="panel-header px-5 pt-5 pb-4 shrink-0">
        <div class="flex items-center gap-3 mb-3">

          <!-- Avatar -->
          <div class="avatar-wrap">
            <div class="avatar-inner">{{ item.avatar ?? '👤' }}</div>
            <div class="avatar-ring"></div>
          </div>

          <!-- Name + meta -->
          <div class="flex-1 min-w-0">
            <h2 class="teacher-name truncate">{{ item.name }}</h2>
            <div class="flex items-center gap-2 mt-0.5 flex-wrap">
              <p class="teacher-meta">
                {{ existingAssignments.length }} {{ existingAssignments.length === 1 ? 'class' : 'classes' }} ·
                {{ distinctSubjectCount }} distinct {{ distinctSubjectCount === 1 ? 'subject' : 'subjects' }}
              </p>
              <!-- Load indicator — Tailwind classes driven by computed -->
              <span
                class="text-[0.6rem] font-bold tracking-widest uppercase px-2 py-0.5 rounded-full border whitespace-nowrap"
                :class="loadLabel.cls"
              >
                {{ loadLabel.text }}
              </span>
            </div>
          </div>

          <!-- Save button -->
          <button
            @click="attemptSave"
            :disabled="saving || (assignments.length === 0 && deassignList.length === 0)"
            class="save-btn"
          >
            <span v-if="saving" class="save-spinner"></span>
            <CheckIcon v-else-if="saved" class="w-4 h-4" />
            <span v-else>Save</span>
          </button>
        </div>

        <!-- Saving progress hint -->
        <Transition name="flash">
          <div
            v-if="saving"
            class="flex items-center gap-2 px-3 py-2 rounded-lg border border-white/8
            bg-white/4 text-white/40 text-[0.72rem] italic"
          >
            <span v-if="deassignList.length > 0">Removing old assignments...</span>
            <span v-else>Saving new assignments...</span>
          </div>
        </Transition>

        <!-- Saved flash -->
        <Transition name="flash">
          <div v-if="saved" class="saved-flash">
            <CheckIcon class="w-3.5 h-3.5" />
            Assignments updated successfully
          </div>
        </Transition>
      </div>

      <!-- OVERLOAD WARNING — absolute overlay -->
      <Transition name="flash">
        <div
          v-if="showWarning"
          class="absolute inset-0 z-50 flex items-center justify-center p-6
          bg-black/60 backdrop-blur-sm"
        >
          <div class="w-full max-w-xs rounded-2xl p-6 bg-[#0f2537] border border-amber-400/30
            shadow-[0_20px_60px_rgba(0,0,0,0.5)]">

            <!-- Icon -->
            <div class="w-11 h-11 rounded-full flex items-center justify-center mb-4
              bg-amber-400/12 border border-amber-400/25">
              <ExclamationTriangleIcon class="w-5 h-5 text-amber-400" />
            </div>

            <!-- Text -->
            <p class="text-white font-bold text-base mb-2">Teacher Overloaded</p>
            <p class="text-white/50 text-[0.78rem] leading-relaxed mb-5">
              This teacher will handle
              <span class="text-amber-400 font-bold">{{ distinctSubjectCount }} distinct subjects</span>
              after this assignment — exceeding the recommended limit of 3.
              Proceeding may affect teaching quality.
            </p>

            <!-- Actions -->
            <div class="flex gap-2">
              <button
                @click="showWarning = false"
                class="flex-1 py-2 px-3 rounded-xl border border-white/12 bg-transparent
                text-white/50 text-[0.78rem] font-semibold cursor-pointer
                hover:border-white/25 hover:text-white transition-all"
              >
                Cancel
              </button>
              <button
                @click="confirmOverload"
                class="flex-1 py-2 px-3 rounded-xl border border-amber-400/40 bg-amber-400/12
                text-amber-400 text-[0.78rem] font-semibold cursor-pointer
                hover:bg-amber-400/20 transition-all"
              >
                Proceed Anyway
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- TWO COLUMN BODY -->
      <div class="flex flex-1 overflow-x-auto overflow-y-hidden md:overflow-x-hidden
       md:divide-x md:divide-white/5 scroll-smooth" style="-webkit-overflow-scrolling: touch;">

        <!-- ══ LEFT: ASSIGN ════════════════════════════════════ -->
        <div class=" w-[85vw] shrink-0 overflow-y-auto px-4 py-4  space-y-4 scrollbar-thin
        md:w-auto md:flex-1">

          <!-- Section label -->
          <div class="flex items-center gap-1.5 text-[0.65rem] font-semibold
            tracking-[0.1em] uppercase text-white/30">
            <PlusIcon class="w-3 h-3" />
            Assign class(es) / subject(s)
          </div>

          <!-- Class pills -->
          <div class="flex flex-wrap gap-2">
            <button
              v-for="a in assignments"
              :key="a.classId"
              @click="selectedClass = a.classId"
              class="class-pill"
              :class="selectedClass === a.classId ? 'class-pill--active' : ''"
            >
              <span class="class-pill-name">{{ a.className }}</span>
              <span class="class-pill-count">{{ a.subjects.length }}</span>
              <span class="class-pill-remove" @click.stop="removeClass(a.classId)">
                <XMarkIcon class="w-3 h-3" />
              </span>
            </button>

            <button
              @click="showClassPicker = !showClassPicker"
              class="add-class-btn"
              :class="showClassPicker ? 'add-class-btn--open' : ''"
            >
              <PlusIcon
                class="w-3.5 h-3.5 transition-transform duration-200"
                :class="showClassPicker ? 'rotate-45' : ''"
              />
              Add Class
            </button>
          </div>

          <!-- Class picker -->
          <Transition name="slide-down">
            <div v-if="showClassPicker && availableClasses.length" class="class-picker">
              <div class="class-picker-grid">
                <button
                  v-for="cls in availableClasses"
                  :key="cls.id"
                  @click="addClass(cls)"
                  class="class-picker-item"
                >
                  {{ cls.name }}
                </button>
              </div>
            </div>
          </Transition>

          <p v-if="assignments.length === 0" class="text-[0.72rem] text-white/20 italic">
            No new classes — add one above
          </p>

          <!-- Subject picker -->
          <Transition name="slide-down">
            <div v-if="activeAssignment" class="subject-section">
              <div class="flex items-center gap-2 text-[0.65rem] font-semibold
                tracking-[0.1em] uppercase text-white/30 mb-3">
                <BookOpenIcon class="w-3.5 h-3.5" />
                Subjects for
                <span class="text-white font-semibold normal-case tracking-normal">
                  {{ activeAssignment.className }}
                </span>
              </div>
              <div class="subject-grid">
                <button
                  v-for="subject in allSubjects"
                  :key="subject"
                  @click="toggleSubject(subject)"
                  class="subject-pill"
                  :class="hasSubject(subject) ? 'subject-pill--active' : ''"
                >
                  <span class="subject-check">
                    <CheckIcon v-if="hasSubject(subject)" class="w-2.5 h-2.5" />
                  </span>
                  {{ subject }}
                </button>
              </div>
              <p v-if="activeAssignment.subjects.length === 0"
                class="text-[0.72rem] text-white/20 italic mt-3">
                No subjects selected yet
              </p>
            </div>
          </Transition>

          <!-- Assign summary -->
          <div v-if="assignments.length" class="summary-card">
            <div class="summary-title">New Assignments</div>
            <div class="space-y-2">
              <div v-for="a in assignments" :key="a.classId" class="summary-row">
                <span class="summary-class">{{ a.className }}</span>
                <span class="summary-subjects">
                  {{ a.subjects.length ? a.subjects.join(', ') : 'No subjects' }}
                </span>
              </div>
            </div>
          </div>

        </div>
        <!-- END ASSIGN -->

        <!-- ══ RIGHT: DEASSIGN ════════════════════════════════ -->
        <div class=" w-[85vw] shrink-0 overflow-y-auto px-4 py-4  space-y-4 scrollbar-thin
        md:w-auto md:flex-1">

          <!-- Section label + clear -->
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-1.5 text-[0.65rem] font-semibold
              tracking-[0.1em] uppercase text-white/30">
              <XMarkIcon class="w-3 h-3" />
              Remove existing
            </div>
            <button
              v-if="deassignList.length > 0"
              @click="clearDeassign"
              class="text-[0.62rem] text-red-400/60 hover:text-red-400 transition-colors cursor-pointer"
            >
              Clear all
            </button>
          </div>

          <!-- Deassign count badge -->
          <Transition name="flash">
            <div
              v-if="deassignList.length > 0"
              class="flex items-center gap-2 px-3 py-1.5 rounded-lg border
              border-red-400/20 bg-red-400/8 text-red-400 text-[0.72rem] font-medium"
            >
              <XMarkIcon class="w-3 h-3 shrink-0" />
              {{ deassignList.length }} subject{{ deassignList.length > 1 ? 's' : '' }} marked for removal
            </div>
          </Transition>

          <!-- Existing assignments grouped by class -->
          <template v-if="existingAssignments.length">
            <div
              v-for="a in existingAssignments"
              :key="a.classId"
              class="rounded-xl border border-white/6 bg-white/[0.025] p-3 space-y-2"
            >
              <!-- Class name -->
              <p class="text-[0.65rem] font-bold tracking-[0.08em] uppercase
                text-white/30 font-mono">
                {{ a.className }}
              </p>

              <!-- Subject pills -->
              <div class="subject-grid">
                <button
                  v-for="subject in a.subjects"
                  :key="subject"
                  @click="toggleDeassign(a.classId, a.className, subject)"
                  class="subject-pill"
                  :class="isMarkedForRemoval(a.classId, subject) ? 'subject-pill--remove' : ''"
                >
                  <span
                    class="subject-check"
                    :class="isMarkedForRemoval(a.classId, subject) ? 'subject-check--remove' : ''"
                  >
                    <XMarkIcon v-if="isMarkedForRemoval(a.classId, subject)" class="w-2.5 h-2.5" />
                  </span>
                  <span :class="isMarkedForRemoval(a.classId, subject) ? 'line-through opacity-40' : ''">
                    {{ subject }}
                  </span>
                </button>
              </div>
            </div>
          </template>

          <p v-if="existingAssignments.length === 0"
            class="text-[0.72rem] text-white/20 italic">
            No existing assignments to remove
          </p>

        </div>
        <!-- END DEASSIGN -->

      </div>
    </template>
  </div>
</template>

<style scoped>
/*
  Hybrid scoped CSS — only for things Tailwind genuinely cannot do:
  1. assignment-panel background gradient + radial glows
  2. avatar conic ring (CSS mask trick)
  3. save-spinner keyframe animation
  4. subject-pill box-shadow on active state
  5. custom scrollbar styling
  6. Vue transition animation curves
  7. rgba() values that need dynamic opacity Tailwind can't express cleanly
     (e.g. rgba(255,255,255,0.025) backgrounds on dark panels)
*/

/* ── Panel background ────────────────────────────────────────── */
.assignment-panel {
  background: linear-gradient(160deg, #0f1e1c 0%, #0a1614 50%, #071210 100%);
  font-family: 'Outfit', sans-serif;
}

.assignment-panel::before {
  content: '';
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse 60% 40% at 80% 10%, rgba(7,94,84,0.25) 0%, transparent 60%),
    radial-gradient(ellipse 40% 30% at 10% 80%, rgba(37,211,102,0.08) 0%, transparent 60%);
  pointer-events: none;
}

/* ── Empty orb ───────────────────────────────────────────────── */
.empty-orb {
  width: 72px; height: 72px;
  border-radius: 50%;
  background: rgba(7,94,84,0.2);
  border: 1px solid rgba(7,94,84,0.3);
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 0 40px rgba(7,94,84,0.15);
}

/* ── Header ──────────────────────────────────────────────────── */
.panel-header {
  border-bottom: 1px solid rgba(255,255,255,0.06);
  position: relative;
  z-index: 1;
}

/* ── Avatar with conic attendance ring ───────────────────────── */
.avatar-wrap {
  position: relative;
  width: 48px; height: 48px;
  flex-shrink: 0;
}

.avatar-inner {
  width: 48px; height: 48px;
  border-radius: 50%;
  background: rgba(7,94,84,0.4);
  border: 1.5px solid rgba(37,211,102,0.3);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.5rem;
}

/* Conic gradient ring — impossible with Tailwind */
.avatar-ring {
  position: absolute;
  inset: -4px;
  border-radius: 50%;
  border: 1.5px solid transparent;
  background: conic-gradient(#25d366 0% 70%, transparent 70%) border-box;
  -webkit-mask: linear-gradient(#fff 0 0) padding-box, linear-gradient(#fff 0 0);
  -webkit-mask-composite: destination-out;
  mask-composite: exclude;
}

.teacher-name {
  font-size: 1rem;
  font-weight: 700;
  color: #ffffff;
  letter-spacing: -0.01em;
  line-height: 1.2;
}

.teacher-meta {
  font-size: 0.7rem;
  color: rgba(255,255,255,0.35);
  font-family: 'DM Mono', monospace;
  letter-spacing: 0.02em;
}

/* ── Save button ─────────────────────────────────────────────── */
.save-btn {
  display: flex; align-items: center; justify-content: center; gap: 6px;
  padding: 8px 18px;
  border-radius: 10px;
  font-size: 0.8rem;
  font-weight: 600;
  font-family: 'Outfit', sans-serif;
  cursor: pointer;
  transition: all 0.2s ease;
  background: linear-gradient(135deg, #075e54, #0a8a78);
  border: 1px solid rgba(37,211,102,0.3);
  color: #ffffff;
  box-shadow: 0 4px 15px rgba(7,94,84,0.4);
  min-width: 72px;
}

.save-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #0a8a78, #25d366);
  box-shadow: 0 4px 20px rgba(37,211,102,0.35);
  transform: translateY(-1px);
}

.save-btn:disabled { opacity: 0.35; cursor: not-allowed; box-shadow: none; }

/* Spinner — keyframe needed */
.save-spinner {
  width: 14px; height: 14px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* ── Saved flash ─────────────────────────────────────────────── */
.saved-flash {
  display: flex; align-items: center; gap: 6px;
  padding: 8px 12px;
  border-radius: 8px;
  background: rgba(37,211,102,0.12);
  border: 1px solid rgba(37,211,102,0.2);
  color: #25d366;
  font-size: 0.75rem;
  font-weight: 500;
}

/* ── Class pills ─────────────────────────────────────────────── */
.class-pill {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 6px 10px 6px 12px;
  border-radius: 10px;
  border: 1px solid rgba(255,255,255,0.1);
  background: rgba(255,255,255,0.05);
  color: rgba(255,255,255,0.6);
  font-size: 0.78rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.class-pill:hover { border-color: rgba(255,255,255,0.2); color: white; }

.class-pill--active {
  background: rgba(7,94,84,0.35);
  border-color: rgba(37,211,102,0.4);
  color: #a8f0c6;
  box-shadow: 0 0 12px rgba(37,211,102,0.1);
}

.class-pill-name { font-weight: 600; }

.class-pill-count {
  background: rgba(255,255,255,0.1);
  border-radius: 4px;
  padding: 1px 5px;
  font-size: 0.65rem;
  font-family: 'DM Mono', monospace;
}

.class-pill--active .class-pill-count {
  background: rgba(37,211,102,0.2);
  color: #25d366;
}

.class-pill-remove {
  display: flex; align-items: center; justify-content: center;
  width: 16px; height: 16px;
  border-radius: 4px;
  color: rgba(255,255,255,0.3);
  transition: all 0.15s ease;
}

.class-pill-remove:hover { background: rgba(239,68,68,0.2); color: #f87171; }

/* ── Add class button ────────────────────────────────────────── */
.add-class-btn {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 6px 12px;
  border-radius: 10px;
  border: 1px dashed rgba(37,211,102,0.3);
  background: transparent;
  color: rgba(37,211,102,0.6);
  font-size: 0.78rem;
  font-weight: 500;
  font-family: 'Outfit', sans-serif;
  cursor: pointer;
  transition: all 0.2s ease;
}

.add-class-btn:hover,
.add-class-btn--open {
  border-color: rgba(37,211,102,0.6);
  color: #25d366;
  background: rgba(37,211,102,0.08);
}

/* ── Class picker ────────────────────────────────────────────── */
.class-picker {
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 14px;
  padding: 12px;
  backdrop-filter: blur(10px);
}

.class-picker-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(72px, 1fr));
  gap: 6px;
}

.class-picker-item {
  padding: 7px 4px;
  border-radius: 8px;
  border: 1px solid rgba(255,255,255,0.07);
  background: rgba(255,255,255,0.03);
  color: rgba(255,255,255,0.55);
  font-size: 0.72rem;
  font-weight: 500;
  font-family: 'Outfit', sans-serif;
  cursor: pointer;
  transition: all 0.15s ease;
  text-align: center;
}

.class-picker-item:hover {
  background: rgba(7,94,84,0.3);
  border-color: rgba(37,211,102,0.3);
  color: #a8f0c6;
}

/* ── Subject section ─────────────────────────────────────────── */
.subject-section {
  background: rgba(255,255,255,0.025);
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: 16px;
  padding: 16px;
}

.subject-grid { display: flex; flex-wrap: wrap; gap: 7px; }

.subject-pill {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 6px 11px;
  border-radius: 8px;
  border: 1px solid rgba(255,255,255,0.08);
  background: rgba(255,255,255,0.04);
  color: rgba(255,255,255,0.5);
  font-size: 0.73rem;
  font-weight: 500;
  font-family: 'Outfit', sans-serif;
  cursor: pointer;
  transition: all 0.18s ease;
}

.subject-pill:hover { border-color: rgba(255,255,255,0.18); color: rgba(255,255,255,0.8); }

/* Assign active — box-shadow needed */
.subject-pill--active {
  background: rgba(7,94,84,0.4);
  border-color: rgba(37,211,102,0.45);
  color: #c6f6e0;
  box-shadow: 0 2px 10px rgba(37,211,102,0.1);
}

/* Deassign remove state */
.subject-pill--remove {
  background: rgba(239,68,68,0.12);
  border-color: rgba(239,68,68,0.35);
  color: #f87171;
}

.subject-check {
  width: 14px; height: 14px;
  border-radius: 4px;
  border: 1.5px solid rgba(255,255,255,0.2);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  transition: all 0.15s ease;
}

.subject-pill--active .subject-check {
  background: #25d366;
  border-color: #25d366;
  color: white;
}

.subject-check--remove {
  background: #ef4444;
  border-color: #ef4444;
  color: white;
}

/* ── Summary card ────────────────────────────────────────────── */
.summary-card {
  background: rgba(255,255,255,0.025);
  border: 1px solid rgba(255,255,255,0.06);
  border-radius: 14px;
  padding: 14px 16px;
}

.summary-title {
  font-size: 0.65rem;
  font-weight: 600;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: rgba(255,255,255,0.25);
  margin-bottom: 10px;
}

.summary-row {
  display: flex;
  align-items: baseline;
  gap: 10px;
  padding: 5px 0;
  border-bottom: 1px solid rgba(255,255,255,0.04);
}

.summary-row:last-child { border-bottom: none; }

.summary-class {
  font-size: 0.72rem;
  font-weight: 700;
  color: rgba(255,255,255,0.6);
  font-family: 'DM Mono', monospace;
  white-space: nowrap;
  min-width: 52px;
}

.summary-subjects {
  font-size: 0.7rem;
  color: rgba(255,255,255,0.3);
  line-height: 1.4;
}

/* ── Custom scrollbar ────────────────────────────────────────── */
.scrollbar-thin::-webkit-scrollbar       { width: 10px; }
.scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
.scrollbar-thin::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 2px; }

/* ── Vue transitions ─────────────────────────────────────────── */
.slide-down-enter-active { transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
.slide-down-leave-active { transition: all 0.2s ease-in; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-8px); }

.flash-enter-active { transition: all 0.3s ease; }
.flash-leave-active { transition: all 0.4s ease; }
.flash-enter-from, .flash-leave-to { opacity: 0; transform: translateY(-4px); }
</style>
