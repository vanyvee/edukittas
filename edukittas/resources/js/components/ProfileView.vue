<template>
  <div class="profile-card my-4 shadow-sm">
    <!-- Header -->
    <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200">
      <h2 class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Profile</h2>
      <button
        @click="editMode = !editMode"
        class="text-xs font-medium px-3 py-1.5 rounded border transition-colors"
        :class="editMode
          ? 'border-gray-400 text-gray-600 hover:bg-gray-100'
          : 'border-blue-500 text-blue-600 hover:bg-blue-50'"
      >
        {{ editMode ? 'View Profile' : 'Edit Profile' }}
      </button>
    </div>

    <!-- Body -->
    <div class="profile-body overflow-auto px-5 py-4">

      <!-- Display Mode -->
      <div v-if="!editMode" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div
          v-for="field in visibleFields"
          :key="field.key"
          class="flex gap-2"
        >
          <span class="font-semibold text-gray-700 shrink-0" style="min-width: 130px">
            {{ field.label }}:
          </span>
          <span class="text-gray-600">
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
          >
            <label class="block text-xs font-medium text-gray-500 mb-1">
              {{ field.label }}
            </label>
            <div class="border border-gray-200 rounded bg-gray-50 px-3 py-2">
              <input
                v-if="['text', 'email', 'number'].includes(field.type)"
                v-model="editableObject[field.key]"
                :type="field.type"
                :placeholder="field.label"
                class="w-full bg-transparent text-sm text-gray-800 outline-none placeholder-gray-400"
                @change="markAsEdited(field.key)"
              />
              <select
                v-else-if="field.type === 'select'"
                v-model="editableObject[field.key]"
                class="w-full bg-transparent text-sm text-gray-800 outline-none"
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
        <div class="flex justify-end gap-2 mt-5 pt-4 border-t border-gray-100">
          <button
            type="button"
            class="text-xs font-medium px-4 py-1.5 rounded border border-gray-300 text-gray-600 hover:bg-gray-100 transition-colors"
            @click="cancelEdit"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="editedObjectKeys.length === 0"
            class="text-xs font-medium px-4 py-1.5 rounded bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
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
.profile-card {
  height: 80vh;
  display: flex;
  flex-direction: column;
  border-radius: 0.75rem;
  border: 1px solid #e5e7eb;
  background: #fff;
  overflow: hidden;
}

.profile-body {
  flex-grow: 1;
  overflow-y: auto;
}

.profile-content {
  overflow-y: auto;
}

@media (min-width: 768px) {
  .profile-content {
    overflow-y: hidden;
  }
}
</style>
