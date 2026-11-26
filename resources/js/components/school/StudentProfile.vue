<template>
  <ProfileView2
    :object="props.item"
    :object-fields="studentFields"
    :editable-object-fields="studentEditableFields"
    :current-user="currentUser"
    :default-visible-fields="['name', 'grade']"
  />
</template>

<script setup>
import ProfileView from '@/components/ProfileView.vue'

const props = defineProps({
 item: { type: Object, required: true },
  currentUser: { type: Object, required: true }, // pass in from parent/store
})

const studentFields = [
  { key: 'name', label: 'Full Name' },
  { key: 'grade', label: 'Grade' },
  { key: 'email', label: 'Email' },
  { key: 'Phone', label: 'Phone' }
]

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
