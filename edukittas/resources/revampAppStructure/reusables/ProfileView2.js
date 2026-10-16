
<template>
  <div class="card h-100 shadow-sm border-0 my-4 profile-card">
    <!-- Header with toggle button -->
    <div class="card-header">
      <button @click="editMode = !editMode" class="btn btn-sm btn-outline-primary mb-2">
        {{ editMode ? 'View Profile' : 'Edit Profile' }}
      </button>
    </div>

    <div class="card-body overflow-auto">
      <!-- Display Mode -->
      <div v-if="!editMode" class="row">
        <div
          v-for="field in visibleFields"
          :key="field.key"
          class="col-md-6 mb-4 d-flex"
        >
          <div class="fw-bold me-2" style="min-width: 120px">
            {{ field.label }}:
          </div>
          <div>
            {{ getSelectLabel(field, object[field.key]) }}
          </div>
        </div>
      </div>

      <!-- Edit Mode -->
      <form v-else @submit.prevent="submitChanges">
        <div class="row g-3 profile-content">
          <div
            v-for="field in editableObjectFields"
            :key="field.key"
            class="col-md-4"
          >
            <label class="form-label text-muted small">{{ field.label }}</label>
            <div class="border rounded bg-light p-2">
              <input
                v-if="['text', 'email', 'number'].includes(field.type)"
                v-model="editableObject[field.key]"
                :type="field.type"
                class="form-control form-control-sm border-0 bg-transparent p-0 m-0 shadow-none"
                :placeholder="field.label"
                @change="markAsEdited(field.key)"
              />

              <select
                v-else-if="field.type === 'select'"
                v-model="editableObject[field.key]"
                class="form-select form-select-sm border-0 bg-transparent p-0 m-0 shadow-none"
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

        <div class="card-footer text-end bg-light border-0 mt-3">
          <button type="button" class="btn btn-outline-secondary btn-sm me-2" @click="cancelEdit">
            Cancel
          </button>
          <button type="submit" :disabled="editedObjectKeys.length === 0" class="btn btn-primary btn-sm">
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

// Props for reusability: works for Teacher, Student, or any entity
const props = defineProps({
  object: { type: Object, required: true },
  objectFields: { type: Array, default: () => [] },
  editableObjectFields: { type: Array, default: () => [] },
  currentUser: { type: Object, required: true },
  defaultVisibleFields: { type: Array, default: () => [] }
})

// Edit/View state
const editMode = ref(false)

// Map to track edited fields
const editedMap = reactive(new Map())

// Reactive form state
const editableObject = reactive({})

// Computed: list of changed keys
const editedObjectKeys = computed(() => [...editedMap.keys()])

// Initialize form from object
const initializeForm = () => {
  props.editableObjectFields.forEach(field => {
    editableObject[field.key] = props.object[field.key] ?? ''
  })
  editedMap.clear()
}

// Watch for object changes
watch(
  () => props.object,
  initializeForm,
  { immediate: true }
)

// Mark a field as edited
const markAsEdited = (key) => {
  const original = props.object[key] ?? ''
  const current = editableObject[key]

  if (current !== original) {
    editedMap.set(key, current)
  } else {
    editedMap.delete(key)
  }
}

// Get label for select fields
const getSelectLabel = (field, value) => {
  return field.options?.find(opt => opt.value === value)?.label || value
}

// Computed: only changed fields
const editedObject = computed(() => Object.fromEntries(editedMap))

// Cancel editing and reset form
const cancelEdit = () => {
  initializeForm()
  editMode.value = false
}

// Submit changes (currently logs; replace with API call)
const submitChanges = () => {
  console.log('Submitting changes:', editedObject.value)
  editMode.value = false
}

// Computed: visible fields for display
const visibleFields = computed(() =>
  getVisibleFields({
    fields: props.objectFields,
    targetObject: props.object,
    currentUser: props.currentUser,
    defaultVisibleFields: props.defaultVisibleFields
  })
)
</script>

<style scoped>
.profile-card {
  height: 80vh;
  display: flex;
  flex-direction: column;
}

.profile-content {
  overflow-y: auto;
  flex-grow: 1;
}

@media (min-width: 768px) {
  .profile-content {
    overflow-y: hidden !important;
  }
}
</style>