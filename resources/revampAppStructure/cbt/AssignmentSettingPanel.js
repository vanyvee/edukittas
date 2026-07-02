<template>
  <div class="upload-wrapper">

    <!-- 🧾 Question Text -->
    <textarea v-model="text" placeholder="Enter question text" class="form-control mb-3"></textarea>

    <!-- 🎬 File Upload Inputs -->
    <div class="input-group mb-3">
      <input
        type="file"
        class="form-control"
        @change="onFileSelect"
      />
      <input
        type="text"
        class="form-control"
        placeholder="Enter title for this file"
        v-model="fileTitle"
      />
      <button class="btn btn-primary" @click="addFile">Add</button>
    </div>

    <!-- 🖼️ Thumbnails of Added Files -->
    <div class="thumbs mt-3 d-flex flex-wrap gap-3">
      <div
        v-for="(f, index) in files"
        :key="index"
        class="thumbnail border p-2 text-center"
        style="width: 150px;"
      >
        <div v-if="f.type.includes('image')">
          <img :src="f.preview" style="width:100%; height:100px; object-fit:cover;" />
        </div>

        <div v-else-if="f.type.includes('video')">
          <video :src="f.preview" controls style="width:100%; height:100px;"></video>
        </div>

        <p class="small mt-1">{{ f.title }}</p>
      </div>
    </div>

    <!-- 🚀 Final Submit -->
    <button class="btn btn-success mt-4" @click="submit">Submit Assignment</button>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const text = ref('')
const fileTitle = ref('')
const fileObject = ref(null)
const files = ref([])

const onFileSelect = (e) => {
  fileObject.value = e.target.files[0]
}

const addFile = () => {
  if (!fileObject.value || !fileTitle.value) {
    alert('Please select a file and enter a title.')
    return
  }

  const file = fileObject.value
  const preview = URL.createObjectURL(file)

  files.value.push({
    title: fileTitle.value,
    file,
    preview,
    type: file.type
  })

  // Reset fields for next upload
  fileTitle.value = ''
  fileObject.value = null
  document.querySelector('input[type="file"]').value = ''
}

const submit = async () => {
  const formData = new FormData()
  formData.append('teacher_id', 1)
  formData.append('asgn_id', 5)
  formData.append('text', text.value)

  files.value.forEach((f, i) => {
    formData.append(`files[${i}][title]`, f.title)
    formData.append(`files[${i}][file]`, f.file)
  })

  await fetch('/api/assignments', {
    method: 'POST',
    body: formData,
  })
  alert('Assignment submitted!')
}
</script>

<style scoped>
.thumbnail {
  border-radius: 8px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
</style>

