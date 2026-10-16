
<script setup>

import {ref, reactive, computed, watch} from 'vue'
import getVisibleFields from '../utils/getVisibleFields';


const props = defineProps({
  item: { type: Object, required: true,  default: ()=>({}), },
 // objectFields: {type: Array, default:()=>[]},
 // editableObjectFields: {type: Array, default: ()=>[]},
  currentUser: {type: Object, required: true, },
  defaultVisibleFields: {type: Array, default: ()=>[]}
})

const editMode = ref(false)
const editedMap = reactive(new Map())
const editableStudent = reactive({})




const studentFields =  [
  {key: 'gender', label: 'Gender', type: 'select',
   options: [
    {value: 'Male', label: 'Male'},
    {value: 'Female', label: 'Female'}
  ]},
  {key: 'dob', label: 'Date of Birth', type:'date'},
  {key: 'guardian', label: 'Guardian', type: 'text'},
  {key: 'phone', label: 'Phone', type: 'text'}, 
  {key: 'term', label: 'Term', type: 'text'},
  {key: 'email', label: 'Email', type: 'text'},
]

const editableStudentFields = computed(()=>{
  if(props.currentUser.role === 'admin' || props.currentUser.role=== 'teacher'){
    return studentFields
  }
   return[
  {key: 'email', label: 'Email' , type: 'text'},
  {key: 'phone', label: 'Phone', type: 'text'},
]
})

const initializeForm = ()=>{
  if(!props.item) return
  editableStudentFields.value.forEach(field =>{
    editableStudent[field?.key]= props.item[field?.key] ?? ''
  })
  editedMap.clear()
}

watch(()=> props.item?.id, (newId, oldId)=>{
  if(newId !== oldId) initializeForm()
}, {immediate: true})

const markAsEdited = (key) => {
  const original = props.item[key] ?? ''
  const current = editableStudent[key]
  current !== original ? editedMap.set(key, current) : editedMap.delete(key)
}

const editedObjectKeys = computed(()=> [...editedMap.keys()])
const editedObject = computed(()=> Object.fromEntries(editedMap))
const cancelEdit = ()=> initializeForm()
const submitChanges = ()=> {
  console.log('submiting')
  editMode.value = false
}

const visibleFields = computed(()=> {
  if(!props.item || !props.currentUser) return []
  return getVisibleFields({
    fields: studentFields,
    targetObject: props.item,
    currentUser: props.currentUser,
    defaultVisibleFields: props.defaultVisibleFields
  })
})
</script>

<template>
  <!-- NO wrapper div — gradient lives directly on this element -->
  <div
    class="relative w-full overflow-hidden p-4"
    style="background: linear-gradient(135deg, #075e54 0%, #0a8a78 40%, #128c7e 70%, #1a3a35 100%)"
  >
 
    
    <!-- Content — full width, no max-w-xs -->
    <div class="relative z-10 w-full">

      <!-- Avatar + Name row -->
      <div class="flex items-center gap-3 mb-4">
        <div class="shrink-0 rounded-full"
          style="box-shadow: 0 0 0 3px rgba(255,255,255,0.2), 0 0 20px rgba(37,211,102,0.3)">
          <div class="w-14 h-14 rounded-full flex items-center justify-center text-2xl"
            style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2)">
            {{ item?.avatar ?? '👤' }}
          </div>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between gap-2">
            <h2 class="text-white text-base font-bold leading-tight truncate">
              {{ item?.name }}
            </h2>
            <span class="shrink-0 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold"
              style="background: rgba(37,211,102,0.2); border: 1px solid rgba(37,211,102,0.3); color: #a8f0c6">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 inline-block"></span>
              {{ item?.status ?? 'Active' }}
            </span>
          </div>
          <p class="text-xs mt-0.5" style="color: rgba(255,255,255,0.5)">
            {{ item?.class }} · {{ item?.stream }}
          </p>
          <p class="text-[10px] font-mono mt-0.5" style="color: rgba(255,255,255,0.3)">
            {{ item?.studentId }}
          </p>
        </div>
      </div>

      <!-- Stats row — maintain their design -->
      <div class="flex gap-2 mb-4">
        <div
          v-for="stat in [
            { value: item?.attendance + '%', label: 'Attendance' },
            { value: item?.gpa,             label: 'GPA' },
            { value: item?.subjects,        label: 'Subjects' },
          ]"
          :key="stat.label"
          class="flex-1 text-center rounded-xl py-2"
          style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12)"
        >
          <p class="text-white text-base font-bold leading-none">{{ stat.value }}</p>
          <p class="text-[10px] mt-1" style="color: rgba(255,255,255,0.4)">{{ stat.label }}</p>
        </div>
      </div>

      

    <fieldset class="rounded-2xl  mb-4 bg-gray-100 max-w-md overflow-y-auto" 
    style="border: 2px solid rgba(255,255,255,0.12) ; max-height: 280px">


    <legend @click="editMode = !editMode" class=" mx-4 px-4 text-[0.72rem] 
      font-semibold py-0.5 px-4 rounded-full cursor-pointer
      mb-3 transition-all duration-150" style="background: #D4AF37; 
      border: 1px solid  #075e54; color:  #075e54; margin-inline: auto">
    {{ editMode? 'View profile' : 'Edit Profile' }}  
    </legend>

     <!--  view panel-->
      <div v-if="!editMode">
        <div v-for="(field, i) in visibleFields" :key="field.key" class="flex items-center py-1.5"
        :style="i < visibleFields.length - 1 ? 'border-bottom: 1px solid rgba(225, 225, 255, 0.07)' :
        '' ">
        <span class="text-[16px] uppercase tracking-wider font-medium text-right pr-3 shrink-0 pl-2 rounded-l-md 
        py-1 w-[50%] text-[#D4AF37]" style="background: linear-gradient(135deg, #075e54 0%, #0a8a78 40%, #128c7e 100%);">
         {{ field.label }}:
      </span>

      <span class="text-[16px] font-medium rounded-r-md pl-3 bg-gray-200 py-1 text-[#075e54] flex-1 shrink-0">
        {{ field.options ? field.options.find(o => o.value === item[field.key]) ?.label || 
        item[field.key] : item[field.key] }}
      </span>
        </div>

      </div>


     <!-- edit panel-->
      <div v-else>


        <div v-for="(field, i) in editableStudentFields" :key="field.key"
        class="flex items-center py-1.5" :style="i < editableStudentFields.length - 1 ? 
         'border-bottom: ipx solid rgba(255, 255,255,0.07)' : ''">
        <span class="text-[16px] uppercase tracking-wider font-medium text-right pr-3 shrink-0 pl-2 rounded-l-md
        py-1 w-[50%] text-[#D4AF37]" style="background: linear-gradient(135deg, #075e54 0%, #0a8a78 40%, #128c7e 100%);">
      {{ field.label }} :
        </span>

        <span class="text-[14px] font-medium rounded-r-md pl-3  
        bg-gray-100 py-1 text-[#075e54] flex-1 shrink-0 min-w-0 overflow-hidden "
        style="border: 1px solid  #075e54;">
          <input 
          v-if="['text', 'email', 'number'].includes(field.type)"
          v-model="editableStudent[field.key]"
          :type="field.type"
          :placeholder="field.label"
          class="w-full bg-transparent outline-none h-full  text-[#075e54] placeholder-[#075e54]/40"
          @change="markAsEdited(field.key)"
          />


          <input 
          v-else-if="['date'].includes(field.type)"
          v-model="editableStudent[field.key]"
          :type="field.type"
          :placeholder="field.label"
          class="w-full bg-transparent outline-none h-full  text-[#075e54] placeholder-[#075e54]/40"
          @change="markAsEdited(field.key)"
          />

          <select
          v-else-if="field.type === 'select'"
          v-model="editableStudent[field.key]"
          class="w-full bg-transparent outline-none text-xs text-[#075e54]"
          @change="markAsEdited(field.key)"
          >
          <option v-for="option in field.options" :key="option.value"
          :value="option.value">
          {{ option.label }}
        </option>
        
        </select>
        </span>
        
        </div>

        <!-- cancel button -->
         <div class="flex justify-end gap-2 px-3 py-2"
         style="border-top: 1px solid rgba(255, 255, 255, 0.07)">
        <button
      type="button" 
         @click="cancelEdit"
         class="text-[0.72rem] font-semibold px-3 py-1 rounded-lg transition-all duration-150"
         style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.15);
         color: rgba(255, 255, 255, 0.6)">
         cancel
        </button>

        <button 
        type="button"
        @click="submitChanges"
        :disabled="editedObjectKeys.length === 0"
        class="text-[0.72rem] font-semibold px-3 py-1 rounded-lg transition-all duration-150 
        disabled:opacity-30 disabled:cursor-not-allowed"
        style="background: rgba(37,211,102,0.2); border: 1px solid rgba(37,211,102,0.3); color:#a8f0c6"
        >
      Save Changes
      </button>
    </div>


      </div>

    </fieldset>

      <!-- Info rows — hymn book alignment -->
      <!-- <div class="rounded-2xl px-3 py-2 mb-4 bg-gray-100"
        style=" border: 2px solid rgba(255,255,255,0.12) ; ">
       

         <div
          v-for="(row, i) in infoRows(item)"
          :key="row.label"
          class="flex items-center py-1.5 "
          :style="     i < infoRows(item).length - 1 ? 'border-bottom: 1px solid rgba(255,255,255,0.07)' : ''"
          
          > -->
          <!-- Label — right aligned, fixed width -->
          <!-- <span
            class="text-[11px]  uppercase tracking-wider font-medium text-right pr-3 shrink-0
            pl-2 rounded-l-md py-1 w-[150px] text-[#D4AF37] "
            style="background: linear-gradient(135deg, #075e54 0%, #0a8a78 40%, #128c7e 100%)"
  
          >
            {{ row.label + " :" }} 
          </span> -->

      
<!-- Value — left aligned -->
          <!-- <span class=" text-xs font-medium  rounded-r-md pl-3 bg-gray-300 py-1 text-[#075e54] 
           flex-1 shrink-0 " >
            {{ row.value }}
          </span>
        </div>

       
      
      </div> -->

      <!-- Performance bar -->
      <div>
        <div class="flex items-center justify-between mb-1.5">
          <span class="text-[10px] uppercase tracking-wider font-medium"
            style="color: rgba(255,255,255,0.75)">
            Performance
          </span>
          <span class="text-xs font-semibold text-emerald-400">{{ item?.performance }}%</span>
        </div>
        <div class="h-1 rounded-full overflow-hidden" style="background: rgba(255,255,255,0.15)">
          <div
            class="h-full rounded-full transition-all duration-1000"
            style="background: linear-gradient(90deg, #25d366, #a8f0c6)"
            :style="{ width: item?.performance + '%' }"
          ></div>
        </div>
      </div>

    </div>
  </div>
</template>