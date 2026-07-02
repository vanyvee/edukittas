<template>
  <div class="profile-card my-4">
    <!-- Header -->
    <div class="flex items-center justify-between px-5 py-3.5 bg-white border-b border-stone-200">
      <h2 class="text-[0.65rem] font-bold uppercase tracking-widest text-stone-400">Profile</h2>
      <button
        @click="editMode = !editMode"
        class="text-[0.72rem] font-semibold px-3.5 py-1.5 rounded-md transition-all duration-150"
        :class="editMode
          ? 'bg-white text-stone-500 border border-stone-300 hover:bg-stone-50'
          : 'bg-stone-900 text-white border border-stone-900 hover:bg-stone-700 shadow-sm'"
      >
        {{ editMode ? 'View Profile' : 'Edit Profile' }}
      </button>
    </div>

    <!-- Body -->
    <div class="profile-body flex-grow overflow-y-auto px-5 py-4">

      <!-- Display Mode -->
      <div v-if="!editMode" class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div
          v-for="field in visibleFields"
          :key="field.key"
          class="flex flex-col gap-1 bg-white border border-stone-200 rounded-xl px-4 py-3"
        >
          <span class="text-[0.65rem] font-bold uppercase tracking-wider text-stone-400">
            {{ field.label }}
          </span>
          <span class="text-sm font-medium text-stone-800">
            {{ getSelectLabel(field, object[field.key]) }}
          </span>
        </div>
      </div>

      <!-- Edit Mode -->
      <form v-else @submit.prevent="submitChanges">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 profile-content">
          <div
            v-for="field in editableObjectFields"
            :key="field.key"
            class="flex flex-col gap-1"
          >
            <label class="text-[0.65rem] font-bold uppercase tracking-wider text-stone-400">
              {{ field.label }}
            </label>
            <div
              class="bg-white border rounded-lg px-3 py-2 transition-all duration-150 focus-within:ring-2 focus-within:ring-stone-300 focus-within:border-stone-400"
              :class="editedMap.has(field.key)
                ? 'border-amber-400 bg-amber-50'
                : 'border-stone-200'"
            >
              <input
                v-if="['text', 'email', 'number'].includes(field.type)"
                v-model="editableObject[field.key]"
                :type="field.type"
                :placeholder="field.label"
                class="w-full bg-transparent text-sm text-stone-800 outline-none placeholder-stone-300"
                @change="markAsEdited(field.key)"
              />
              <select
                v-else-if="field.type === 'select'"
                v-model="editableObject[field.key]"
                class="w-full bg-transparent text-sm text-stone-800 outline-none"
                @change="markAsEdited(field.key)"
              >
                <option
                  v-for="option in field.options"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </option>
              </select>
            </div>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="flex justify-end gap-2 mt-5 pt-4 border-t border-stone-100">
          <button
            type="button"
            class="text-[0.72rem] font-semibold px-4 py-1.5 rounded-md border border-stone-300 text-stone-600 hover:bg-stone-50 transition-all duration-150"
            @click="cancelEdit"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="editedObjectKeys.length === 0"
            class="text-[0.72rem] font-semibold px-4 py-1.5 rounded-md bg-stone-900 text-white hover:bg-stone-700 disabled:opacity-30 disabled:cursor-not-allowed transition-all duration-150 shadow-sm"
          >
            Save Changes
          </button>
        </div>
      </form>

    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import getVisibleFields from '@/utils/getVisibleFields'

const props = defineProps({
  object: { type: Object, required: true },
  objectFields: { type: Array, default: () => [] },
  editableObjectFields: { type: Array, default: () => [] },
  currentUser: { type: Object, required: true },
  defaultVisibleFields: { type: Array, default: () => [] }
})

const editMode = ref(false)
const editedMap = reactive(new Map())
const editableObject = reactive({})

const editedObjectKeys = computed(() => [...editedMap.keys()])

const initializeForm = () => {
    if(!props.object) return
  props.editableObjectFields.forEach(field => {
    editableObject[field?.key] = props.object[field?.key] ?? ''
  })
  editedMap.clear()
}

watch(() => props.object, initializeForm, { immediate: true })

const markAsEdited = (key) => {
  const original = props.object[key] ?? ''
  const current = editableObject[key]
  if (current !== original) {
    editedMap.set(key, current)
  } else {
    editedMap.delete(key)
  }
}

const getSelectLabel = (field, value) => {
  return field.options?.find(opt => opt.value === value)?.label || value
}

const editedObject = computed(() => Object.fromEntries(editedMap))

const cancelEdit = () => {
  initializeForm()
  editMode.value = false
}

const submitChanges = () => {
  console.log('Submitting changes:', editedObject.value)
  editMode.value = false
}

const visibleFields = computed(() => {
if(!props.object || !props.currentUser) return []
return  getVisibleFields({
    fields: props.objectFields,
    targetObject: props.object,
    currentUser: props.currentUser,
    defaultVisibleFields: props.defaultVisibleFields
  })


}
 
)
</script>

<style scoped>
/* Card shell — warm background, layered shadow, and 80vh height */
.profile-card {
  height: 80vh;
  display: flex;
  flex-direction: column;
  border-radius: 1rem;
  border: 1px solid #e7e3dd;
  background: #faf9f7;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05), 0 0 0 1px rgba(0,0,0,0.02);
  overflow: hidden;
}

/* Custom scrollbar — no Tailwind equivalent */
.profile-body::-webkit-scrollbar { width: 4px; }
.profile-body::-webkit-scrollbar-track { background: transparent; }
.profile-body::-webkit-scrollbar-thumb { background: #d6d0c9; border-radius: 99px; }

/* Edit content overflow — responsive behaviour */
.profile-content { overflow-y: auto; }
@media (min-width: 768px) {
  .profile-content { overflow-y: hidden; }
}
</style>
