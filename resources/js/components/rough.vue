





1. fix the take attendance logic
if in attendance mode and the user deselects all selected it is observed that we are still in attendance mode but the checkbox is gone and selection is no longer possible. if user wants to go on he has to cancel and start attendance taking afresh
 
2. when in a mobile raw accordion view, tab should cause expansion and collapsing and displaying of profilepanel just like it would have been in mobile.

3. am hoping that  if i move most of my accordion method to the mainstore it will 
reduce the number of props in my accordion usage, again recall that i am using inject and provide  to bring the stoer

get a deassign panel  on the right  with list of  classes a certian  teahcer already  takes and the subjects  he takes in that class. clicking on subject deassign the teacher forthe  that subject and if theat if the only  subject it deassign the teacher for the class as a  whole
when save is clicked check the totall distinct subjects assigned and the previous already had. if it is  creter than 3 raise a promt that says this should be too much for this teacher. 3 distint subject do your can he really handle this.. or you  would like to  deassign some first.  the promp has two  butt, deassign, and  ok


















<script setup>
import { provide, ref, } from 'vue'
import { useMainStore } from '../stores/mainStore';
import PcProfilePanel from './PcProfilePanel.vue';
import MobileProfilePanel from './MobileProfilePanel.vue';
import MyAccordionNew from './MyAccordionNew.vue';
import AccordionNavBar from './AccordionNavBar.vue';
//import RowList1 from '../../../../../AppData/Local/Microsoft/Windows/INetCache/IE/QIUTIG15/RowList[1].vue';
import StudentRowList from './StudentRowList.vue';
import StudentListItem from './StudentListItem.vue';
// import ReusableAccordionList from './ReusableAccordionList.vue'
// import ChatAccordionBody from './ChatAccordionBody.vue'
// import ChatPCPanel from './ChatPCPanel.vue'


const store =  useMainStore()

provide('accordionStore', store)
// Sample contacts
const contacts = ref([
  { 
    id: '1', 
    name: 'John Doe', 
    avatar: '👤', 
    lastSeen: '10:30 AM',
    messages: [
      { id: 1, text: 'Hey, how are you?', time: '10:25 AM', sent: false },
      { id: 2, text: 'I\'m good! How about you?', time: '10:27 AM', sent: true },
      { id: 3, text: 'Doing great! Want to grab lunch?', time: '10:30 AM', sent: false },
    ],
    studentId: 'STU/2024/001',
    class: 'JSS 3',
    stream: 'science',
    gender: 'Male',
    dob: '14 Mar 2010',
    guardian: 'mr. james doe',
    phone: '08035688732',
    attendance: 87,
    gpa: 3.8,
    subjects: 12,
    performance: 78,
    term: '2nd term',
    status: 'Active',

  },
  { 
    id: '2', 
    name: 'Jane Smith', 
    avatar: '👩', 
    lastSeen: '9:15 AM',
    messages: [
      { id: 1, text: 'Meeting at 3pm today', time: '9:10 AM', sent: false },
      { id: 2, text: 'Got it, I\'ll be there', time: '9:12 AM', sent: true },
      { id: 3, text: 'Don\'t forget the presentation', time: '9:15 AM', sent: false },
    ],
    studentId: 'STU/2024/001',
    class: 'JSS 3',
    stream: 'science',
    gender: 'Male',
    dob: '14 Mar 2010',
    guardian: 'mr. james doe',
    phone: '08035688732',
    attendance: 87,
    gpa: 3.8,
    subjects: 12,
    performance: 78,
    term: '2nd term',
    status: 'Active',
  },
  { 
    id: '3', 
    name: 'Team Group', 
    avatar: '👥', 
    lastSeen: 'Yesterday',
    messages: [
      { id: 1, text: 'Who\'s coming to the event?', time: 'Yesterday', sent: false },
      { id: 2, text: 'I\'m in!', time: 'Yesterday', sent: true },
      { id: 3, text: 'Me too! Sounds good!', time: 'Yesterday', sent: false },
      { id: 4, text: 'Great, see you all there', time: 'Yesterday', sent: true },
      { id: 5, text: 'Great, see you all therejdiifdid', time: 'Yesterday', sent: true },
 
       { id: 6, text: 'Great, see you all therejdiifdid', time: 'Yesterday', sent: true },
        { id: 7, text: 'Great, see you all therejdiifdid', time: 'Yesterday', sent: true },
    ],
    studentId: 'STU/2024/001',
    class: 'JSS 3',
    stream: 'science',
    gender: 'Male',
    dob: '14 Mar 2010',
    guardian: 'mr. james doe',
    phone: '08035688732',
    attendance: 87,
    gpa: 3.8,
    subjects: 12,
    performance: 78,
    term: '2nd term',
    status: 'Active',
  },
  { 
    id: '4', 
    name: 'Mom', 
    avatar: '❤️', 
    lastSeen: 'Yesterday',
    messages: [
      { id: 1, text: 'Don\'t forget dinner tonight', time: 'Yesterday', sent: false },
      { id: 2, text: 'I won\'t mom! What time?', time: 'Yesterday', sent: true },
      { id: 3, text: '7pm, bring your appetite 😊', time: 'Yesterday', sent: false },
    ],
    studentId: 'STU/2024/001',
    class: 'JSS 3',
    stream: 'science',
    gender: 'Male',
    dob: '14 Mar 2010',
    guardian: 'mr. james doe',
    phone: '08035688732',
    attendance: 87,
    gpa: 3.8,
    subjects: 12,
    performance: 78,
    term: '2nd term',
    status: 'Active',
  },
    { 
    id: '5', 
    name: 'John Doe', 
    avatar: '👤', 
    lastSeen: '10:30 AM',
    messages: [
      { id: 1, text: 'Hey, how are you?', time: '10:25 AM', sent: false },
      { id: 2, text: 'I\'m good! How about you?', time: '10:27 AM', sent: true },
      { id: 3, text: 'Doing great! Want to grab lunch?', time: '10:30 AM', sent: false },
    ],
    studentId: 'STU/2024/001',
    class: 'JSS 3',
    stream: 'science',
    gender: 'Male',
    dob: '14 Mar 2010',
    guardian: 'mr. james doe',
    phone: '08035688732',
    attendance: 87,
    gpa: 3.8,
    subjects: 12,
    performance: 78,
    term: '2nd term',
    status: 'Active',
  },
    { 
    id: '6', 
    name: 'John Doe', 
    avatar: '👤', 
    lastSeen: '10:30 AM',
    messages: [
      { id: 1, text: 'Hey, how are you?', time: '10:25 AM', sent: false },
      { id: 2, text: 'I\'m good! How about you?', time: '10:27 AM', sent: true },
      { id: 3, text: 'Doing great! Want to grab lunch?', time: '10:30 AM', sent: false },
    ],
    studentId: 'STU/2024/001',
    class: 'JSS 3',
    stream: 'science',
    gender: 'Male',
    dob: '14 Mar 2010',
    guardian: 'mr. james doe',
    phone: '08035688732',
    attendance: 87,
    gpa: 3.8,
    subjects: 12,
    performance: 78,
    term: '2nd term',
    status: 'Active',
  },
    { 
    id: '7', 
    name: 'John Doe', 
    avatar: '👤', 
    lastSeen: '10:30 AM',
    messages: [
      { id: 1, text: 'Hey, how are you?', time: '10:25 AM', sent: false },
      { id: 2, text: 'I\'m good! How about you?', time: '10:27 AM', sent: true },
      { id: 3, text: 'Doing great! Want to grab lunch?', time: '10:30 AM', sent: false },
    ],
    studentId: 'STU/2024/001',
    class: 'JSS 3',
    stream: 'science',
    gender: 'Male',
    dob: '14 Mar 2010',
    guardian: 'mr. james doe',
    phone: '08035688732',
    attendance: 87,
    gpa: 3.8,
    subjects: 12,
    performance: 78,
    term: '2nd term',
    status: 'Active',
  },
    { 
    id: '8', 
    name: 'John Doe', 
    avatar: '👤', 
    lastSeen: '10:30 AM',
    messages: [
      { id: 1, text: 'Hey, how are you?', time: '10:25 AM', sent: false },
      { id: 2, text: 'I\'m good! How about you?', time: '10:27 AM', sent: true },
      { id: 3, text: 'Doing great! Want to grab lunch?', time: '10:30 AM', sent: false },
    ],
    studentId: 'STU/2024/001',
    class: 'JSS 3',
    stream: 'science',
    gender: 'Male',
    dob: '14 Mar 2010',
    guardian: 'mr. james doe',
    phone: '08035688732',
    attendance: 87,
    gpa: 3.8,
    subjects: 12,
    performance: 78,
    term: '2nd term',
    status: 'Active',
  },
    { 
    id: '9', 
    name: 'John Doe', 
    avatar: '👤', 
    lastSeen: '10:30 AM',
    messages: [
      { id: 1, text: 'Hey, how are you?', time: '10:25 AM', sent: false },
      { id: 2, text: 'I\'m good! How about you?', time: '10:27 AM', sent: true },
      { id: 3, text: 'Doing great! Want to grab lunch?', time: '10:30 AM', sent: false },
    ],
    studentId: 'STU/2024/001',
    class: 'JSS 3',
    stream: 'science',
    gender: 'Male',
    dob: '14 Mar 2010',
    guardian: 'mr. james doe',
    phone: '08035688732',
    attendance: 87,
    gpa: 3.8,
    subjects: 12,
    performance: 78,
    term: '2nd term',
    status: 'Active',
  },
    { 
    id: '10', 
    name: 'John Doe', 
    avatar: '👤', 
    lastSeen: '10:30 AM',
    messages: [
      { id: 1, text: 'Hey, how are you?', time: '10:25 AM', sent: false },
      { id: 2, text: 'I\'m good! How about you?', time: '10:27 AM', sent: true },
      { id: 3, text: 'Doing great! Want to grab lunch?', time: '10:30 AM', sent: false },
    ],
    studentId: 'STU/2024/001',
    class: 'JSS 3',
    stream: 'science',
    gender: 'Male',
    dob: '14 Mar 2010',
    guardian: 'mr. james doe',
    phone: '08035688732',
    attendance: 87,
    gpa: 3.8,
    subjects: 12,
    performance: 78,
    term: '2nd term',
    status: 'Active',
  },
    { 
    id: '11', 
    name: 'John Doe', 
    avatar: '👤', 
    lastSeen: '10:30 AM',
    messages: [
      { id: 1, text: 'Hey, how are you?', time: '10:25 AM', sent: false },
      { id: 2, text: 'I\'m good! How about you?', time: '10:27 AM', sent: true },
      { id: 3, text: 'Doing great! Want to grab lunch?', time: '10:30 AM', sent: false },
    ],
    studentId: 'STU/2024/001',
    class: 'JSS 3',
    stream: 'science',
    gender: 'Male',
    dob: '14 Mar 2010',
    guardian: 'mr. james doe',
    phone: '08035688732',
    attendance: 87,
    gpa: 3.8,
    subjects: 12,
    performance: 78,
    term: '2nd term',
    status: 'Active',
  },
    { 
    id: '12', 
    name: 'John Doe', 
    avatar: '👤', 
    lastSeen: '10:30 AM',
    messages: [
      { id: 1, text: 'Hey, how are you?', time: '10:25 AM', sent: false },
      { id: 2, text: 'I\'m good! How about you?', time: '10:27 AM', sent: true },
      { id: 3, text: 'Doing great! Want to grab lunch?', time: '10:30 AM', sent: false },
    ],
    studentId: 'STU/2024/001',
    class: 'JSS 3',
    stream: 'science',
    gender: 'Male',
    dob: '14 Mar 2010',
    guardian: 'mr. james doe',
    phone: '08035688732',
    attendance: 87,
    gpa: 3.8,
    subjects: 12,
    performance: 78,
    term: '2nd term',
    status: 'Active',
  },
    { 
    id: '13', 
    name: 'John Doe', 
    avatar: '👤', 
    lastSeen: '10:30 AM',
    messages: [
      { id: 1, text: 'Hey, how are you?', time: '10:25 AM', sent: false },
      { id: 2, text: 'I\'m good! How about you?', time: '10:27 AM', sent: true },
      { id: 3, text: 'Doing great! Want to grab lunch?', time: '10:30 AM', sent: false },
    ],
    studentId: 'STU/2024/001',
    class: 'JSS 3',
    stream: 'science',
    gender: 'Male',
    dob: '14 Mar 2010',
    guardian: 'mr. james doe',
    phone: '08035688732',
    attendance: 87,
    gpa: 3.8,
    subjects: 12,
    performance: 78,
    term: '2nd term',
    status: 'Active',
  },
    { 
    id: '14', 
    name: 'John Doe', 
    avatar: '👤', 
    lastSeen: '10:30 AM',
    messages: [
      { id: 1, text: 'Hey, how are you?', time: '10:25 AM', sent: false },
      { id: 2, text: 'I\'m good! How about you?', time: '10:27 AM', sent: true },
      { id: 3, text: 'Doing great! Want to grab lunch?', time: '10:30 AM', sent: false },
    ],
    studentId: 'STU/2024/001',
    class: 'JSS 3',
    stream: 'science',
    gender: 'Male',
    dob: '14 Mar 2010',
    guardian: 'mr. james doe',
    phone: '08035688732',
    attendance: 87,
    gpa: 3.8,
    subjects: 12,
    performance: 78,
    term: '2nd term',
    status: 'Active',
  },
  { 
    id: '15', 
    name: 'Team Group', 
    avatar: '👥', 
    lastSeen: 'Yesterday',
    messages: [
      { id: 1, text: 'Who\'s coming to the event?', time: 'Yesterday', sent: false },
      { id: 2, text: 'I\'m in!', time: 'Yesterday', sent: true },
      { id: 3, text: 'Me too! Sounds good!', time: 'Yesterday', sent: false },
      { id: 4, text: 'Great, see you all there', time: 'Yesterday', sent: true },
      { id: 5, text: 'Great, see you all therejdiifdid', time: 'Yesterday', sent: true },
 
       { id: 6, text: 'Great, see you all therejdiifdid', time: 'Yesterday', sent: true },
        { id: 7, text: 'Great, see you all therejdiifdid', time: 'Yesterday', sent: true },
    ],
    studentId: 'STU/2024/001',
    class: 'JSS 3',
    stream: 'science',
    gender: 'Male',
    dob: '14 Mar 2010',
    guardian: 'mr. james doe',
    phone: '08035688732',
    attendance: 87,
    gpa: 3.8,
    subjects: 12,
    performance: 78,
    term: '2nd term',
    status: 'Active',
  },
])

const displayConfig = {
  avatar: 'avatar',
  title: 'name',
  subtitle: (item) => item.messages[item.messages.length - 1].text,
  timestamp: 'lastSeen'
}
</script>

<template>
  <MyAccordionNew
    :items="contacts" 
    :display-config="displayConfig"
    key-field="id"
    :tabs="[
      {key:'Analytics', label:'Analytics'},
      {key: 'Dashboard', label:'Dashboard'}
    ]"

    
  >


<template #accordion-body="{ item }">
  <MobileProfilePanel :item="item" />
 
    </template>

     <template #profile ="{ item }">
      <div class="flex-1 overflow-y-auto pt-2 flex justify-center">
       <PcProfilePanel :item="item"  />
      </div>
  
    </template>


     <template #Analytics="{ item, close }">
     <Grade :item="item" />
    </template>


     <template #nav-bar="{ store, allSelected, toggleSelectAll, 
      toggleAttendanceMode, viewType, setViewType, activeTab, setActiveTab,
      tabs }">
     <AccordionNavBar :store="store"
     :all-selected="allSelected"
     :view-type="viewType"
     :active-tab="activeTab"
     :tabs="tabs"
     @select-all="toggleSelectAll"
     @toggle-attendance="toggleAttendanceMode"
     @set-view="setViewType"
     @set-tab="setActiveTab" />
    </template>

  





    
    <template #list-item="{ item, isOpen, isSelected,
     isSelectionMode,toggleAccordion, toggleSelection
      , toggleCheckbox, getDisplayValue, itemRef
    }">


   <StudentListItem :item="item"
   :is-open="isOpen"
   :is-selected="isSelected"
   :is-selection-mode="isSelectionMode"
   :get-display-value="getDisplayValue"
   :item-ref="itemRef"
   @accordion="toggleAccordion"
   @select="toggleSelection"
   @checkbox="toggleCheckbox" />
 
    </template>
    

    <template #Dashboard="{ item}">
   <Grade :item="item" />
 
    </template>
    
   

   
  </MyAccordionNew>
</template>