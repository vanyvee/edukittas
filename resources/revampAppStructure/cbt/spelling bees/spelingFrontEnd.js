<template>
  <div>
    <button @click="startRecording" :disabled="recording">Start</button>
    <button @click="stopRecording" :disabled="!recording">Stop</button>
    <p v-if="uploading">Uploading audio...</p>
  </div>
</template>

<script setup>
import { ref } from "vue";

const recording = ref(false);
const uploading = ref(false);
let mediaRecorder = null;
let chunks = [];

// ✅ Check if browser is Safari (iPhone/iPad)
const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);

// ✅ Pick best audio format
const mimeType = isSafari 
  ? "audio/mp4"                // AAC/M4A (Safari only)
  : "audio/webm;codecs=opus";  // OPUS inside WebM

async function startRecording() {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    chunks = [];

    mediaRecorder = new MediaRecorder(stream, { mimeType });

    mediaRecorder.ondataavailable = (e) => chunks.push(e.data);

    mediaRecorder.start();
    recording.value = true;

  } catch (err) {
    console.error("Mic error:", err);
  }
}

function stopRecording() {
  if (!mediaRecorder) return;
  
  mediaRecorder.stop();
  recording.value = false;

  mediaRecorder.onstop = async () => {
    const blob = new Blob(chunks, { type: mimeType });

    // Safari: use .m4a ; Others: .webm
    const fileExt = isSafari ? "m4a" : "webm";
    const file = new File([blob], "spell." + fileExt, { type: mimeType });

    const formData = new FormData();
    formData.append("audio", file);
    formData.append("expected", "girl");     // example
    formData.append("duration", "3");         // example

    uploading.value = true;

    try {
      const response = await fetch("/api/spell-check", {
        method: "POST",
        body: formData
      });

      const json = await response.json();
      console.log("Server:", json);

    } catch (err) {
      console.error("Upload error:", err);
    }

    uploading.value = false;
  };
}
</script>