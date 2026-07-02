// /stores/useComponentStore.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import {useMainStore} from '/stores'

>
export const useClassStore= defineStore('classStore', () => {
  const currentClassComponent = ref('DefaultClassView')
   const mainStore = useMainStore();
  
  const formFields = (type) => {
  if (type === 'student') {
    return [
      { key: 'name', label: 'Full Name', type: 'text', editableBy: ['teacher', 'admin'] },
      { key: 'nickname', label: 'Nickname', type: 'text', editableBy: ['student', 'teacher', 'admin'] },
      { key: 'email',label: 'Email', type: 'email', editableBy: ['admin'] },
      { key: 'gender', label: 'Gender', type: 'select', editableBy: ['admin'],
        options: [
          { value: 'M', label: 'Male' },
          { value: 'F', label: 'Female' }
        ]
      },
      { key: 'gracePage', label: 'Grace Page', type: 'text', editableBy: ['student'] }
    ]
  }
  return []
}
  const objects = ref([])
  const student = ref({})
  const setStudent = (data) =>{
    student.value = data
  }
  const studentFields = ref(formFields('student'))
  const editableStudentFields = computed(() =>
  studentFields.value.filter(field => field.editableBy?.includes(auth.role.value))
  )
  
  //with Editable fields only
  const editableStudent= ref(
  Object.fromEntries(
    editableStudentFields.map(field => [field.key, student[field.key]])
  )
)
  
  const markAsEdited = (employee, field) => {
  if (!editedEmployees.has(employee.id)) {
    editedEmployees.set(employee.id, {});
  }
  editedEmployees.get(employee.id)[field] = employee[field];
};
  
  
  const showBackButton = computed(() =>
    currentClassComponent.value !== 'DefaultClassView'
  )

  function load(name) {
    currentClassComponent.value = name
  }
  
 function  reset() {
      currentClassComponent.value= 'DefaultClassView'
      showBackButton = false
      // Remove dummy slide state
      if (history.state?.slideView) {
        // Do a "go forward to fake state" with replaceState to clean it
        window.history.replaceState({}, '')
      }
    }
    
  
  // carouelel auto play slide controller
    const activeCarousels = ref(new Map());
  const timer = ref(null);
  const interval = ref(5000);

  const register = (id, nextFn, customInterval) => {
    activeCarousels.value.set(id, nextFn);
    if (customInterval) interval.value = customInterval;
    ensureRunning();
  };

  const unregister = (id) => {
    activeCarousels.value.delete(id);
    if (activeCarousels.value.size === 0) stop();
  };

  const ensureRunning = () => {
    if (!timer.value && activeCarousels.value.size > 0) {
      timer.value = setInterval(() => {
        activeCarousels.value.forEach((next) => next());
      }, interval.value);
    }
  };

  const stop = () => {
    if (timer.value) {
      clearInterval(timer.value);
      timer.value = null;
    }
  };

  return {
    currentClassComponent,
    showBackButton,
    load,
    stop,
    register,
    unregister,
    rese, 
    register
  }
})






