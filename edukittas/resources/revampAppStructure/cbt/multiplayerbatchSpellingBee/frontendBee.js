<template>
  <div class="remarks">
    <!-- Personal remark -->
    <p v-if="personalRemark" class="personal">{{ personalRemark }}</p>

    <!-- Generic remark -->
    <p v-if="genericRemark" class="generic">{{ genericRemark }}</p>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const sessionId = 12; // dynamically from your game state
const playerId  = 123; // logged-in player

const personalRemark = ref(null);
const genericRemark  = ref(null);

// Called once per broadcast to fetch & play Opus audio
async function playGenericRemark(audioKey) {
  try {
    const res = await fetch(`/api/tts/fetch/${audioKey}`);
    const arrayBuffer = await res.arrayBuffer();
    const blob = new Blob([arrayBuffer], { type: 'audio/opus' });
    const url = URL.createObjectURL(blob);
    const audio = new Audio(url);
    audio.play();
  } catch (err) {
    console.error('Failed to fetch/play generic remark audio', err);
  }
}

onMounted(() => {
  // Subscribe to Reverb/Echo channel for this session
  Echo.channel(`room.${sessionId}`)
    .listen('SpellingBeeRoundComplete', (payload) => {
      const remarks = payload.remarks || {};

      // --- Personal remark (text only) ---
      personalRemark.value = remarks.player_remarks?.[playerId] || null;

      // --- Generic remark (text + audio) ---
      genericRemark.value = remarks.generic_remark || null;

      if (remarks.generic_audio_key) {
        playGenericRemark(remarks.generic_audio_key);
      }
    });
});
</script>

<style scoped>
.personal {
  color: #2c7;
  font-weight: bold;
}
.generic {
  color: #07f;
  font-style: italic;
}
</style>

