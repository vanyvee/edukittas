<script setup>
import { ref, computed, nextTick, inject, watchEffect  } from 'vue'
import { useBreakpoints, breakpointsTailwind, onLongPress } from '@vueuse/core'
import { gsap } from 'gsap'
//import { useMainStore } from  "@/stores/mainStore";


import {CheckIcon, ChevronRightIcon, ClipboardDocumentCheckIcon} from '@heroicons/vue/24/outline'
// Props
const props = defineProps({
  items: { type: Array,required: true},
  keyField: { type: String, default: 'id'},
  displayConfig: {    type: Object,  required: true,    // Expected shape:
    // {
    //   avatar: 'avatar',      // field name for avatar
    //   title: 'name',         // field name for title
    //   subtitle: (item) => item.messages[item.messages.length - 1].text, // can be string or function
    //   timestamp: 'lastSeen'  // field name for timestamp
    // }
  },
  tabs:{
      type: Array, required: true, default: ()=>[]
      //shape [key:'analytics', lable: 'analytiv', icon: 'icon']
    }
})

// Store
const store = inject('accordionStore', {
  attendanceMode: false,
  selectionMode:false
})




// Local state

const openItemId = ref(null)
const selectedItem = ref(null)
const breakpoints = useBreakpoints(breakpointsTailwind)
 const isMobile= breakpoints.smaller('md')
 const listContainer = ref(null)
 const itemRefs = {}
 const emptyPanel = ref(null)
 const profilePanel = ref(null)
 const activeTab = ref(null)
const viewType = ref('list')
// Hover timer for thumbnail
const hoverTimers = ref({})
const showProfile = ref(false)

// Computed
const allSelected = computed(() => 
  store.selectedItems.size === props.items.length && props.items.length > 0
)

const defaultTab = computed(()=>props.tabs[0].key)
watchEffect(()=> {
  if(!activeTab.value && props.tabs?.length){
    activeTab.value = props.tabs[0].key
  }
})


const closePanel=()=>{
  selectedItem.value = null
  showProfile.value=false
  activeTab.value = defaultTab.value
      }
 //close profile and return to empty state
 const closeProfile = async()=> {
  if(profilePanel.value){
    gsap.to(profilePanel.value, {
      opacity: 0,
      scale: 0.97,
      y: 10,
      duration: 0.25,
      ease: 'power2.in',
      onComplete: ()=>closePanel()
    })
  }
 }
//wether profile panel should show or not
 

const setItemRef = (el, itemId, item) => {
  if(el && !itemRefs[itemId]){
    itemRefs[itemId]=el
    onLongPress(el, ()=>{
      store.selectionMode = true
      store.toggleSelection(item, props.keyField)
    }, {delay: 500})
  }
}

const toggleAttendanceMode = () => {
  if (store.attendanceMode) {
    // Cancel — exit everything, clear selections
    store.attendanceMode = false
    store.selectionMode = false
    
    store.selectedItems.clear()
  } else {
    // Enter attendance mode
      store.selectedItems.clear()
    store.attendanceMode = true
    store.selectionMode = true
    //viewType.value = 'list'
  
  }
}

const selectItem = async(item)=>{

  
  if(selectedItem.value){
    selectedItem.value = item
    if(activeTab.value === defaultTab.value){
    showProfile.value=true
  }
    return
  }
 
  
  if(selectedItem.value){
    if(profilePanel.value){
      gsap.to(profilePanel.value, {
        opacity:0,
        x:20,
        duration: 0.2,
        ease: 'power2.in',
        onComplete: async()=>{
          selectedItem.value = item
          await nextTick()
          gsap.fromTo(profilePanel.value,  {opacity: 0, x: 20},
           {opacity: 1, x: 0, duration: 0.3,
            ease: 'power2.out'
          }
          )
        }
      })
    }
     return
  }


const emptyEl = emptyPanel.value
 //animate empty panel out
 if(emptyEl){
  gsap.to(emptyEl, {
    opacity: 0,
    scale: 0.95,
    duration: 0.25,
    ease: 'power2.in',
  })
 }

  //set selected after short delay - let empty start fading 
 setTimeout(async ()=>{
  selectedItem.value = item
  await nextTick()
  // Animate profile panel in simultanously
  if(profilePanel.value){
    gsap.fromTo(profilePanel.value, 
      { opacity: 0, scale: 0.97, y: 10},
      {opacity: 1, scale: 1, y:0, duration: 0.4, ease: 'power3.out'}
    )
  }
 }, 150) //150ms - just enough for empty to start fading before profile appear

}

// Toggle accordion
const toggleAccordion = async(item) => {
  const itemId = item[props.keyField]
  
  if (store.selectionMode) {
   
    store.toggleSelection(item, props.keyField)
    return
  }
  
  // PC mode
  if (!isMobile.value) {
   
    selectItem(item) //selectedItem.value = item
    return
  }
  
  // Mobile mode - toggle accordion
  if (openItemId.value === itemId) {
    openItemId.value = null
    return
  } 

  // if another item is already open, close it first
  if(openItemId.value !== null ){
    openItemId.value = null
    //wait for the leave animation
    await new Promise(resolve => setTimeout(resolve, 520))
  }
  // opening - set open first, then scroll item to top
    openItemId.value = itemId
    await nextTick() //wait for DOM to update
    const itemEl = itemRefs[itemId]
    const container = listContainer.value
    if(itemEl && container){
      const containerTop = container.getBoundingClientRect().top
      const itemTop = itemEl.getBoundingClientRect().top
      const scrollOffset = container.scrollTop + (itemTop - containerTop)
      gsap.to(container, {
        scrollTop: scrollOffset,
        duration: 0.5,
        ease: 'power3.out',
      })
    }
  
}


// Enter selection mode
const toggleSelection = (item) => {
  store.selectionMode = true
  store.toggleSelection(item, props. keyField)
}

// Handle thumbnail click
// const handleThumbnailClick = (item) => {
//     toggleSelection
// }

// Handle thumbnail hover (desktop only)
const handleThumbnailMouseEnter = (itemId) => {
    if(!store.selectionMode){
     hoverTimers.value[itemId] = setTimeout(() => {
    console.log('hello')
  }, 1000)
    }
}

const handleThumbnailMouseLeave = (itemId) => {
  if (hoverTimers.value[itemId]) {
    clearTimeout(hoverTimers.value[itemId])
    delete hoverTimers.value[itemId]
  }
}

// Select all / Deselect all
const toggleSelectAll = () => {
  if (allSelected.value) {
    store.selectedItems.clear()
    store.selectionMode = false
  } else {
    props.items.forEach(item => {
      store.selectedItems.set(item[props.keyField], item)
    })
    
  }
}

// Helper to get display value
const getDisplayValue = (item, configKey) => {
  const config = props.displayConfig[configKey]
  if (typeof config === 'function') {
    return config(item)
  }
  return item[config]
}

// GSAP Animations
const onCheckboxEnter = (el) => {
  gsap.from(el, {
    scale: 0,
    opacity: 0,
    duration: 0.2,
    ease: 'back.out(1.7)'
  })
}

const onCheckboxLeave = (el, done) => {
  gsap.to(el, {
    scale: 0,
    opacity: 0,
    duration: 0.15,
    ease: 'power2.in',
    onComplete: done
  })
}



const onContentEnter = (el) => {
  gsap.from(el, {
    height: 0,
    opacity: 0,
    duration: 0.6,
    ease: 'power2.out',
    onComplete: ()=>{
        gsap.set(el, {height: 'auto'})
    }
  })
}

const onContentLeave = (el, done) => {
  gsap.to(el, {
    height: 0,
    opacity: 0,
    duration: 0.5,
    ease: 'power2.in',
    onComplete: done
  })
}
</script>

<template>
  <div class="flex flex-col md:flex-row w-full h-full bg-white">
    <!-- LEFT PANEL: List -->
    <div class="flex flex-col w-full h-full flex-1  md:flex-none md:border-r md:border-[#e9edef] 
    transition-all duration-300" :class="viewType==='fullview' ? 'md:w-full' : 'md:w-[30%]'">
      
      <!-- Selection Header -->

      <div
  class="flex items-center justify-between px-3 py-2 sm:px-4 sm:py-3 shrink-0 z-10 
  transition-colors duration-300 h-[56px] sm:h-[64px]"
  :class="store.attendanceMode ? 'bg-[#b91c1c]' : 'bg-[#075e54]'"
>
  <!-- LEFT: Select All — only visible in selection mode -->
  <div class="flex items-center gap-2 min-w-[80px]">
    <Transition name="fade">
      <button
        v-if="store.selectionMode"
        @click="toggleSelectAll"
        class="flex items-center gap-2 bg-transparent border-none text-white text-sm
         cursor-pointer px-2 py-1 rounded active:bg-white/20 transition-colors
         touch-manipulation min-h-[44px]"
      >
        <span
          class="w-5 h-5 sm:w-6 sm:h-6 border-2 border-white rounded-full flex items-center
           justify-center transition-all shrink-0"
          :class="allSelected ? 'bg-white text-[#075e54]' : ''"
        >
          <CheckIcon v-if="allSelected" class="w-3 h-3 sm:w-4 sm:h-4 stroke-[3]" />
        </span>
        <span class="hidden sm:inline text-white/90 text-xs font-medium">
          {{ allSelected ? 'None' : 'All' }}
        </span>
      </button>
    </Transition>

    <!-- Count badge -->
    <Transition name="fade">
      <span
        v-if="store.selectionMode && store.selectedItems.size > 0"
        class="text-xs sm:text-sm font-semibold text-white/90 whitespace-nowrap"
      >
        {{ store.selectedItems.size }}{{ store.attendanceMode ? ' Absent' : '' }}
      </span>
    </Transition>
  </div>








  <!-- CENTER: Tabs -->


     <div class="min-w-[80px] flex justify-end">
    <button
        @click=" viewType= viewType ==='list' ? 'fullview' : 'list'"
      class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs sm:text-sm
       font-medium transition-all touch-manipulation min-h-[36px] border"
      :class="store.attendanceMode
        ? 'bg-white text-[#b91c1c] border-white'
        : 'bg-white/15 text-white border-white/30 hover:bg-white/25'"
    >
            <span> {{ viewType ==='list' ? 'Full View' : 'Mini View' }}</span>
    </button>
  </div>




  <!-- RIGHT: Take Attendance button -->
  <div class="min-w-[80px] flex justify-end">
    <button
      @click="toggleAttendanceMode"
      class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs sm:text-sm
       font-medium transition-all touch-manipulation min-h-[36px] border"
      :class="store.attendanceMode
        ? 'bg-white text-[#b91c1c] border-white'
        : 'bg-white/15 text-white border-white/30 hover:bg-white/25'"
    >
   
      <span class="hidden sm:inline">
        {{ store.attendanceMode ? 'Cancel' : ' Take Attendance' }}
      </span>
    </button>
  </div>
</div>
      
      <!-- Scrollable List -->

      
      <div ref="listContainer" v-if="viewType==='list'" class="overflow-y-auto overflow-x-hidden w-full"
      :class="openItemId !== null && isMobile ? 'flex-none' : 'flex-1'">
        <div
          v-for="item in items"
          :key="item[keyField]"
          :ref="el =>{if(el) itemRefs[item[keyField]]= el}"
          class="border-b border-[#e9edef]" 
          :class="openItemId === item[keyField] && isMobile ? 'flex flex-col h-full' : ''"
        >
          <!-- List Item Header -->
          <div class="flex items-center hover:bg-[#f5f6f6] transition-colors">
            
            <!-- Checkbox (selection mode) -->
            <Transition @enter="onCheckboxEnter" @leave="onCheckboxLeave">
              <div
                v-if="store.selectionMode"
                class="px-2 sm:px-3 flex items-center cursor-pointer touch-manipulation min-h-[48px]"
                @click="store.toggleSelection(item, keyField)"
              >
                <span 
                  class="w-5 h-5 sm:w-6 sm:h-6 border-2 border-[#027eb5] rounded-full flex items-center 
                  justify-center text-xs sm:text-sm text-white transition-all"
                  :class="store.selectedItems.has(item[keyField]) ? 'bg-[#027eb5]' : ''"
                >
                  <CheckIcon v-if="store.selectedItems.has(item[keyField])" class="w-3 h-3 sm:h-4 
                  stroke-[3]"/>
                </span>
              </div>
            </Transition>

            <!-- Thumbnail -->
            <div
              
            class="w-11 h-11 sm:w-12 sm:h-12 md:w-14 md:h-14 rounded-full bg-[#dfe5e7]
               flex items-center justify-center text-lg sm:text-xl md:text-2xl my-2 mx-2 
               sm:mx-3 shrink-0 cursor-pointer select-none touch-manipulation active:scale-95 
               transition-transform"
               :ref="el=> setItemRef(el, item[keyField], item)"
              @click="toggleSelection(item)"
              @mouseenter="handleThumbnailMouseEnter(item[keyField])"
              @mouseleave="handleThumbnailMouseLeave(item[keyField])"
             
            >
              {{ getDisplayValue(item, 'avatar') }}
            </div>

            <!-- Main Content Button -->
            <button
              class="flex-1 flex items-center justify-between py-2 sm:py-3 pr-3 sm:pr-4 bg-transparent 
              border-none cursor-pointer text-left min-w-0 touch-manipulation min-h-[48px]"
              :ref="el=>setItemRef(el, 'btn-'+ item[keyField], item)"
              @click="toggleAccordion(item)"
                 
            >
              <div class="flex-1 min-w-0 pr-2">
                <h3 class="text-sm sm:text-base md:text-lg font-medium m-0 mb-0.5 sm:mb-1 text-black 
                leading-tight">
                  {{ getDisplayValue(item, 'title') }}
                </h3>
                <p class="text-xs sm:text-sm md:text-base text-[#667781] m-0 whitespace-nowrap 
                overflow-hidden text-ellipsis leading-tight">
                  {{ getDisplayValue(item, 'subtitle') }}
                </p>
              </div>
              <div class="flex flex-col items-end gap-0.5 sm:gap-1 shrink-0">
                <span class="text-[10px] sm:text-xs md:text-sm text-[#667781] whitespace-nowrap">
                  {{ getDisplayValue(item, 'timestamp') }}
                </span>
                <!-- Chevron -->
                <ChevronRightIcon class="w-4 h-4 sm:w-5 sm:h-5 text-[#667781] transition-transform 
                duration-500 ease-out" :class="openItemId === item[keyField] ? 'rotate-90': ''"/>
              </div>
            </button>
          </div>

          <!-- ACCORDION BODY (FULL SLOT) -->
          <Transition
            @enter="onContentEnter"
            @leave="onContentLeave"
            :css="false"
          >
            <div v-if="openItemId === item[keyField] && isMobile" class="flex-1 overflow-y-auto overflow-x-hidden">
              <slot name="accordion-body" :item="item" :close="() => openItemId = null"></slot>
            </div>
          </Transition>
        </div>
      </div>


      <div v-if="viewType==='fullview'" class="flex-1 overflow-y-auto overflow-x-hidden w-full">


 <slot name="fullView"  :items="items"  />


      </div>





    </div>

    <!-- RIGHT PANEL (PC) -->
    <div 
      v-if="!isMobile"
      class="hidden md:flex md:flex-col md:w-[70%] bg-[#efeae2] overflow-hidden"
    >
     <!-- RIGHT NAV BAR — mirrors left header -->
  <div
    class="flex items-center justify-between px-3 py-2 sm:px-4 sm:py-3 shrink-0 z-10
    transition-colors duration-300 h-[56px] sm:h-[64px] bg-[#075e54]"
  >
    <!-- LEFT: Tab buttons — styled like attendance button -->
    <div class="flex items-center gap-2">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        @click="activeTab = tab.key; 
        showProfile = false"
        class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs sm:text-sm
        font-medium transition-all touch-manipulation min-h-[36px] border"
        :class="activeTab === tab.key
          ? 'bg-white text-[#075e54] border-white'
          : 'bg-white/15 text-white border-white/30 hover:bg-white/25'"
      >
        <span v-if="tab.icon">{{ tab.icon }}</span>
        {{ tab.label }}
      </button>
    </div>

    <!-- RIGHT: Close button — only when profile is showing -->
    <!-- <Transition name="fade">
      <button
        v-if="tabs.length && activeTab !== tabs[0].key"
        @click="closeProfile"
        class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs sm:text-sm
        font-medium transition-all touch-manipulation min-h-[36px] border
        bg-white/15 text-white border-white/30 hover:bg-white/25"
      >
        <ChevronRightIcon class="w-4 h-4 rotate-180 shrink-0" />
        <span class="hidden sm:inline">Back</span>
      </button>
    </Transition> -->
  </div>





    <!-- Profile panel — only on default tab + student selected -->
  <div
    ref="profilePanel"
    v-if="showProfile"
    class="flex flex-col h-full overflow-hidden"
  >
    <slot name="profile" :item="selectedItem" :close="closeProfile" />
  </div>

  <!-- All other slots — always mounted, receive :item as it updates -->
  <div v-show="!showProfile" class="flex flex-col flex-1 overflow-hidden">
  
      <slot
        :name="activeTab"
        :item="selectedItem"
        :key="activeTab"
        :close="closePanel"
      />
  
  </div>


    </div>
  </div>
</template>

<style scoped>
/* Smooth animations */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fadeIn {
  animation: fadeIn 0.3s ease-out;
}

/* Scrollbar */
.overflow-y-auto::-webkit-scrollbar {
  width: 3px;
}

@media (min-width: 640px) {
  .overflow-y-auto::-webkit-scrollbar {
    width: 4px;
  }
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #c4c4c4;
  border-radius: 2px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #a0a0a0;
}
</style>