<template>
  <div class="flex flex-col h-full bg-[#0d1f2d] rounded-xl overflow-hidden font-mono">

    <!-- ── Toolbar ─────────────────────────────────────────────────────── -->
    <div class="flex items-center justify-between flex-wrap gap-3 px-4 py-2.5 bg-[#0d1f2d] border-b border-[#43efef]/15 shrink-0">

      <!-- Left -->
      <div class="flex items-center gap-3">
        <span class="text-xs tracking-wide text-[#43efef]/60">
          <i class="bi bi-people-fill mr-1"></i>
          {{ displayStudents.length }} student{{ displayStudents.length !== 1 ? 's' : '' }}
        </span>
        <span
          v-if="dirtyCount > 0"
          class="text-[0.7rem] tracking-wide px-2.5 py-0.5 rounded-full bg-amber-400/15 text-amber-400 border border-amber-400/30"
        >
          <i class="bi bi-pencil-fill mr-1 text-[0.65rem]"></i>
          {{ dirtyCount }} unsaved
        </span>
      </div>

      <!-- Right -->
      <div class="flex items-center gap-2">
        <button
          v-if="dirtyCount > 0"
          class="text-xs tracking-wide px-3.5 py-1 rounded-md border border-amber-400/40 bg-amber-400/10 text-amber-400 cursor-pointer transition-all hover:bg-amber-400/20 hover:border-amber-400"
          @click="$emit('save', dirtyStudents)"
        >
          <i class="bi bi-cloud-upload-fill mr-1"></i> Save Changes
        </button>
        <button
          class="text-xs tracking-wide px-3.5 py-1 rounded-md border cursor-pointer transition-all"
          :class="editMode
            ? 'border-[#43efef] bg-[#43efef]/10 text-[#43efef]'
            : 'border-[#43efef]/35 bg-transparent text-[#43efef]/75 hover:border-[#43efef] hover:bg-[#43efef]/10 hover:text-[#43efef]'"
          @click="toggleEditMode"
        >
          <i :class="editMode ? 'bi bi-lock-fill' : 'bi bi-pencil-square'" class="mr-1"></i>
          {{ editMode ? 'Lock' : 'Edit' }}
        </button>
      </div>
    </div>

    <!-- ── Table container ──────────────────────────────────────────────── -->
    <div class="table-container flex-1 overflow-auto">
      <table class="sv-table w-max min-w-full" style="border-collapse: separate; border-spacing: 0;">
        <thead>
          <tr>
            <th
              v-for="col in columns"
              :key="col.key"
              @click="sortData(col.key)"
              class="th-base select-none cursor-pointer whitespace-nowrap p-0 transition-colors duration-150"
              :class="{
                'sticky-col th-sticky-corner': col.key === 'surname',
                'text-[#43efef]': sortKey === col.key,
                'text-[#43efef]/70': sortKey !== col.key,
              }"
            >
              <span class="flex items-center gap-1.5 px-3.5 py-2.5">
                {{ col.label }}
                <i 
                   class="text-[0.65rem] transition-opacity duration-150"
                   :class="sortKey === col.key ? 'opacity-100' : 'opacity-40' "></i>
              </span>
            </th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="student in displayStudents"
            :key="student.id"
            class="group"
            :class="{ 'row-dirty': student._edited }"
          >
            <!-- Surname (sticky) -->
            <td
              class="sticky-col td-sticky-col p-1.5"
              :class="{ 'cell-dirty': isFieldDirty(student, 'surname') }"
            >
              <input v-model.trim="student.surname" type="text"
                @change="markAsEdited(student, 'surname')" :readonly="!editMode"
                class="cell-input" :class="{ 'cell-readonly': !editMode }" />
            </td>

            <!-- First Name -->
            <td class="td-base p-1.5" :class="{ 'cell-dirty': isFieldDirty(student, 'firstName') }">
              <input v-model.trim="student.firstName" type="text"
                @change="markAsEdited(student, 'firstName')" :readonly="!editMode"
                class="cell-input" :class="{ 'cell-readonly': !editMode }" />
            </td>

            <!-- Other Name -->
            <td class="td-base p-1.5" :class="{ 'cell-dirty': isFieldDirty(student, 'otherName') }">
              <input v-model.trim="student.otherName" type="text"
                @change="markAsEdited(student, 'otherName')" :readonly="!editMode"
                class="cell-input" :class="{ 'cell-readonly': !editMode }" />
            </td>

            <!-- Sex -->
            <td class="td-base p-1.5" :class="{ 'cell-dirty': isFieldDirty(student, 'sex') }">
              <select v-model="student.sex" @change="markAsEdited(student, 'sex')"
                :disabled="!editMode"
                class="cell-input cell-select" :class="{ 'cell-readonly': !editMode }">
                <option value="M">Male</option>
                <option value="F">Female</option>
              </select>
            </td>

            <!-- Date of Birth -->
            <td class="td-base p-1.5" :class="{ 'cell-dirty': isFieldDirty(student, 'dob') }">
              <input v-model="student.dob" type="date"
                @change="markAsEdited(student, 'dob')" :readonly="!editMode"
                class="cell-input" :class="{ 'cell-readonly': !editMode }" />
            </td>

            <!-- Passport -->
            <td class="td-base p-1.5" :class="{ 'cell-dirty': isFieldDirty(student, 'passport') }">
              <label
                class="flex items-center justify-center w-12 h-12 mx-auto my-0.5 rounded-md overflow-hidden border relative transition-colors duration-150"
                :class="editMode
                  ? 'border-[#43efef]/20 cursor-pointer hover:border-[#43efef]'
                  : 'border-[#43efef]/15 cursor-default pointer-events-none'"
              >
                <img v-if="student._passportPreview || student.passport"
                  :src="student._passportPreview || student.passport"
                  class="w-full h-full object-cover" alt="passport" />
                <span v-else class="text-[#43efef]/30 text-xl">
                  <i class="bi bi-person-bounding-box"></i>
                </span>
                <input v-if="editMode" type="file" accept="image/*" capture="camera"
                  class="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
                  @change="(e) => handlePassport(student, e)" />
              </label>
            </td>

            <!-- Field -->
            <td class="td-base p-1.5" :class="{ 'cell-dirty': isFieldDirty(student, 'field') }">
              <select v-model="student.field" @change="markAsEdited(student, 'field')"
                :disabled="!editMode"
                class="cell-input cell-select" :class="{ 'cell-readonly': !editMode }">
                <option value="0">Mixed</option>
                <option value="1">Art</option>
                <option value="2">Science</option>
              </select>
            </td>

            <!-- LGA -->
            <td class="td-base p-1.5" :class="{ 'cell-dirty': isFieldDirty(student, 'lga') }">
              <select v-model="student.lga" @change="markAsEdited(student, 'lga')"
                :disabled="!editMode"
                class="cell-input cell-select" :class="{ 'cell-readonly': !editMode }">
                <option v-for="lga in lgas" :key="lga.id" :value="lga.id">{{ lga.name }}</option>
              </select>
            </td>

            <!-- NIN -->
            <td class="td-base p-1.5" :class="{ 'cell-dirty': isFieldDirty(student, 'nin') }">
              <input v-model.number.trim="student.nin" type="number" maxlength="11"
                @change="markAsEdited(student, 'nin')" :readonly="!editMode"
                class="cell-input" :class="{ 'cell-readonly': !editMode }" />
            </td>

            <!-- Phone -->
            <td class="td-base p-1.5" :class="{ 'cell-dirty': isFieldDirty(student, 'phone') }">
              <input v-model.trim="student.phone" type="tel" pattern="^\d{11}$"
                @change="markAsEdited(student, 'phone')" :readonly="!editMode"
                class="cell-input" :class="{ 'cell-readonly': !editMode }" />
            </td>

            <!-- Email -->
            <td class="td-base p-1.5" :class="{ 'cell-dirty': isFieldDirty(student, 'email') }">
              <input v-model.trim="student.email" type="email"
                @change="markAsEdited(student, 'email')" :readonly="!editMode"
                class="cell-input" :class="{ 'cell-readonly': !editMode }" />
            </td>

            <!-- Arm -->
            <td class="td-base p-1.5" :class="{ 'cell-dirty': isFieldDirty(student, 'arm') }">
              <select v-model="student.arm" @change="markAsEdited(student, 'arm')"
                :disabled="!editMode"
                class="cell-input cell-select" :class="{ 'cell-readonly': !editMode }">
                <option v-for="arm in arms" :key="arm.id" :value="arm.id">{{ arm.name }}</option>
              </select>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- <MobileStudentEditButton v-if="myStore.isMobile" /> -->
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useMainStore } from '@/stores/mainStore'
// import { useMyStore } from '@/stores/myStore'
// import MobileStudentEditButton from '@/components/MobileStudentEditButton.vue'

// ─── Props & Emits ────────────────────────────────────────────────────────────
defineProps({
  lgas: { type: Array, default: () => [] },
  arms: { type: Array, default: () => [] },
})
defineEmits(['save'])

// ─── Stores (only what's genuinely global) ───────────────────────────────────
const store = useMainStore()
// const myStore   = useMyStore()

// ─── Local state ─────────────────────────────────────────────────────────────
const editMode  = ref(false)
const sortKey   = ref(null)
const sortOrder = ref('asc')

// ─── Column definitions ──────────────────────────────────────────────────────
const columns = [
  { key: 'surname',   label: 'Surname'       },
  { key: 'firstName', label: 'First Name'    },
  { key: 'otherName', label: 'Other Name'    },
  { key: 'sex',       label: 'Sex'           },
  { key: 'dob',       label: 'Date of Birth' },
  { key: 'passport',  label: 'Passport'      },
  { key: 'field',     label: 'Field'         },
  { key: 'lga',       label: 'L.G.A'         },
  { key: 'nin',       label: 'NIN'           },
  { key: 'phone',     label: 'Phone No'      },
  { key: 'email',     label: 'Email'         },
  { key: 'arm',       label: 'Arm'           },
]

// ─── Source data ──────────────────────────────────────────────────────────────
const tempStudents = ref([...store.classStudents])

const displayStudents = computed(() =>
  editMode.value ? store.classStudents : tempStudents.value
)

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
  student._edited = true
  if (!student._editFields) student._editFields = []
  if (!student._editFields.includes(field)) student._editFields.push(field)
}

function isFieldDirty(student, field) {
  return student._edited && student._editFields?.includes(field)
}

const dirtyStudents = computed(() => displayStudents.value.filter(s => s._edited))
const dirtyCount    = computed(() => dirtyStudents.value.length)

// ─── Edit mode ────────────────────────────────────────────────────────────────
function toggleEditMode() {
  editMode.value = !editMode.value
  if (!editMode.value) tempStudents.value = [...mainStore.classStudents]
}

// ─── Passport ─────────────────────────────────────────────────────────────────
function handlePassport(student, e) {
  const file = e.target.files[0]
  if (!file) return
  student._passportPreview = URL.createObjectURL(file)
  student.passport = file
  markAsEdited(student, 'passport')
}
</script>

<style scoped>
/*
  Scoped CSS only for things Tailwind can't cleanly express:
  - sticky positioning with precise z-index layering
  - custom scrollbar styling
  - dirty-cell inset box-shadow accent
  - group-hover reaching into sticky td (Tailwind group doesn't cross scoped boundaries well)
  - cell input focus ring & readonly suppression
  - select option background theming
*/

/* ── Custom scrollbar ───────────────────────────── */
.table-container {
  scrollbar-width: thin;
  scrollbar-color: rgba(67, 239, 239, 0.2) transparent;
}
.table-container::-webkit-scrollbar { width: 5px; height: 5px; }
.table-container::-webkit-scrollbar-thumb {
  background: rgba(67, 239, 239, 0.2);
  border-radius: 4px;
}
@media (max-width: 768px) {
  .table-container::-webkit-scrollbar { display: none; }
  .table-container { scrollbar-width: none; }
}

/* ── TH: sticky top + dark header background ────── */
.th-base {
  position: sticky;
  top: 0;
  z-index: 3;
  background: #0f2537;
  font-size: 0.68rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  border-bottom: 1px solid rgba(67, 239, 239, 0.2);
}

/* ── TH + TD sticky first column ───────────────── */
.sticky-col {
  position: sticky;
  left: 0;
  z-index: 2;
}
/* Corner cell: above both sticky axes */
.th-sticky-corner { z-index: 4 !important; }

/* ── TD base ────────────────────────────────────── */
.td-base {
  background: #0d1f2d;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
  min-width: 120px;
  transition: background 0.15s;
}
.group:hover .td-base {
  background: rgba(67, 239, 239, 0.04);
}

/* ── TD sticky column ───────────────────────────── */
.td-sticky-col {
  background: #0f2034;
  border-right: 1px solid rgba(67, 239, 239, 0.12);
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
  min-width: 120px;
  transition: background 0.15s;
}
.group:hover .td-sticky-col { background: #102640; }

/* ── Dirty states ───────────────────────────────── */
.row-dirty .td-base,
.row-dirty .td-sticky-col {
  background: rgba(251, 191, 36, 0.03);
}
.cell-dirty {
  background: rgba(251, 191, 36, 0.09) !important;
  box-shadow: inset 2px 0 0 #fbbf24;
}
.td-sticky-col.cell-dirty { background: rgba(251, 191, 36, 0.1) !important; }

/* ── Cell inputs ────────────────────────────────── */
.cell-input {
  width: 100%;
  min-width: 100px;
  background: transparent;
  border: 1px solid transparent;
  border-radius: 4px;
  padding: 5px 8px;
  font-size: 0.78rem;
  font-family: inherit;
  color: #c8eaf0;
  outline: none;
  transition: border-color 0.15s, background 0.15s;
}
.cell-input:not(.cell-readonly):focus {
  border-color: rgba(67, 239, 239, 0.5);
  background: rgba(67, 239, 239, 0.06);
}
.cell-readonly {
  border-color: transparent !important;
  background: transparent !important;
  cursor: default;
  pointer-events: none;
  color: rgba(200, 234, 240, 0.8);
}

/* ── Select option theming ──────────────────────── */
.cell-select { cursor: pointer; }
.cell-select option {
  background: #0f2537;
  color: #c8eaf0;
}
</style>
