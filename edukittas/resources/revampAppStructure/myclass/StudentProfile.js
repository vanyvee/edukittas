<template>
  <div>
    <ProfileView2
      :object="props.student"
      :object-fields="studentFields"
      :editable-object-fields="studentEditableFields"
      :current-user="currentUser"
      :default-visible-fields="['name', 'grade']"
    />
  </div>
</template>

<script setup>
import ProfileView2 from '@/components/ProfileCard.vue'


const props = defineProps({
  student: { type: Object, required: true },
})
const currentUser = {
  id: 1,
  role: 'admin'
}

const student = {
  id: 202,
  name: 'Jane Smith',
  grade: '10',
  email: 'jane@example.com',
  parentPhone: '0987654321'
}

// Fields to display in view mode
const studentFields = [
  { key: 'name', label: 'Full Name' },
  { key: 'grade', label: 'Grade' },
  { key: 'email', label: 'Email' },
  { key: 'parentPhone', label: 'Parent Phone' }
]

// Fields allowed to edit
const studentEditableFields = [
  { key: 'email', label: 'Email', type: 'email' },
  { key: 'parentPhone', label: 'Parent Phone', type: 'text' },
  { 
    key: 'grade', 
    label: 'Grade', 
    type: 'select', 
    options: [
      { value: '9', label: '9' },
      { value: '10', label: '10' },
      { value: '11', label: '11' }
    ]
  }
]
</script>

