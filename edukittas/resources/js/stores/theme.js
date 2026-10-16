import {defineStore} from "pinia";
import {ref} from "vue";

export const useThemeStore = defineStore('theme', () => {
  const themes = ref([
    { id: 'theme1', name: 'Emerald' },
    { id: 'theme2', name: 'Rose' },
    { id: 'theme3', name: 'Sky' }
  ])
  
  const active = ref("");
   
  function apply(id){
    active.value = id;
    
    if(id){
      document.documentElement.dataset.theme = id;
      localStorage.setItem("theme", id);
    }else{
      document.documentElement.removeAttribute("data-theme");
      localStorage.removeItem("theme");
    }
  }

  function init(){
apply(localStorage.getItem("theme") || "")
    
  }
  
  return { themes, active, apply, init }
})