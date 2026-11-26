<template>
    <div v-show="!mounted" class="min-h-screen bg-gray-100 h-dvh"></div>
   <div v-show="mounted"   class="h-dvh w-full overflow-hidden">
   <RouterView />
   </div> 
</template>

<script setup>
import { useThemeStore } from '@/stores/theme'
import { useAuthStore }  from '@/stores/auth'
import { onMounted, ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
//import ContextPicker from '@/components/ContextPicker.vue'

const theme   = useThemeStore()
const auth    = useAuthStore()
const router  = useRouter()
const route   = useRoute()
const mounted = ref(false)

onMounted(async () => {
  theme.init()
  await router.isReady()   // the guard in app.js already loads auth.me()
  mounted.value = true
})

// The school the person is working in decides the colour theme.
//  - On refresh, init() already painted the remembered copy, so there is no flash.
//  - Once /me has answered, the server's theme is painted (a repaint only if it differs) and remembered.
//  - Logging out (or an admin/affiliate with no school) goes back to the default and forgets it.
watch(
  () => [auth.checked, auth.activeSchool?.theme],
  ([checked, code]) => {
    if (!checked) return          // wait for /me; the remembered colour is showing meanwhile
    theme.showSchool(code)
  },
  { immediate: true }
)

// Logging in or out re-runs the guard, which moves the person to their own world.
watch(() => [auth.isLoggedIn, auth.guard], () => {
  if (mounted.value) router.replace({ path: route.path, query: route.query, force: true })
})

</script>



<style>
html, body, #app {
  height: 100%;
  overflow: hidden;
}



/* ═══════════════════════════════════════════
   BASE BRAND — Onion & Milky
═══════════════════════════════════════════ */
/* Onion & Milky */
:root,
[data-theme="default"] {
  --primary-300: oklch(82.00% 0.1377 307.52);
  --primary-500: oklch(62.00% 0.1377 307.52);
  --primary-700: oklch(42.00% 0.1377 307.52);
  --primary-900: oklch(24.00% 0.0991 307.52);
  --secondary-300: oklch(84.00% 0.1185 310.65);
  --secondary-500: oklch(64.00% 0.1185 310.65);
  --secondary-700: oklch(43.00% 0.1185 310.65);
  --secondary-900: oklch(25.00% 0.0854 310.65);
  --accent-300: oklch(88.00% 0.0725 309.65);
  --accent-500: oklch(68.00% 0.0725 309.65);
  --accent-700: oklch(46.00% 0.0725 309.65);
  --accent-900: oklch(27.00% 0.0522 309.65);
  --surface-300: oklch(96.00% 0.0120 313.22);
  --surface-500: oklch(78.00% 0.0160 313.22);
  --surface-700: oklch(48.00% 0.0200 313.22);
  --surface-900: oklch(22.00% 0.0200 313.22);
  --glass-white: oklch(100% 0 0 / 0.18);
  --glass-primary: oklch(37.18% 0.1170 307.52 / 0.18);
  --glass-dark: oklch(12% 0.01 260 / 0.34);
  --shadow-primary: oklch(37.18% 0.1239 307.52 / 0.36);
  --shadow-secondary: oklch(48.83% 0.1067 310.65 / 0.30);

  /* NEUTRAL — stable utility colours, independent of brand selection */
  --neutral-300: oklch(82% 0 0);
  --neutral-500: oklch(56% 0 0);
  --neutral-700: oklch(34% 0 0);
  --neutral-900: oklch(14% 0 0);
  /* STATUS COLOURS — intentionally consistent across themes */
  --danger-300: oklch(76% 0.13 15);
  --danger-500: oklch(54% 0.18 15);
  --danger-700: oklch(36% 0.22 25);
  --danger-900: oklch(20% 0.14 25);
  --success-300: oklch(80% 0.14 150);
  --success-500: oklch(60% 0.18 150);
  --success-700: oklch(40% 0.15 150);
  --success-900: oklch(20% 0.07 150);
  --warning-300: oklch(82% 0.17 80);
  --warning-500: oklch(68% 0.19 80);
  --warning-700: oklch(48% 0.15 80);
  --warning-900: oklch(28% 0.07 80);
  --info-300: oklch(78% 0.13 240);
  --info-500: oklch(55% 0.18 240);
  --info-700: oklch(38% 0.16 240);
  --info-900: oklch(22% 0.09 240);
  --overlay-soft: oklch(0% 0 0 / 0.40);
  --overlay-medium: oklch(0% 0 0 / 0.60);
  --overlay-hard: oklch(0% 0 0 / 0.85);
  /* SHADOWS that were dropped in the drop-in (used by shadow-soft, glow utilities, dropdowns) */
  --shadow-danger:  oklch(54% 0.18 15 / 0.35);
  --shadow-success: oklch(60% 0.18 150 / 0.35);
  --shadow-neutral: oklch(20% 0 0 / 0.15);
  /* TEXT ON -500 TONES (needed by Glass/Soft/Elastic styles; ink gives 4.3-4.9:1 on every theme) */
  --on-primary:   var(--surface-900);
  --on-secondary: var(--surface-900);
  --on-accent:    var(--surface-900);
}

/* Goal Mastery */
[data-theme="theme1"] {
  --primary-300: oklch(82.00% 0.0721 247.98);
  --primary-500: oklch(62.00% 0.0721 247.98);
  --primary-700: oklch(42.00% 0.0721 247.98);
  --primary-900: oklch(24.00% 0.0519 247.98);
  --secondary-300: oklch(84.00% 0.1320 266.80);
  --secondary-500: oklch(64.00% 0.1320 266.80);
  --secondary-700: oklch(43.00% 0.1320 266.80);
  --secondary-900: oklch(25.00% 0.0950 266.80);
  --accent-300: oklch(88.00% 0.0486 234.94);
  --accent-500: oklch(68.00% 0.0486 234.94);
  --accent-700: oklch(46.00% 0.0486 234.94);
  --accent-900: oklch(27.00% 0.0350 234.94);
  --surface-300: oklch(96.00% 0.0120 255.47);
  --surface-500: oklch(78.00% 0.0160 255.47);
  --surface-700: oklch(48.00% 0.0200 255.47);
  --surface-900: oklch(22.00% 0.0200 255.47);
  --glass-white: oklch(100% 0 0 / 0.18);
  --glass-primary: oklch(34.28% 0.0613 247.98 / 0.18);
  --glass-dark: oklch(12% 0.01 260 / 0.34);
  --shadow-primary: oklch(34.28% 0.0649 247.98 / 0.36);
  --shadow-secondary: oklch(66.91% 0.1188 266.80 / 0.30);
}

/* Royal Mint */
[data-theme="theme2"] {
  --primary-300: oklch(82.00% 0.1269 308.60);
  --primary-500: oklch(62.00% 0.1269 308.60);
  --primary-700: oklch(42.00% 0.1269 308.60);
  --primary-900: oklch(24.00% 0.0914 308.60);
  --secondary-300: oklch(84.00% 0.0926 175.43);
  --secondary-500: oklch(64.00% 0.0926 175.43);
  --secondary-700: oklch(43.00% 0.0926 175.43);
  --secondary-900: oklch(25.00% 0.0667 175.43);
  --accent-300: oklch(88.00% 0.0477 178.30);
  --accent-500: oklch(68.00% 0.0477 178.30);
  --accent-700: oklch(46.00% 0.0477 178.30);
  --accent-900: oklch(27.00% 0.0344 178.30);
  --surface-300: oklch(96.00% 0.0120 319.45);
  --surface-500: oklch(78.00% 0.0160 319.45);
  --surface-700: oklch(48.00% 0.0200 319.45);
  --surface-900: oklch(22.00% 0.0200 319.45);
  --glass-white: oklch(100% 0 0 / 0.18);
  --glass-primary: oklch(30.78% 0.1079 308.60 / 0.18);
  --glass-dark: oklch(12% 0.01 260 / 0.34);
  --shadow-primary: oklch(30.78% 0.1142 308.60 / 0.36);
  --shadow-secondary: oklch(82.28% 0.0833 175.43 / 0.30);
}

/* Nanocodes */
[data-theme="theme3"] {
  --primary-300: oklch(82.00% 0.1885 293.35);
  --primary-500: oklch(62.00% 0.1885 293.35);
  --primary-700: oklch(42.00% 0.1885 293.35);
  --primary-900: oklch(24.00% 0.1357 293.35);
  --secondary-300: oklch(84.00% 0.1441 292.30);
  --secondary-500: oklch(64.00% 0.1441 292.30);
  --secondary-700: oklch(43.00% 0.1441 292.30);
  --secondary-900: oklch(25.00% 0.1037 292.30);
  --accent-300: oklch(88.00% 0.0962 300.82);
  --accent-500: oklch(68.00% 0.0962 300.82);
  --accent-700: oklch(46.00% 0.0962 300.82);
  --accent-900: oklch(27.00% 0.0693 300.82);
  --surface-300: oklch(96.00% 0.0120 286.33);
  --surface-500: oklch(78.00% 0.0160 286.33);
  --surface-700: oklch(48.00% 0.0200 286.33);
  --surface-900: oklch(22.00% 0.0200 286.33);
  --glass-white: oklch(100% 0 0 / 0.18);
  --glass-primary: oklch(41.33% 0.1602 293.35 / 0.18);
  --glass-dark: oklch(12% 0.01 260 / 0.34);
  --shadow-primary: oklch(41.33% 0.1697 293.35 / 0.36);
  --shadow-secondary: oklch(30.47% 0.1297 292.30 / 0.30);
}

/* Blue Serenity */
[data-theme="theme4"] {
  --primary-300: oklch(82.00% 0.1283 269.53);
  --primary-500: oklch(62.00% 0.1283 269.53);
  --primary-700: oklch(42.00% 0.1283 269.53);
  --primary-900: oklch(24.00% 0.0924 269.53);
  --secondary-300: oklch(84.00% 0.1320 266.80);
  --secondary-500: oklch(64.00% 0.1320 266.80);
  --secondary-700: oklch(43.00% 0.1320 266.80);
  --secondary-900: oklch(25.00% 0.0950 266.80);
  --accent-300: oklch(88.00% 0.0468 266.09);
  --accent-500: oklch(68.00% 0.0468 266.09);
  --accent-700: oklch(46.00% 0.0468 266.09);
  --accent-900: oklch(27.00% 0.0337 266.09);
  --surface-300: oklch(96.00% 0.0120 303.41);
  --surface-500: oklch(78.00% 0.0160 303.41);
  --surface-700: oklch(48.00% 0.0200 303.41);
  --surface-900: oklch(22.00% 0.0200 303.41);
  --glass-white: oklch(100% 0 0 / 0.18);
  --glass-primary: oklch(46.36% 0.1091 269.53 / 0.18);
  --glass-dark: oklch(12% 0.01 260 / 0.34);
  --shadow-primary: oklch(46.36% 0.1155 269.53 / 0.36);
  --shadow-secondary: oklch(66.91% 0.1188 266.80 / 0.30);
}

/* Ocean Breeze */
[data-theme="theme5"] {
  --primary-300: oklch(82.00% 0.1649 265.48);
  --primary-500: oklch(62.00% 0.1649 265.48);
  --primary-700: oklch(42.00% 0.1649 265.48);
  --primary-900: oklch(24.00% 0.1188 265.48);
  --secondary-300: oklch(84.00% 0.1140 233.66);
  --secondary-500: oklch(64.00% 0.1140 233.66);
  --secondary-700: oklch(43.00% 0.1140 233.66);
  --secondary-900: oklch(25.00% 0.0821 233.66);
  --accent-300: oklch(88.00% 0.1670 297.45);
  --accent-500: oklch(68.00% 0.1670 297.45);
  --accent-700: oklch(46.00% 0.1670 297.45);
  --accent-900: oklch(27.00% 0.1202 297.45);
  --surface-300: oklch(96.00% 0.0120 267.36);
  --surface-500: oklch(78.00% 0.0160 267.36);
  --surface-700: oklch(48.00% 0.0200 267.36);
  --surface-900: oklch(22.00% 0.0200 267.36);
  --secondary-300: oklch(81.33% 0.0841 224.87);
  --glass-white: oklch(100% 0 0 / 0.18);
  --glass-primary: oklch(62.16% 0.1402 265.48 / 0.18);
  --glass-dark: oklch(12% 0.01 260 / 0.34);
  --shadow-primary: oklch(62.16% 0.1484 265.48 / 0.36);
  --shadow-secondary: oklch(74.77% 0.1026 233.66 / 0.30);
}

/* Electric Teal */
[data-theme="theme6"] {
  --primary-300: oklch(82.00% 0.0877 191.36);
  --primary-500: oklch(62.00% 0.0877 191.36);
  --primary-700: oklch(42.00% 0.0877 191.36);
  --primary-900: oklch(24.00% 0.0632 191.36);
  --secondary-300: oklch(84.00% 0.1282 188.42);
  --secondary-500: oklch(64.00% 0.1282 188.42);
  --secondary-700: oklch(43.00% 0.1282 188.42);
  --secondary-900: oklch(25.00% 0.0923 188.42);
  --accent-300: oklch(88.00% 0.1282 188.42);
  --accent-500: oklch(68.00% 0.1282 188.42);
  --accent-700: oklch(46.00% 0.1282 188.42);
  --accent-900: oklch(27.00% 0.0923 188.42);
  --surface-300: oklch(94.00% 0.0120 253.69);
  --surface-500: oklch(72.00% 0.0160 253.69);
  --surface-700: oklch(42.00% 0.0200 253.69);
  --surface-900: oklch(27.33% 0.0242 253.69);
  --glass-white: oklch(100% 0 0 / 0.08);
  --glass-primary: oklch(65.57% 0.0746 191.36 / 0.18);
  --glass-dark: oklch(12% 0.01 260 / 0.34);
  --shadow-primary: oklch(65.57% 0.0790 191.36 / 0.36);
  --shadow-secondary: oklch(90.80% 0.1154 188.42 / 0.30);
}

/* ═══════════════════════════════════════════
   GRADIENT UTILITIES
═══════════════════════════════════════════ */
@layer utilities {
  .grad-brand {
    background: linear-gradient(in oklch 135deg,
      var(--primary-500) 0%,
      var(--secondary-700) 100%
    );
  }
  .grad-brand-soft {
    background: linear-gradient(in oklch 135deg,
      var(--primary-300) 0%,
      var(--secondary-300) 100%
    );
  }
  .grad-hero {
    background: linear-gradient(in oklch 160deg,
      var(--primary-700) 0%,
      var(--secondary-900) 100%
    );
  }
  .grad-accent {
    background: linear-gradient(in oklch 135deg,
      var(--accent-300) 0%,
      var(--primary-500) 100%
    );
  }
  .grad-surface {
    background: linear-gradient(180deg,
      var(--surface-300) 0%,
      var(--surface-500) 100%
    );
  }
  .grad-dark {
    background: linear-gradient(in oklch 160deg,
      var(--surface-700) 0%,
      var(--surface-900) 100%
    );
  }
  .grad-success {
    background: linear-gradient(in oklch 135deg,
      var(--success-300) 0%,
      var(--success-700) 100%
    );
  }
  .grad-danger {
    background: linear-gradient(in oklch 135deg,
      var(--danger-300) 0%,
      var(--danger-700) 100%
    );
  }
  .grad-text-brand {
    background: linear-gradient(in oklch 90deg,
      var(--primary-500),
      var(--secondary-500)
    );
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }
  .grad-text-accent {
    background: linear-gradient(in oklch 90deg,
      var(--accent-500),
      var(--primary-700)
    );
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  /* Glass */
  .glass {
    background: var(--glass-white);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid oklch(100% 0 0 / 0.18);
  }
  .glass-primary {
    background: var(--glass-primary);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid var(--primary-300);
  }
  .glass-dark {
    background: var(--glass-dark);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid oklch(100% 0 0 / 0.08);
  }

  /* Shadows */
  .shadow-primary-glow {
    box-shadow: 0 4px 24px var(--shadow-primary);
  }
  .shadow-secondary-glow {
    box-shadow: 0 4px 24px var(--shadow-secondary);
  }
  .shadow-danger-glow {
    box-shadow: 0 4px 24px var(--shadow-danger);
  }
  .shadow-success-glow {
    box-shadow: 0 4px 24px var(--shadow-success);
  }
  .shadow-soft {
    box-shadow: 0 2px 16px var(--shadow-neutral);
  }

  /* Overlay */
  .overlay-soft   { background: var(--overlay-soft); }
  .overlay-medium { background: var(--overlay-medium); }
  .overlay-hard   { background: var(--overlay-hard); }
}
</style>