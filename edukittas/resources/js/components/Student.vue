<template>
  <div class="flex flex-col h-full overflow-hidden font-mono" style="background: #0a1a26;">

    <!-- ── Toolbar ─────────────────────────────────────────────────────── -->
    <div class="toolbar-bar flex items-center justify-between flex-wrap gap-3 px-4 py-2.5 shrink-0">

      <!-- Left -->
      <div class="flex items-center gap-3">
        <span class="count-pill">
          <i class="bi bi-people-fill mr-1.5 text-[0.65rem]"></i>
          {{ displayStudents.length }}
          <span class="opacity-60 ml-0.5">student{{ displayStudents.length !== 1 ? 's' : '' }}</span>
        </span>

        <Transition name="fade">
          <span v-if="dirtyCount > 0" class="dirty-pill">
            <i class="bi bi-pencil-fill mr-1 text-[0.6rem]"></i>
            {{ dirtyCount }} unsaved
          </span>
        </Transition>
      </div>

      <!-- Right -->
      <div class="flex items-center gap-2">
        <Transition name="fade">
          <div v-if="editMode" class="flex items-center gap-2">
            <button class="btn-cancel" @click="cancelEdit">
              <i class="bi bi-x-lg text-[0.7rem]"></i>
              Cancel
            </button>
            <button
              class="btn-save"
              :class="dirtyCount === 0 ? 'btn-save--disabled' : ''"
              :disabled="dirtyCount === 0"
              @click="store.save(dirtyStudents)"
            >
              <i class="bi bi-cloud-upload-fill text-[0.7rem]"></i>
              Save{{ dirtyCount > 0 ? ` (${dirtyCount})` : '' }}
            </button>
          </div>
        </Transition>

        <button class="btn-edit" :class="editMode ? 'btn-edit--active' : ''" @click="toggleEditMode">
          <i :class="editMode ? 'bi bi-lock-fill' : 'bi bi-pencil-square'" class="text-[0.7rem]"></i>
          {{ editMode ? 'Editing' : 'Edit' }}
        </button>
      </div>
    </div>

    <!-- ── Table ──────────────────────────────────────────────────────── -->
    <div class="table-container flex-1 overflow-auto">
      <table class="sv-table w-max min-w-full" style="border-collapse: separate; border-spacing: 0;">

        <thead>
          <tr>
            <th
              v-for="col in columns"
              :key="col.key"
              @click="sortData(col.key)"
              class="th-base select-none cursor-pointer whitespace-nowrap p-0"
              :class="{
                'sticky-col th-sticky-corner': col.sticky,
                'th-active': sortKey === col.key,
              }"
            >
              <span class="flex items-center gap-1.5 px-3.5 py-2.5">
                {{ col.label }}
                <i  :class=" [getSortIcon(col.key), 'text-[0.6rem] transition-opacity duration-150',
                  sortKey === col.key ? 'opacity-100' : 'opacity-30']"
                ></i>
              </span>
            </th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="student in displayStudents"
            :key="student.id"
            class="sv-row"
            :class="{
              'row-dirty': isDirtyRow(student),
            }"
          >

            <!-- Surname — sticky -->
            <td class="sticky-col td-sticky-col p-1.5" :class="{ 'cell-dirty': isFieldDirty(student, 'surname') }">
              <input
                v-model.trim="student.surname"
                type="text"
                class="cell-input"
                :class="{ 'cell-readonly': !editMode }"
                :readonly="!editMode"
                @change="markAsEdited(student, 'surname')"
              />
            </td>

            <!-- First Name -->
            <td class="td-base p-1.5" :class="{ 'cell-dirty': isFieldDirty(student, 'firstName') }">
              <input
                v-model.trim="student.firstName"
                type="text"
                class="cell-input"
                :class="{ 'cell-readonly': !editMode }"
                :readonly="!editMode"
                @change="markAsEdited(student, 'firstName')"
              />
            </td>

            <!-- Other Name -->
            <td class="td-base p-1.5" :class="{ 'cell-dirty': isFieldDirty(student, 'otherName') }">
              <input
                v-model.trim="student.otherName"
                type="text"
                class="cell-input"
                :class="{ 'cell-readonly': !editMode }"
                :readonly="!editMode"
                @change="markAsEdited(student, 'otherName')"
              />
            </td>

            <!-- Gender -->
            <td class="td-base p-1.5" :class="{ 'cell-dirty': isFieldDirty(student, 'gender') }">
              <select
                v-model="student.gender"
                class="cell-input cell-select"
                :class="{ 'cell-readonly': !editMode }"
                :disabled="!editMode"
                @change="markAsEdited(student, 'gender')"
              >
                <option value="M">Male</option>
                <option value="F">Female</option>
              </select>
            </td>


             <!-- class -->
    
            <td class="td-base p-1.5" :class="{ 'cell-dirty': isFieldDirty(student, 'class') }">
              <input
                v-model.trim="student.class"
                type="text"
                class="cell-input"
                :class="{ 'cell-readonly': !editMode }"
                :readonly="!editMode"
                @change="markAsEdited(student, 'class')"
              />
            </td>

            <!-- Date of Birth -->
            <td class="td-base p-1.5" :class="{ 'cell-dirty': isFieldDirty(student, 'dob') }">
              <input
                v-model="student.dob"
                type="date"
                class="cell-input"
                :class="{ 'cell-readonly': !editMode }"
                :readonly="!editMode"
                @change="markAsEdited(student, 'dob')"
              />
            </td>

            <!-- Field — badge in read, select in edit -->
            <td class="td-base p-1.5" :class="{ 'cell-dirty': isFieldDirty(student, 'field') }">
              <span v-if="!editMode"  class="field-badge">
                {{ student.field }}
              </span>
              <select
                v-else
                v-model="student.field"
                class="cell-input cell-select"
                @change="markAsEdited(student, 'field')"
              >
                <option value="0">Mixed</option>
                <option value="1">Art</option>
                <option value="2">Science</option>
              </select>
            </td>

            <!-- LGA -->
            <td class="td-base p-1.5" :class="{ 'cell-dirty': isFieldDirty(student, 'lga') }">
              <span v-if="!editMode" class="cell-text">{{ student.lga}}</span>
              <select
                v-else
                v-model="student.lga"
                class="cell-input cell-select"
                @change="markAsEdited(student, 'lga')"
              >
                <option v-for="lga in store.lgas" :key="lga.id" :value="lga.id">{{ lga.name }}</option>
              </select>
            </td>

            <!-- NIN -->
            <td class="td-base p-1.5" :class="{ 'cell-dirty': isFieldDirty(student, 'nin') }">
              <input
                v-model.number.trim="student.nin"
                type="number"
                maxlength="11"
                class="cell-input"
                :class="{ 'cell-readonly': !editMode }"
                :readonly="!editMode"
                @change="markAsEdited(student, 'nin')"
              />
            </td>

            <!-- Phone -->
            <td class="td-base p-1.5" :class="{ 'cell-dirty': isFieldDirty(student, 'phone') }">
              <input
                v-model.trim="student.phone"
                type="tel"
                pattern="^\d{11}$"
                class="cell-input"
                :class="{ 'cell-readonly': !editMode }"
                :readonly="!editMode"
                @change="markAsEdited(student, 'phone')"
              />
            </td>

            <!-- Email -->
            <td class="td-base p-1.5" :class="{ 'cell-dirty': isFieldDirty(student, 'email') }">
              <input
                v-model.trim="student.email"
                type="email"
                class="cell-input"
                :class="{ 'cell-readonly': !editMode }"
                :readonly="!editMode"
                @change="markAsEdited(student, 'email')"
              />
            </td>

            <!-- Arm — badge in read, select in edit -->
            <td class="td-base p-1.5" :class="{ 'cell-dirty': isFieldDirty(student, 'arm') }">
              <span v-if="!editMode" class="arm-badge">{{student.arm}}</span>
              <select
                v-else
                v-model="student.arm"
                class="cell-input cell-select"
                @change="markAsEdited(student, 'arm')"
              >
                <option v-for="arm in arms" :key="arm.id" :value="arm.id">{{ arm.name }}</option>
              </select>
            </td>

            <!-- Attendance — read only -->
            <td class="td-base p-1.5">
              <div class="flex flex-col gap-1 min-w-[56px]">
                <span
                  class="text-[0.7rem] font-bold tracking-wide"
                  :class="(student.attendance ?? 0) >= 80 ? 'text-[#43efef]' : 'text-rose-400'"
                >
                  {{ student.attendance ?? 0 }}%
                </span>
                <div class="mini-bar">
                  <div
                    class="mini-bar-fill"
                    :class="(student.attendance ?? 0) >= 80 ? 'mini-bar-fill--good' : 'mini-bar-fill--low'"
                    :style="{ width: (student.attendance ?? 0) + '%' }"
                  ></div>
                </div>
              </div>
            </td>

            <!-- Performance — read only -->
            <td class="td-base p-1.5">
              <div class="flex flex-col gap-1 min-w-[56px]">
                <span
                  class="text-[0.7rem] font-bold tracking-wide"
                  :class="(student.performance ?? 0) >= 60 ? 'text-[#43efef]' : 'text-amber-400'"
                >
                  {{ student.performance ?? 0 }}%
                </span>
                <div class="mini-bar">
                  <div
                    class="mini-bar-fill"
                    :class="(student.performance ?? 0) >= 60 ? 'mini-bar-fill--good' : 'mini-bar-fill--warn'"
                    :style="{ width: (student.performance ?? 0) + '%' }"
                  ></div>
                </div>
              </div>
            </td>

          </tr>
        </tbody>
      </table>
    </div>

    <!-- <MobileStudentEditButton v-if="store.isMobile" /> -->
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useMainStore } from '@/stores/mainStore'
//import MobileStudentEditButton from '@/components/MobileStudentEditButton.vue'

// ─── Props & Emits ────────────────────────────────────────────────────────────
const props = defineProps({
  items:         { type: Array,  default: () => [] }, 
})


// ─── Stores ───────────────────────────────────────────────────────────────────
const store = useMainStore()


// ─── Local state ──────────────────────────────────────────────────────────────
const editMode  = ref(false)
const sortKey   = ref(null)
const sortOrder = ref('asc')

// editedRows: { studentId: { fieldKey: newValue, ... } }
// Tracks changes without mutating the source object's metadata
const editedRows = ref({})

// ─── Columns ──────────────────────────────────────────────────────────────────
const columns = [
  { key: 'surname',     label: 'Surname',       sticky: true },
  { key: 'firstName',   label: 'First Name'    },
  { key: 'otherName',   label: 'Other Name'    },
  { key: 'gender',         label: 'Gender'           },
   { key: 'class',       label: 'Class'      },
  { key: 'dob',         label: 'Date of Birth' },
 
  { key: 'field',       label: 'Field'         },
  { key: 'lga',         label: 'L.G.A'         },
  { key: 'nin',         label: 'NIN'           },
  { key: 'phone',       label: 'Phone No'      },
  { key: 'email',       label: 'Email'         },
  { key: 'arm',         label: 'Arm'           },
  { key: 'attendance',  label: 'Attendance'    },
  { key: 'performance', label: 'Performance'   },
]

// ─── Source & display ─────────────────────────────────────────────────────────
const tempStudents = ref([...props.items])

const displayStudents = computed(() =>
  editMode.value ? props.items : tempStudents.value
)

const lgas = computed(()=>store.lgas)
const arms = computed(()=>store.arms)

// ─── Sort ─────────────────────────────────────────────────────────────────────
function sortData(key) {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value   = key
    sortOrder.value = 'asc'
  }
  tempStudents.value.sort((a, b) => {
    const x = a[key] ?? ''
    const y = b[key] ?? ''
    if (x < y) return sortOrder.value === 'asc' ? -1 : 1
    if (x > y) return sortOrder.value === 'asc' ?  1 : -1
    return 0
  })
}

function getSortIcon(key) {
  if (sortKey.value !== key) return 'bi bi-arrow-down-up'
  return sortOrder.value === 'asc' ? 'bi bi-arrow-up' : 'bi bi-arrow-down'
}

// ─── Dirty tracking ───────────────────────────────────────────────────────────
function markAsEdited(student, field) {
  if (!editedRows.value[student.id]) editedRows.value[student.id] = {}
  editedRows.value[student.id][field] = student[field]
}

function isFieldDirty(student, field) {
  return editedRows.value[student.id]?.[field] !== undefined
}

function isDirtyRow(student) {
  return !!editedRows.value[student.id]
}

const dirtyStudents = computed(() =>
  displayStudents.value.filter(s => editedRows.value[s.id])
)
const dirtyCount = computed(() => Object.keys(editedRows.value).length)

// ─── Edit mode ────────────────────────────────────────────────────────────────
function toggleEditMode() {
  editMode.value = !editMode.value
  if (!editMode.value) {
    editedRows.value   = {}
    tempStudents.value = [...props.items]
  }
}

function cancelEdit() {
  editedRows.value   = {}
  editMode.value     = false
  tempStudents.value = [...props.items]
}



// ─── Passport ─────────────────────────────────────────────────────────────────
// function handlePassport(student, e) {
//   const file = e.target.files[0]
//   if (!file) return
//   student._passportPreview = URL.createObjectURL(file)
//   student.passport = file
//   markAsEdited(student, 'passport')
// }

// ─── Display helpers ──────────────────────────────────────────────────────────
//const getFieldName = (v) => ({ '0': 'Mixed', '1': 'Art', '2': 'Science' }[v] ?? v)
//const getLgaName   = (id) => lgas.value?.find(l => l.id === id)?.name ?? id
//const getArmName   = (id) => arms.value?.find(a => a.id === id)?.name ?? id

// const fieldBadgeClass = (v) => ({
//   '0': 'field-badge--mixed',
//   '1': 'field-badge--art',
//   '2': 'field-badge--science',
// }[v] ?? '')
</script>

<style scoped>
/*
  Scoped CSS only where Tailwind falls short:
  – sticky z-index layering
  – custom scrollbar
  – dirty-cell inset box-shadow
  – group-hover into sticky td
  – cell input focus / readonly
  – select option background
  – badge variants
  – mini bar gradients
  – transition helpers
*/

/* ── Toolbar ────────────────────────────────────────────────── */
.toolbar-bar {
  background: rgba(13, 31, 45, 0.95);
  border-bottom: 1px solid rgba(67, 239, 239, 0.12);
  backdrop-filter: blur(6px);
}

/* ── Count pill ─────────────────────────────────────────────── */
.count-pill {
  display: inline-flex;
  align-items: center;
  font-size: 0.7rem;
  font-weight: 600;
  letter-spacing: 0.05em;
  color: rgba(67, 239, 239, 0.75);
  background: rgba(67, 239, 239, 0.07);
  border: 1px solid rgba(67, 239, 239, 0.15);
  padding: 3px 10px;
  border-radius: 20px;
}

/* ── Dirty pill ─────────────────────────────────────────────── */
.dirty-pill {
  display: inline-flex;
  align-items: center;
  font-size: 0.68rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  color: #fbbf24;
  background: rgba(251, 191, 36, 0.1);
  border: 1px solid rgba(251, 191, 36, 0.25);
  padding: 3px 10px;
  border-radius: 20px;
}

/* ── Buttons ────────────────────────────────────────────────── */
.btn-edit {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 14px;
  border-radius: 7px;
  font-size: 0.72rem;
  font-weight: 600;
  font-family: inherit;
  letter-spacing: 0.04em;
  cursor: pointer;
  transition: all 0.18s ease;
  border: 1px solid rgba(67, 239, 239, 0.25);
  background: transparent;
  color: rgba(67, 239, 239, 0.7);
}
.btn-edit:hover {
  border-color: rgba(67, 239, 239, 0.7);
  background: rgba(67, 239, 239, 0.06);
  color: #43efef;
}
.btn-edit--active {
  border-color: #43efef;
  background: rgba(67, 239, 239, 0.12);
  color: #43efef;
  box-shadow: 0 0 12px rgba(67, 239, 239, 0.12);
}

.btn-save {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 14px;
  border-radius: 7px;
  font-size: 0.72rem;
  font-weight: 600;
  font-family: inherit;
  letter-spacing: 0.04em;
  cursor: pointer;
  transition: all 0.18s ease;
  border: 1px solid rgba(67, 239, 239, 0.5);
  background: rgba(67, 239, 239, 0.14);
  color: #43efef;
}
.btn-save:not(.btn-save--disabled):hover {
  background: rgba(67, 239, 239, 0.22);
  border-color: #43efef;
  box-shadow: 0 0 14px rgba(67, 239, 239, 0.18);
}
.btn-save--disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.btn-cancel {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 14px;
  border-radius: 7px;
  font-size: 0.72rem;
  font-weight: 600;
  font-family: inherit;
  letter-spacing: 0.04em;
  cursor: pointer;
  transition: all 0.18s ease;
  border: 1px solid rgba(248, 113, 113, 0.25);
  background: transparent;
  color: rgba(248, 113, 113, 0.7);
}
.btn-cancel:hover {
  border-color: rgba(248, 113, 113, 0.6);
  background: rgba(248, 113, 113, 0.07);
  color: #f87171;
}

/* ── Scrollbar ──────────────────────────────────────────────── */

.table-container::-webkit-scrollbar { width: 12px; height: 12px; }
.table-container::-webkit-scrollbar-thumb {
  background: rgba(67, 239, 239, 0.18);
  border-radius: 4px;
}
@media (max-width: 768px) {
  .table-container::-webkit-scrollbar { display: none; }
  .table-container { scrollbar-width: none; }
}

/* ── TH sticky top ──────────────────────────────────────────── */
.th-base {
  position: sticky;
  top: 0;
  z-index: 3;
  background: #0c2030;
  font-size: 0.64rem;
  font-weight: 700;
  letter-spacing: 0.09em;
  text-transform: uppercase;
  color: rgba(67, 239, 239, 0.5);
  border-bottom: 1px solid rgba(67, 239, 239, 0.14);
  transition: color 0.15s;
}
.th-base:hover { color: rgba(67, 239, 239, 0.85); }
.th-active     { color: #43efef !important; }

/* ── Sticky col ─────────────────────────────────────────────── */
.sticky-col { position: sticky; left: 0; z-index: 2; }
.th-sticky-corner { z-index: 4 !important; }

/* ── TD base ────────────────────────────────────────────────── */
.td-base {
  background: rgba(10, 26, 38, 1);
  border-bottom: 1px solid rgba(255, 255, 255, 0.03);
  width: 1%;
  max-width: 160px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap; 
  transition: background 0.15s;
}
.sv-row:hover .td-base {
  background: rgba(67, 239, 239, 0.035);
}

/* ── TD sticky ──────────────────────────────────────────────── */
.td-sticky-col {
  background: rgba(12, 28, 42, 1);
  border-right: 1px solid rgba(67, 239, 239, 0.09);
  border-bottom: 1px solid rgba(255, 255, 255, 0.03);
 width: 1%;
 max-width: 160px;
 overflow: ellipsis;
 white-space: nowrap;
  transition: background 0.15s;
}
.sv-row:hover .td-sticky-col { background: rgba(67, 239, 239, 0.05); }


/* ── Dirty row / cell ───────────────────────────────────────── */
.row-dirty .td-base,
.row-dirty .td-sticky-col {
  background: rgba(251, 191, 36, 0.025);
}
.cell-dirty {
  background: rgba(251, 191, 36, 0.07) !important;
  box-shadow: inset 2px 0 0 rgba(251, 191, 36, 0.7);
}

/* ── Cell inputs ────────────────────────────────────────────── */
.cell-input {
  width: 100%;
  background: transparent;
  border: 1px solid transparent;
  border-radius: 4px;
  padding: 5px 8px;
  font-size: 0.78rem;
  font-family: inherit;
  color: rgba(200, 234, 240, 0.9);
  outline: none;
  transition: border-color 0.15s, background 0.15s;
}
.cell-input:not(.cell-readonly):focus {
  border-color: rgba(67, 239, 239, 0.4);
  background: rgba(67, 239, 239, 0.05);
}
.cell-readonly {
  border-color: transparent !important;
  background: transparent !important;
  cursor: default;
  pointer-events: none;
  color: rgba(200, 234, 240, 0.65);
}
.cell-select { cursor: pointer; }
.cell-select option {
  background: #0f2537;
  color: #c8eaf0;
}

.cell-text {
  font-size: 0.78rem;
  color: rgba(200, 234, 240, 0.65);
}

.cell-select.cell-readonly{appearance: none; -webkit-appearance: none;}

/* ── Field badges ───────────────────────────────────────────── */
.field-badge {
  display: inline-block;
  padding: 2px 9px;
  border-radius: 4px;
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}
/* .field-badge--mixed {
  background: rgba(67, 239, 239, 0.08);
  color: rgba(67, 239, 239, 0.6);
  border: 1px solid rgba(67, 239, 239, 0.15);
}
.field-badge--art {
  background: rgba(167, 139, 250, 0.12);
  color: rgba(196, 181, 253, 0.9);
  border: 1px solid rgba(167, 139, 250, 0.2);
}
.field-badge--science {
  background: rgba(52, 211, 153, 0.1);
  color: rgba(110, 231, 183, 0.9);
  border: 1px solid rgba(52, 211, 153, 0.18);
} */

/* ── Arm badge ──────────────────────────────────────────────── */
.arm-badge {
  display: inline-block;
  padding: 2px 9px;
  /* border-radius: 4px; */
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  /* background: rgba(67, 239, 239, 0.1); */
  color: rgba(67, 239, 239, 0.8);
  /* border: 1px solid rgba(67, 239, 239, 0.18); */
  font-family: 'DM Mono', monospace;
}

/* ── Mini bars ──────────────────────────────────────────────── */
.mini-bar {
  height: 3px;
  border-radius: 99px;
  background: rgba(255, 255, 255, 0.06);
  overflow: hidden;
}
.mini-bar-fill {
  height: 100%;
  border-radius: 99px;
  transition: width 0.5s ease;
}
.mini-bar-fill--good { background: linear-gradient(90deg, rgba(67,239,239,0.7), rgba(67,239,239,1)); }
.mini-bar-fill--low  { background: linear-gradient(90deg, rgba(239,68,68,0.7), rgba(248,113,113,1)); }
.mini-bar-fill--warn { background: linear-gradient(90deg, rgba(217,119,6,0.7), rgba(251,191,36,1)); }

/* ── Fade transition (Cancel/Save appear) ───────────────────── */
.fade-enter-active, .fade-leave-active { transition: opacity 0.18s ease, transform 0.18s ease; }
.fade-enter-from, .fade-leave-to       { opacity: 0; transform: translateY(-3px); }
</style>
