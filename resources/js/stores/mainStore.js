import { defineStore } from 'pinia'
import { ref, computed, reactive } from 'vue'
import { gsap } from 'gsap'
import { useRouter, useRoute } from 'vue-router'
import { useBreakpoints, breakpointsTailwind } from '@vueuse/core'
import axios from 'axios'

export const useMainStore = defineStore('mainStore', () => {
  const route = useRoute()
  const router = useRouter()

  // ============ AUTH & USER ============
  const userName = ref('')
  const password = ref('')
  const isLoggedIn = ref(false)
  const loginCredential = computed(() => ({ userName: userName.value, password: password.value }))
  
  const authData = ref(null) // NOT reactive, just ref
  const authUser = computed(() => authData.value?.user || null)
  const students = computed(() => authData.value?.students || [])
  const teachersSubjectClass = computed(() => authData.value?.teachersSubjectClass || [])
  const employees = computed(() => authData.value?.employees || [])

  // ============ UI STATE ============
  const welcomeAnimation = ref(null)
  const error = ref(false)
  const isLoading = ref(false)
  const schools = ref([])
  const userGrade = ref([])
  const breakpoints = useBreakpoints(breakpointsTailwind)
  const isMobile = breakpoints.smaller('md')



  // ============ CAROUSEL & TABS ============
  const activeSlide = ref('Dashboard') // Slide NAME not index

  // ============ SELECTION & EDITING ============
  const editMode = ref(false)
  const selectionMode = ref(false)
  const showDetails = ref(false)
  
  // Use Map for selectedItems (key = item ID, value = item object)
  const selectedItems = reactive(new Map())
  const selectedItem = ref(null)
  const editedItems = reactive(new Map())

  // ============ VIEW MODES ============
  const attendanceMode = ref(false)
  const isDetailView = ref(false)
  const isFullView = ref(false)

  // ============ CLASS & SUBJECT DATA ============
  const mySchoolClasses = computed(() => {
    // Get unique classes from students
    if (!students.value.length) return []
    const classMap = new Map()
    students.value.forEach(s => {
      if (s.classId && s.className && !classMap.has(s.classId)) {
        classMap.set(s.classId, { id: s.classId, name: s.className })
      }
    })
    return Array.from(classMap.values())
  })

  const activeClassSubjects = ref([])

  const lgas = [
    {id: 1, name: 'ikeja',},
    {id: 2, name: 'surulere'},
    {id: 3, name: 'eti-osa'},
  ]

  const arms = [
    {id: 'A', name: 'Arm A'},
    {id: 'B', name: 'Arm B'},
    {id: 'C', name: 'Arm C'},
  ]

  // ============ ACTIONS: SELECTION, MOSTLY FOR MyAccordion ============
  const toggleSelection = (item, keyField = 'id') => {
    const itemKey = item[keyField]
    if (selectedItems.has(itemKey)) {
      selectedItems.delete(itemKey)
    } else {
      selectedItems.set(itemKey, item)
    }
    if(selectedItems.size === 0 //&& attendanceMode.value === false
      ){          selectionMode.value = false;
     
    }
    
    // Update selectedItem to first selected or null
    const firstSelected = selectedItems.values().next().value
    selectedItem.value = firstSelected || null
  }

  const clearSelection = () => {
    selectedItems.clear()
    selectedItem.value = null
    selectionMode.value = false
  }

  const getSelectedArray = () => {
    return Array.from(selectedItems.values())
  }

  const setProfile = (item) => {
    selectedItem.value = item
  }

  // ============ ACTIONS: EDITING ============
  const editSelectedItem = (item) => {
    editMode.value = true
    selectedItem.value = item
  }

  const markAsEdited = (item, field, keyField = 'id') => {
    const itemKey = item[keyField]
    if (!editedItems.has(itemKey)) {
      editedItems.set(itemKey, {})
    }
    editedItems.get(itemKey)[field] = item[field]
  }

  // ============ GENERIC: SAVE — create or update, single or array ============
const saveItems = async (url, payload, targetArray, keyField = 'id') => {
  isLoading.value = true
  error.value = false
  const items = Array.isArray(payload) ? payload : [payload]

  try {
    const { data } = await axios.post(url, { items })
    const returned = Array.isArray(data) ? data : [data]

    returned.forEach((object) => {
      const index = targetArray.findIndex(i => i[keyField] === object[keyField])
      if (index !== -1) {
        targetArray[index] = { ...targetArray[index], ...object }
      } else {
        targetArray.push(object)
      }
    })

    editedItems.clear()
    editMode.value = false
    return returned
  } catch (err) {
    error.value = true
    console.error(`save to ${url} failed:`, err)
  } finally {
    isLoading.value = false
  }
}


  // ============ ACTIONS: DELETION ============
 // Delete — one, some, or all. ids for specific rows, all:true skips
// id-enumeration entirely so the backend can run a single bulk DELETE.
const deleteItems = async (url, targetArray, { ids = [], all = false } = {}) => {
  isLoading.value = true
  error.value = false
  try {
    await axios.delete(url, { data: all ? { all: true } : { ids } })

    if (all) {
      targetArray.splice(0, targetArray.length)
    } else {
      targetArray.splice(0, targetArray.length, ...targetArray.filter(item => !ids.includes(item.id)))
    }
    clearSelection()
  } catch (err) {
    error.value = true
    console.error(`delete to ${url} failed:`, err)
  } finally {
    isLoading.value = false
  }
}

// ============ GENERIC: FETCH — one, some, or all ============
const fetchItems = async (url, targetArray, ids = null) => {
  isLoading.value = true
  error.value = false
  try {
    const params = ids ? { ids: ids.join(',') } : {}
    const { data } = await axios.get(url, { params })
    targetArray.splice(0, targetArray.length, ...data)
    return data
  } catch (err) {
    error.value = true
    console.error(`fetch from ${url} failed:`, err)
  } finally {
    isLoading.value = false
  }
}

  // ============ ACTIONS: ATTENDANCE ============
  const registerAbsentees = async () => {
    error.value = false
    const absentees = Array.from(selectedItems.keys())
    isLoading.value = true
    error.value = false
    try{
      await $fetch(apiUrl, {
        method: 'POST',
        body: { absentees, date: new Date().toISOString()}
      })
      clearSelection()

    } catch(err){
      error.value = true 
      console.error('regiter absentess error:', err)
    }finally{
      isLoading.value = false
    }
    
  }

  const toggleAttendanceMode = () => {
  if (attendanceMode) {
    // Cancel — exit everything, clear selections
    attendanceMode = false
    selectionMode = false
    selectedItems.clear()
  } else {
    // Enter attendance mode
      selectedItems.clear()
      attendanceMode = true
      selectionMode = true
    //viewType.value = 'list'
  
  }
}

  //==========API HELPER==========
  const apiCall = async (url, options = {}) =>{
    isLoading.value = true
    error.value = false

    try{
      const response = await $fetch(url, {
        method: options.body || undefined,
        ...options
      })
      return { data: response, error: null }
    }catch(err){
      error.value = true
      console.error(`API call to  ${url} failed:`, err)
      return { data: null, error: err}
    }finally{
      isLoading.value= false
    }
  }

  /* usage 
  const addStudent = async(studentData) => {
    const{data,error} = await apiCall('/api/students',{
    method: 'POST',
    body: studentData})
    if(data) {
    student.value.push(data)} */

  // ============ ACTIONS: RIBBON MENU ============
  const addStudent = () => {
    console.log('Add student')
    // TODO: Implement add student logic
  }

  const refreshStudents = () => {
    console.log('Refresh students')
    // TODO: Implement refresh logic
  }

  const activeSelectedClass = (classId) => {
    console.log('Set class:', classId)
    // TODO: Implement class selection logic
  }

  const activeSelectedSubject = (subjectId) => {
    console.log('Set subject:', subjectId)
    // TODO: Implement subject selection logic
  }

  const dateRange = (classId) => {
    console.log('Date range for class:', classId)
    // TODO: Implement date range logic
  }

  const switchTab = (slideName) => {
    activeSlide.value = slideName
    clearSelection()
    editMode.value = false
  }

  // ============ COMPUTED: RIBBON MENU ============
  const ribbonMenu = computed(() => {
    const selectedCount = selectedItems.size

    // If items are selected, show selection actions
    if (selectedCount > 0) {
      const buttons = []

      if (selectedCount === 1) {
        buttons.push({
          icon: 'bi-pencil',
          title: 'Edit',
          action: () => editSelectedItem(getSelectedArray()[0]),
          variant: 'btn-outline-primary'
        })
      }

      buttons.push({
        icon: 'bi-trash',
        title: `Delete ${selectedCount > 1 ? 'Items' : 'Item'}`,
        action: () => deleteSelectedItems(getSelectedArray()),
        variant: 'btn-danger'
      })

      buttons.push({
        icon: 'bi-x',
        title: 'Cancel',
        action: clearSelection,
        variant: 'btn-light'
      })

      return buttons
    }

    // Default dropdown options
    const defaultIcons = [
      {
        icon: '',
        text: 'Set Class',
        isDropdown: true,
        variant: 'btn-outline-light',
        options: mySchoolClasses.value.map((clas) => ({
          label: clas.name,
          action: () => activeSelectedClass(clas.id)
        }))
      },
      {
        icon: '',
        text: 'Set Date',
        isDropdown: true,
        variant: 'btn-outline-light',
        options: [] // TODO: Implement date options
      },
      {
        icon: '',
        text: 'Active Subject',
        isDropdown: true,
        variant: 'btn-outline-light',
        options: activeClassSubjects.value.map((subject) => ({
          label: subject.name,
          action: () => activeSelectedSubject(subject.id)
        }))
      }
    ]

    // Slide-specific buttons
    switch (activeSlide.value) {
      case 'Dashboard':
        return [
          {
            icon: 'bi-person-plus',
            title: 'Add Student',
            action: addStudent,
            variant: 'btn-success'
          },
          {
            icon: 'bi-arrow-clockwise',
            title: 'Refresh',
            action: refreshStudents,
            variant: 'btn-outline-light'
          }
        ]

      case 'My Class':
      case 'Staff':
      case 'Grade':
      case 'CBT':
        return [
          ...defaultIcons,
          {
            icon: '',
            text: 'Take Attendance',
            variant: 'btn-outline-light',
            action: () => {
              attendanceMode.value = !attendanceMode.value
            }
          },
          {
            icon: '',
            text: isDetailView.value ? 'Detail View' : 'Result View',
            variant: 'btn-outline-light',
            action: () => {
              isDetailView.value = !isDetailView.value
            }
          },
          {
            icon: '',
            text: isFullView.value ? 'Full View' : 'Mini View',
            variant: 'btn-outline-light',
            action: () => {
              isFullView.value = !isFullView.value
            }
          },
          {
            icon: 'i-arrow-clockwise',
            text: '',
            variant: 'btn-outline-light',
            action: () => {
              isDetailView.value = !isDetailView.value
            }
          }
        ]

      default:
        return [
          {
            icon: 'bi-arrow-clockwise',
            title: 'Refresh',
            action: () => alert('Refreshing...'),
            variant: 'btn-outline-light'
          }
        ]
    }
  })

  // ============ HELPER FUNCTIONS ============
  const getUniqueStudent = (studentId) => {
    return computed(() => students.value.find((s) => s.id === studentId))
  }

  const getUniqueEmployee = (empId) => {
    return computed(() => employees.value.find((emp) => emp.id === empId))
  }

  const getStudentsByClassId = (classId) => {
    return computed(() => students.value.filter((student) => student.classId === classId))
  }

  // ============ ANIMATIONS ============
  const startAnimation = (welcomeTextRef) => {
    if (!welcomeTextRef?.value) return

    try {
      gsap.fromTo(
        welcomeTextRef.value,
        { textShadow: '3px 3px 5px red' },
        {
          textShadow: ['3px 3px 5px red', '3px 3px 5px green', '3px 3px 5px blue'],
          duration: 1,
          repeat: 3,
          ease: 'sine.inOut',
          yoyo: true,
          onComplete: () => {
            gsap.to(welcomeTextRef.value, {
              opacity: 0,
              duration: 1,
              onComplete: () => {
                welcomeAnimation.value = 1
              }
            })
          }
        }
      )
    } catch (error) {
      welcomeAnimation.value = 1
    }
  }

  // ============ EXPORTS ============
  return {
    // Auth & User
    userName,
    password,
    isLoggedIn,
    loginCredential,
    authData,
    authUser,
    students,
    teachersSubjectClass,
    employees,

    // UI State
    welcomeAnimation,
    error,
    isLoading,
    schools,
    userGrade,

    // Carousel
    activeSlide,

    // Selection & Editing
    editMode,
    selectionMode,
    showDetails,
    selectedItems,
    selectedItem,
    editedItems,

    // View Modes
    attendanceMode,
    isDetailView,
    isFullView,

    // Class & Subject
    mySchoolClasses,
    activeClassSubjects,

    // Computed
    ribbonMenu,

    // Actions
    switchTab,
    toggleSelection,
    clearSelection,
    getSelectedArray,
    setProfile,
    editSelectedItem,
    markAsEdited,
    saveEdit,
    deleteSelectedItems,
    registerAbsentees,
    addStudent,
    refreshStudents,
    activeSelectedClass,
    activeSelectedSubject,
    dateRange,

    // Helpers
    getUniqueStudent,
    getUniqueEmployee,
    getStudentsByClassId,

    // Animations
    startAnimation
  }
})