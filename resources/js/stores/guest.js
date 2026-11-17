import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useLocalStorage } from '@vueuse/core'
import axios from 'axios'

/**
 * What the guest pages need: the featured schools (hero and picker) and the one school the visitor
 * picked (the "At a glance" slide). School profiles are remembered, so going back to a school costs
 * no request. The picked school is kept between visits.
 */
export const useGuestStore = defineStore('guest', () => {
  const featured = ref([])
  const featuredLoading = ref(false)

  const selectedId = useLocalStorage('guest-school', null)
  const profile = ref(null)
  const profileLoading = ref(false)
  const profileError = ref(null)

  const cache = new Map()

  const selected = computed(() => featured.value.find((s) => s.id === selectedId.value) ?? null)
  const selectedName = computed(() => selected.value?.name ?? profile.value?.name ?? null)

  /**
   * WHAT IT DOES: Loads the featured schools once, then opens the school the visitor picked last time.
   *
   * USED WHEN: When the guest page opens.
   *
   * GIVES BACK: Nothing.
   */
  async function init() {
    await loadFeatured()
    if (selectedId.value) selectSchool(selectedId.value)
  }

  /**
   * WHAT IT DOES: Fetches the featured schools (id, name, logo, cover, location). If it fails the
   *    hero simply shows the brand fallback.
   *
   * USED WHEN: Inside init.
   *
   * GIVES BACK: Nothing.
   */
  async function loadFeatured() {
    if (featured.value.length || featuredLoading.value) return

    featuredLoading.value = true
    try {
      const { data } = await axios.get('/public/schools/featured')
      featured.value = data
    } catch {
      featured.value = []
    } finally {
      featuredLoading.value = false
    }
  }

  /**
   * WHAT IT DOES: Picks a school and loads its public profile, from memory when it was seen before.
   *    An answer that arrives after the visitor has already picked another school is ignored.
   *
   * USED WHEN: When a school is chosen in the top bar or on the At a glance slide.
   *
   * GIVES BACK: Nothing.
   */
  async function selectSchool(id) {
    selectedId.value = id
    profileError.value = null

    if (cache.has(id)) {
      profile.value = cache.get(id)
      return
    }

    profile.value = null
    profileLoading.value = true
    try {
      const { data } = await axios.get(`/public/schools/${id}`)
      cache.set(id, data)
      if (selectedId.value === id) profile.value = data
    } catch {
      if (selectedId.value === id) profileError.value = 'Could not load this school. Please try again.'
    } finally {
      if (selectedId.value === id) profileLoading.value = false
    }
  }

  return {
    featured, featuredLoading,
    selectedId, selected, selectedName,
    profile, profileLoading, profileError,
    init, loadFeatured, selectSchool,
  }
})
