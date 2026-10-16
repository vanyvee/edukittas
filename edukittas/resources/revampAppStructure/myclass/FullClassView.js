<template>
<div class="table-container">

  <table class="table table-bordered">
    <thead>
      <tr>
        <th @click="studentStore.sortData('surname')" class="sortable">
          Surname <i :class="studentStore.getSortIcon('surname')"></i>
        </th>
        <th @click="studentStore.sortData('firstName')" class="sortable">
          First Name <i :class="studentStore.getSortIcon('firstName')"></i>
        </th>
        <th @click="studentStore.sortData('otherName')" class="sortable">
          Other Name <i :class="studentStore.getSortIcon('otherName')"></i>
        </th>
        <th @click="studentStore.sortData('sex')" class="sortable">
          Sex <i :class="studentStore.getSortIcon('sex')"></i>
        </th>
        <th @click="studentStore.sortData('dob')" class="sortable">
          Date of Birth <i :class="studentStore.getSortIcon('dob')"></i>
        </th>
        <th @click="studentStore.sortData('passport')" class="sortable">
          Passport <i :class="studentStore.getSortIcon('passport')"></i>
        </th>
        <th @click="studentStore.sortData('field')" class="sortable">
          Field <i :class="studentStore.getSortIcon('field')"></i>
        </th>
        <th @click="studentStore.sortData('lga')" class="sortable">
          L.G.A <i :class="studentStore.getSortIcon('lga')"></i>
        </th>
        <th @click="studentStore.sortData('nin')" class="sortable">
          NIN <i :class="studentStore.getSortIcon('nin')"></i>
        </th>
        <th @click="studentStore.sortData('phone')" class="sortable">
          Phone No <i :class="studentStore.getSortIcon('phone')"></i>
        </th>
        <th @click="studentStore.sortData('email')" class="sortable">
          Email <i :class="studentStore.getSortIcon('email')"></i>
        </th>
        <th @click="studentStore.sortData('arm')" class="sortable">
          Arm <i :class="studentStore.getSortIcon('arm')"></i>
        </th>
      </tr>
    </thead>

    <tbody>
      <tr v-for="student in editMode ? mainStore.classStudents : studentStore.tempStudents" :key="student.id">
        <td><input v-model.trim="student.surname" type="text" @change="markAsEdited(student, 'surname')" :readonly="!editMode" /></td>
        <td><input v-model.trim="student.firstName" type="text" @change="markAsEdited(student, 'firstName')" :readonly="!editMode" /></td>
        <td><input v-model.trim="student.otherName" type="text" @change="markAsEdited(student, 'otherName')" :readonly="!editMode" /></td>

        <td>
          <select v-model="student.sex" @change="markAsEdited(student, 'sex')" :disabled="!editMode">
            <option value="M">Male</option>
            <option value="F">Female</option>
          </select>
        </td>

        <td><input v-model="student.dob" type="date" @change="markAsEdited(student, 'dob')" :readonly="!editMode" /></td>

        <td>
          <input type="file" accept="image/*" capture="camera"
                 @change="(e) => markAsEdited(student, 'passport', e.target.files[0])"
                 :disabled="!editMode" />
        </td>

        <td>
          <select v-model="student.field" @change="markAsEdited(student, 'field')" :disabled="!editMode">
            <option value="0">Mixed</option>
            <option value="1">Art</option>
            <option value="2">Science</option>
          </select>
        </td>

        <td>
          <select v-model="student.lga" @change="markAsEdited(student, 'lga')" :disabled="!editMode">
            <option v-for="lga in lgas" :key="lga.id" :value="lga.id">{{ lga.name }}</option>
          </select>
        </td>

        <td><input v-model.number.trim="student.nin" type="number" maxlength="11" @change="markAsEdited(student, 'nin')" :readonly="!editMode" /></td>
        <td><input v-model.trim="student.phone" type="tel" pattern="^\\d{11}$" @change="markAsEdited(student, 'phone')" :readonly="!editMode" /></td>
        <td><input v-model.trim="student.email" type="email" @change="markAsEdited(student, 'email')" :readonly="!editMode" /></td>

        <td>
          <select v-model="student.arm" @change="markAsEdited(student, 'arm')" :disabled="!editMode">
            <option v-for="arm in arms" :key="arm.id" :value="arm.id">{{ arm.name }}</option>
          </select>
        </td>
      </tr>
    </tbody>
  </table>

  <mobileStudentEditButton v-if="myStore.isMobile" />
</template>

<script setup>
import { useStudentStore } from '@/stores/studentStore'
import { useMainStore } from '@/stores/mainStore'
import { useMyStore } from '@/stores/myStore'

const studentStore = useStudentStore()
const mainStore = useMainStore()
const myStore = useMyStore()

const editMode = ref(false)
const lgas = ref([])
const arms = ref([])

function markAsEdited(student, field, value = null) {
  if (value !== null) student[field] = value
  studentStore.markAsEdited(student, field)
}
</script>

<style scoped>
    /* Make the table scrollable */
    .table-container {
      position: relative;
      max-height: 400px; /* Adjust height as needed */
      overflow: auto;
    }

    /* Freeze header row */
    thead th {
      position: sticky;
      top: 0;
      background-color: #f8f9fa; /* Same as Bootstrap table header color */
      z-index: 2;
    }

    /* Freeze first column */
    tbody td:first-child,
    thead th:first-child {
      position: sticky;
      left: 0;
      background-color: #fff; /* Adjust to match table background */
      z-index: 1;
    }

    /* Hide scrollbar only on mobile devices */
    @media (max-width: 768px) {
      .table-container::-webkit-scrollbar {
        display: none; /* For Chrome, Safari, and Edge */
      }
      .table-container {
        -ms-overflow-style: none; /* For Internet Explorer and Edge */
        scrollbar-width: none; /* For Firefox */
      }
    }
      .sortable {
      cursor: pointer;
   }

   .sortable i {
      font-size: 0.8rem;
       margin-left: 5px;
    }

input.read-only,
select.read-only {
  border: none;
  background-color: transparent;
  pointer-events: none; /* Prevent focus and dropdown for selects */
}

  </style>




store design

import { defineStore } from 'pinia'

export const useStudentStore = defineStore('studentStore', {
  state: () => ({
    tempStudents: [],
    sortKey: null,
    sortOrder: 'asc',
  }),

  actions: {
    sortData(key) {
      if (this.sortKey === key) {
        this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc'
      } else {
        this.sortKey = key
        this.sortOrder = 'asc'
      }

      this.tempStudents.sort((a, b) => {
        const x = a[key] ?? ''
        const y = b[key] ?? ''
        if (x < y) return this.sortOrder === 'asc' ? -1 : 1
        if (x > y) return this.sortOrder === 'asc' ? 1 : -1
        return 0
      })
    },

    getSortIcon(key) {
      if (this.sortKey !== key) return 'bi bi-arrow-down-up'
      return this.sortOrder === 'asc' ? 'bi bi-arrow-up' : 'bi bi-arrow-down'
    },

    markAsEdited(student, field) {
      student._edited = true
      if (!student._editFields) student._editFields = []
      if (!student._editFields.includes(field)) {
        student._editFields.push(field)
      }
    },
  },
})

