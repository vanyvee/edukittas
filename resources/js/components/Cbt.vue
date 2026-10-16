<script setup>
import { provide, ref, } from 'vue'
import { useMainStore } from '../stores/mainStore';
import PcProfilePanel from './PcProfilePanel.vue';
import MobileProfilePanel from './MobileProfilePanel.vue';
import AssignTeacher from './AssignTeacher.vue';
import StudentProfile from './StudentProfile.vue';
// import ReusableAccordionList from './ReusableAccordionList.vue'
// import ChatAccordionBody from './ChatAccordionBody.vue'
// import ChatPCPanel from './ChatPCPanel.vue'


const store =  useMainStore()
const currentUser =  ref({ 
    id: '1', 
    name: 'John Doe', 
   role: 'admin',
    
    email: 'vany17@gmail.com',

  })

provide('accordionStore', store)
// Sample contacts
const contacts = ref([
  { 
    id: '1', 
    name: 'John Doe', 
      avatar: '👩', 
    surname: 'bella',
    firstName: 'ella',
    otherName: 'Mike',
    studentId: 'STU/2024/001',
    class: 'JSS 3',
    field: 'art',
    gender: 'M',
    dob: '2010-05-02',
    guardian: 'mr. james doe',
    phone: '08035688732',
    attendance: 87,
    gpa: 3.8,
    subjects: 12,
    performance: 78,
    term: '2nd term',
    status: 'Active',
    email: 'vany17@gmail.com',
    lga: 'eket',
     arm: 'a'

  },
  { 
    id: '2', 
    name: 'Jane Smith', 
    avatar: '👩', 
    surname: 'oercy',
    firstName: 'almight',
    otherName: 'Mike',
    studentId: 'STU/2024/001',
    class: 'JSS 3',
    field: 'science',
    gender: 'F',
    dob: '2010-05-02',
    guardian: 'mr. james doe',
    phone: '08035688732',
    attendance: 87,
    gpa: 3.8,
    subjects: 12,
    performance: 78,
    term: '2nd term',
    status: 'Active',
     email: 'vany17@gmail.com',
     lga: 'ibom',
      arm: 'c'
  },
  { 
    id: '3', 
    name: 'Team Group', 
    avatar: '👥', 
     surname: 'blessing',
    firstName: 'miracle',
    otherName: 'Mike',
    studentId: 'STU/2024/001',
    class: 'JSS 3',
    field: 'science',
    gender: 'M',
    dob: '2010-05-02',
    guardian: 'mr. james doe',
    phone: '08035688732',
    attendance: 87,
    gpa: 3.8,
    subjects: 12,
    performance: 78,
    term: '2nd term',
    status: 'Active',
     email: 'vany17@gmail.com',
     lga: 'lagos',
      arm: 'a'
  },
  { 
    id: '4', 
    name: 'Mom', 
    avatar: '❤️', 
     surname: 'gaphel',
    firstName: 'balerinaafe',
    otherName: 'Mike',
    studentId: 'STU/2024/001',
    class: 'JSS 3',
    field: 'science',
    gender: 'F',
    dob: '2010-05-02',
    guardian: 'mr. james doe',
    phone: '08035688732',
    attendance: 87,
    gpa: 3.8,
    subjects: 12,
    performance: 78,
    term: '2nd term',
    status: 'Active',
     email: 'vany17@gmail.com',
     lga: 'ibono',
      arm: 'a'
  },
    { 
    id: '5', 
    name: 'John Doe', 
    surname: 'john',
    firstName: 'Doe',
    otherName: 'Mike',
    avatar: '👤',   
    studentId: 'STU/2024/001',
    class: 'JSS 3',
    field: 'science',
    gender: 'F',
    dob: '2010-05-02',
    guardian: 'mr. james doe',
    phone: '08035688732',
    attendance: 87,
    gpa: 3.8,
    subjects: 12,
    performance: 78,
    term: '2nd term',
    status: 'Active',
     email: 'vany17@gmail.com',
     lga: 'uyo',
      arm: 'b'
  },
    { 
    id: '6', 
    name: 'John Doe', 
    avatar: '👤', 
     surname: 'grace',
    firstName: 'tally',
    otherName: 'Mike',
    studentId: 'STU/2024/001',
    class: 'JSS 3',
    field: 'science',
    gender: 'M',
    dob: '2010-05-02',
    guardian: 'mr. james doe',
    phone: '08035688732',
    attendance: 87,
    gpa: 3.8,
    subjects: 12,
    performance: 78,
    term: '2nd term',
    status: 'Active',
     email: 'vany17@gmail.com',
     lga: 'affort',
      arm: 'a'
  },
  { 
    id: '15', 
    name: 'Team Group', 
    avatar: '👥', 
    surname: 'mark',
    firstName: 'dolly',
    otherName: 'Mike',
    studentId: 'STU/2024/001',
    class: 'JSS 3',
    field: 'science',
    gender: 'M',
    dob: '2010-05-02',
    guardian: 'mr. james doe',
    phone: '08035688732',
    attendance: 87,
    gpa: 3.8,
    subjects: 12,
    performance: 78,
    term: '2nd term',
    status: 'Active',
     email: 'vany17@gmail.com',
     lga: 'etoi',
     arm: 'a'
  },
])

const displayConfig = {
  avatar: 'avatar',
  title: 'name',
 
  timestamp: 'lastSeen'
}


</script>

<template>
  <MyAccordion
    :items="contacts" 
    :display-config="displayConfig"
    key-field="id"
    :tabs="[
      {key:'Analytics', label:'Analytics'},
      {key: 'Dashboard', label:'Dashboard'}
    ]"
  >


<template #accordion-body="{ item }">
  <AssignTeacher :item="item" :current-user="currentUser" />
</template>

     <template #profile ="{ item }">
      <div class="flex-1 overflow-y-auto pt-2 flex justify-center">
       <MobileProfilePanel :item="item"  :current-user="currentUser" />
      </div>
  
    </template>


     <template #Analytics="{ item, close }">
    <MobileProfilePanel :item="item" :current-user="currentUser" />
    </template>

    <template #Dashboard="{ item}">
   <AssignTeacher :item="item" />
    </template>

     <template #fullView="{ items}">
     <Student :items="items" />
    </template>

   
  </MyAccordion>
</template>