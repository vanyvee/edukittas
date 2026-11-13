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
   BASE BRAND — TastyByti (Gold + Oxblood)
═══════════════════════════════════════════ */
:root {
  /* PRIMARY — Gold/Amber */
  --primary-300: oklch(80% 0.18 85);
  --primary-500: oklch(64% 0.20 85);
  --primary-700: oklch(44% 0.16 85);
  --primary-900: oklch(24% 0.08 85);

  /* SECONDARY — Oxblood Red */
  --secondary-300: oklch(72% 0.13 18);
  --secondary-500: oklch(42% 0.17 18);
  --secondary-700: oklch(26% 0.12 18);
  --secondary-900: oklch(14% 0.06 18);

  /* ACCENT — Warm Amber */
  --accent-300: oklch(84% 0.15 90);
  --accent-500: oklch(68% 0.19 90);
  --accent-700: oklch(48% 0.15 90);
  --accent-900: oklch(28% 0.07 90);

  /* SURFACE */
  --surface-300: oklch(90% 0.01 85);
  --surface-500: oklch(70% 0.01 85);
  --surface-700: oklch(42% 0.006 85);
  --surface-900: oklch(16% 0.002 85);

  /* NEUTRAL */
  --neutral-300: oklch(82% 0 0);
  --neutral-500: oklch(56% 0 0);
  --neutral-700: oklch(34% 0 0);
  --neutral-900: oklch(14% 0 0);

  /* DANGER */
  --danger-300: oklch(76% 0.13 15);
  --danger-500: oklch(54% 0.18 15);
  --danger-700: oklch(36% 0.22 25);
  --danger-900: oklch(20% 0.14 25);

  /* SUCCESS */
  --success-300: oklch(80% 0.14 150);
  --success-500: oklch(60% 0.18 150);
  --success-700: oklch(40% 0.15 150);
  --success-900: oklch(20% 0.07 150);

  /* WARNING */
  --warning-300: oklch(82% 0.17 80);
  --warning-500: oklch(68% 0.19 80);
  --warning-700: oklch(48% 0.15 80);
  --warning-900: oklch(28% 0.07 80);

  /* INFO */
  --info-300: oklch(78% 0.13 240);
  --info-500: oklch(55% 0.18 240);
  --info-700: oklch(38% 0.16 240);
  --info-900: oklch(22% 0.09 240);

  /* OVERLAY */
  --overlay-soft:   oklch(0% 0 0 / 0.40);
  --overlay-medium: oklch(0% 0 0 / 0.60);
  --overlay-hard:   oklch(0% 0 0 / 0.85);

  /* GLASS */
  --glass-white:   oklch(100% 0 0 / 0.12);
  --glass-primary: oklch(64% 0.20 85 / 0.15);
  --glass-dark:    oklch(20% 0 0 / 0.25);

  /* SHADOWS */
  --shadow-primary:   oklch(64% 0.20 85 / 0.35);
  --shadow-secondary: oklch(42% 0.17 18 / 0.35);
  --shadow-danger:    oklch(54% 0.18 15 / 0.35);
  --shadow-success:   oklch(60% 0.18 150 / 0.35);
  --shadow-neutral:   oklch(20% 0 0 / 0.15);
}


/* ═══════════════════════════════════════════
   THEME 1 — Metallic Chic
   (Deep navy + indigo + silver)
═══════════════════════════════════════════ */
[data-theme="theme1"] {
  --primary-300: oklch(74% 0.10 255);
  --primary-500: oklch(52% 0.16 255);
  --primary-700: oklch(34% 0.18 255);
  --primary-900: oklch(18% 0.14 255);

  --secondary-300: oklch(78% 0.08 260);
  --secondary-500: oklch(58% 0.12 260);
  --secondary-700: oklch(38% 0.10 260);
  --secondary-900: oklch(22% 0.07 260);

  --accent-300: oklch(82% 0.05 220);
  --accent-500: oklch(66% 0.04 220);
  --accent-700: oklch(46% 0.03 220);
  --accent-900: oklch(28% 0.02 220);

  --surface-300: oklch(88% 0.02 255);
  --surface-500: oklch(68% 0.03 255);
  --surface-700: oklch(38% 0.04 255);
  --surface-900: oklch(16% 0.05 255);

  --glass-white:   oklch(100% 0 0 / 0.08);
  --glass-primary: oklch(52% 0.16 255 / 0.20);
  --glass-dark:    oklch(18% 0.14 255 / 0.35);

  --shadow-primary:   oklch(52% 0.16 255 / 0.40);
  --shadow-secondary: oklch(58% 0.12 260 / 0.30);
}


/* ═══════════════════════════════════════════
   THEME 2 — Cool and Collected
   (Deep teal + terracotta/rust accent)
═══════════════════════════════════════════ */
[data-theme="theme2"] {
  --primary-300: oklch(72% 0.12 192);
  --primary-500: oklch(50% 0.16 192);
  --primary-700: oklch(32% 0.14 192);
  --primary-900: oklch(16% 0.10 192);

  --secondary-300: oklch(68% 0.14 40);
  --secondary-500: oklch(48% 0.16 40);
  --secondary-700: oklch(32% 0.13 40);
  --secondary-900: oklch(18% 0.09 40);

  --accent-300: oklch(80% 0.10 185);
  --accent-500: oklch(62% 0.14 185);
  --accent-700: oklch(42% 0.12 185);
  --accent-900: oklch(24% 0.08 185);

  --surface-300: oklch(86% 0.02 192);
  --surface-500: oklch(62% 0.04 192);
  --surface-700: oklch(34% 0.06 192);
  --surface-900: oklch(14% 0.08 192);

  --glass-white:   oklch(100% 0 0 / 0.10);
  --glass-primary: oklch(50% 0.16 192 / 0.18);
  --glass-dark:    oklch(16% 0.10 192 / 0.32);

  --shadow-primary:   oklch(50% 0.16 192 / 0.38);
  --shadow-secondary: oklch(48% 0.16 40 / 0.32);
}


/* ═══════════════════════════════════════════
   THEME 3 — Striking and Simple
   (Near-black + electric cyan/mint)
═══════════════════════════════════════════ */
[data-theme="theme3"] {
  --primary-300: oklch(84% 0.16 180);
  --primary-500: oklch(70% 0.20 180);
  --primary-700: oklch(50% 0.18 180);
  --primary-900: oklch(18% 0.04 180);

  --secondary-300: oklch(78% 0.10 195);
  --secondary-500: oklch(58% 0.14 195);
  --secondary-700: oklch(38% 0.10 195);
  --secondary-900: oklch(14% 0.03 195);

  --accent-300: oklch(88% 0.14 170);
  --accent-500: oklch(72% 0.18 170);
  --accent-700: oklch(52% 0.16 170);
  --accent-900: oklch(20% 0.05 170);

  --surface-300: oklch(88.433% 0.18735 143.814);
  --surface-500: oklch(40% 0 0);
  --surface-700: oklch(22% 0 0);
  --surface-900: oklch(10% 0 0);

  --glass-white:   oklch(100% 0 0 / 0.06);
  --glass-primary: oklch(70% 0.20 180 / 0.15);
  --glass-dark:    oklch(10% 0 0 / 0.60);

  --shadow-primary:   oklch(70% 0.20 180 / 0.45);
  --shadow-secondary: oklch(58% 0.14 195 / 0.35);
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