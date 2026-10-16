<template>
  <div class="input-capsule d-flex align-items-center p-2">

<div
  v-if="banks"
  class="flex-grow-1 text-center mx-1 py-2 "
>
  {{ 'hello bsnks'}}
</div>



<div
  v-if="readonly"
  class="flex-grow-1 text-center mx-1 py-2 text-muted"
>
  {{ placeholder }}
</div>

<input
  v-else
  type="text"
  class="form-control capsule-input flex-grow-1 mx-1"
  :placeholder="placeholder"
  v-model.trim="value"
/>

    <button
      class="btn btn-primary ms-2 ok-btn"
      @click="handleOk"
      :disabled="readonly"
    >
{{buttonLabel}}
    </button>
  </div>
</template>
  .!
<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const {placeholder, link, onSubmit, readonly, buttonLabel} = defineProps({
  placeholder: { type: String, default: 'Enter text' },
  link: { type: String, default: '' }, // optional route
  buttonLabel:0 { type: String, default: 'Ok'}, /
  onSubmit: { type: Function }, // optional callback
  readonly: { type: Boolean, default: false } // toggle read-only mode
  banks: { type: array, default: 'Opay'}, /

})

const router = useRouter()
const value = ref('')
const banks = ref([])

const handleOk = () => {
  if (props.onSubmit) {
    // call external function
    props.onSubmit(value.value)
  } else if (props.link) {
    // navigate if route provided
    router.push({ path: props.link, query: { value: value.value } })
  }
}
</script>

<style scoped>
.input-capsule {
  background: #fff;
  border: 1px solid #dee2e6;
  border-radius: 50px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
  transition: box-shadow 0.2s ease;
}

.input-capsule:hover {
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
}

.capsule-input {
  border: none !important;
  box-shadow: none !important;
  background: transparent;
  text-align: center;
}

.capsule-input:focus {
  outline: none;
  box-shadow: none;
  background: transparent;
}

.ok-btn {
  border-radius: 50px;
  padding: 0.4rem 1rem;
}

.ok-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>