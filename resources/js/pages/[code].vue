<script setup>
import { ref, computed, provide, inject, watch, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import MainView      from '@/components/MainView.vue'
import MainCarousel2 from '@/components/MainCarousel2.vue'
import LoginForm     from '@/components/LoginForm.vue'
import SiteGlance    from '@/components/site/SiteGlance.vue'
import SiteAbout     from '@/components/site/SiteAbout.vue'
import SiteGallery   from '@/components/site/SiteGallery.vue'
import SiteContact   from '@/components/site/SiteContact.vue'
import SiteEnquiry   from '@/components/site/SiteEnquiry.vue'

// A school's own site, at edukit.test/greenfield. The school owns this page: nothing here mentions
// Edukittas, the demo, or the Edukittas slides, and none of those are mounted.

const route   = useRoute()
const router  = useRouter()
const landing = inject('landing', null)

const school   = ref(null)
const loading  = ref(true)
const notFound = ref(false)

const html        = document.documentElement
const themeBefore = html.dataset.theme
const titleBefore = document.title

const applyTheme = (id) => (id ? (html.dataset.theme = id) : html.removeAttribute('data-theme'))

/**
 * WHAT IT DOES: Gets the school for this address. When the page was opened straight from the school's
 *    link, the server already put the school in `landing`. Otherwise it is fetched by its code.
 *
 * USED WHEN: When the page opens, and whenever the code in the address changes.
 *
 * GIVES BACK: Nothing.
 */
const load = async () => {
  const code = String(route.params.code ?? '')
  loading.value = true
  notFound.value = false

  const fromServer =
    landing?.type === 'school' && landing.school?.url_code?.toLowerCase() === code.toLowerCase()
      ? landing.school
      : null

  try {
    school.value = fromServer ?? (await axios.get(`/public/schools/by-code/${encodeURIComponent(code)}`)).data
    applyTheme(school.value.theme === 'default' ? '' : (school.value.theme || ''))
    document.title = school.value.name
  } catch {
    school.value = null
    notFound.value = true
  } finally {
    loading.value = false
  }
}

watch(() => route.params.code, load, { immediate: true })

onBeforeUnmount(() => {
  applyTheme(themeBefore ?? '')
  document.title = titleBefore
})

// The school's own sub-menu: only the parts the school has filled in.
const slides = computed(() => {
  if (!school.value) return []

  return [
    { component: SiteGlance, name: 'At a glance' },
    school.value.about ? { component: SiteAbout, name: 'About' } : null,
    school.value.photos?.length ? { component: SiteGallery, name: 'Gallery' } : null,
    { component: SiteContact, name: 'Contact' },
    { component: SiteEnquiry, name: 'Admissions' },
  ].filter(Boolean)
})

const carousel = ref(null)
const mainView = ref(null)

provide('school', school)
provide('switchTo', (key) => mainView.value?.switchTo(key))
provide('goToSlideByName', (name) => {
  const i = slides.value.findIndex((s) => s.name === name)
  if (i >= 0) carousel.value?.goToSlide(i, 'click')
})

// Side menu: only what a visitor of the school needs.
const navItems = [
  { key: 'Home',  label: 'Home',  icon: '🏠' },
  { key: 'Login', label: 'Login', icon: '🔑' },
]
</script>

<template>
  <!-- Loading -->
  <div v-if="loading" class="w-full h-full flex items-center justify-center bg-neutral-900">
    <span class="text-primary-300 text-sm animate-pulse">Loading...</span>
  </div>

  <!-- Not a school -->
  <div v-else-if="notFound" class="w-full h-full flex flex-col items-center justify-center gap-4 p-6 bg-surface-300 text-neutral-900">
    <p class="text-lg font-semibold">We could not find that school.</p>
    <button type="button" class="h-11 px-6 rounded-lg bg-primary text-surface-300" @click="router.replace('/')">
      Go to the home page
    </button>
  </div>

  <MainView v-else ref="mainView" :nav-items="navItems">
    <template #Home>
      <!-- The carousel's own tab strip is the sub-menu -->
      <MainCarousel2
        ref="carousel"
        :slides="slides"
        :carousel-id="`site-${school.id}`"
        indicator-position="top"
      />
    </template>

    <template #Login>
      <LoginForm />
    </template>
  </MainView>
</template>
