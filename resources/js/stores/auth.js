import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

/**
 * Who is logged in and where they are working. The backend answers every login and /me with:
 *   { guard, user, school_id, schools, needs_school, ward_id, wards, needs_ward }
 * and this store keeps all of it, so the pickers and the route guard have what they need.
 */

// Which world each backend guard belongs to.
const WORLD = { web: 'school', student: 'school', guardian: 'school', admin: 'admin', affiliate: 'affiliate' }


export const useAuthStore = defineStore('auth', () => {
   // ─── State ────────────────────────────────────────────────────────────
  const user    = ref(null)
  const guard   = ref(null)
  const checked = ref(false)   // has me() finished at least once?
  const loading = ref(false)
  const error   = ref(null)

  
  // Where the person is working (staff and students: a school; guardians: a ward)
  const schoolId    = ref(null)
  const schools     = ref([])      // [{ id, name }]
  const needsSchool = ref(false)
  const wardId      = ref(null)
  const wards       = ref([])      // [{ ward: {...}, school: { id, name } | null }]
  const needsWard   = ref(false)



  // ─── Who ─────────────────────────────────────────────────────────────
  const isLoggedIn  = computed(() => !!user.value)
  const isGuest     = computed(() => !user.value)
  const isStaff     = computed(() => guard.value === 'web')
  const isStudent   = computed(() => guard.value === 'student')
  const isGuardian  = computed(() => guard.value === 'guardian')
  const isAdmin     = computed(() => guard.value === 'admin')
  const isAffiliate = computed(() => guard.value === 'affiliate')



  // Staff role: director, principal, ictAdmin, bursar, teacher...
  const role = computed(() => (isStaff.value ? user.value?.role ?? null : null))

  // What <Can type="..."> compares against: a staff member's role, otherwise the guard name.
  const authtype = computed(() => (isStaff.value ? role.value : guard.value))

  // guest | school | admin | affiliate. Used by the route guard.
  const world = computed(() => WORLD[guard.value] ?? (isLoggedIn.value ? 'school' : 'guest'))

  // A school or ward still has to be picked before the school pages can load data.
  const needsPick = computed(() => needsSchool.value || needsWard.value)

// ─── State ───────────────────────────────────────────────────────────

  /**
   * WHAT IT DOES: Copies one backend answer (login, /me, switch) into the store.
   *
   * USED WHEN: After every call that returns the login payload.
   *
   * GIVES BACK: Nothing.
   */
  const apply = (data) => {
    user.value        = data?.user ?? null
    guard.value       = data?.guard ?? null
    schoolId.value    = data?.school_id ?? null
    schools.value     = data?.schools ?? []
    needsSchool.value = !!data?.needs_school
    wardId.value      = data?.ward_id ?? null
    wards.value       = data?.wards ?? []
    needsWard.value   = !!data?.needs_ward
  }
 
  /**
   * WHAT IT DOES: Forgets everything about the logged-in person.
   *
   * USED WHEN: On logout, and when /me fails.
   *
   * GIVES BACK: Nothing.
   */
  const reset = () => apply(null)

  // ─── Calls ───────────────────────────────────────────────────────────

  /**
   * WHAT IT DOES: Asks the backend who is logged in, and flips `checked` whether it worked or not.
   *
   * USED WHEN: Once at start-up (the route guard), and after switching school or ward.
   *
   * GIVES BACK: Nothing.
   */
  const me = async () => {
    try {
      const { data } = await axios.get('/me')
      apply(data)
    } catch (_) {
      reset()
    } finally {
      checked.value = true
    }
  }

 
  /**
   * WHAT IT DOES: Logs in. Staff, students and guardians are told apart by the username prefix, so
   *    the form sends it exactly as typed.
   *
   * USED WHEN: When the login form is submitted.
   *
   * GIVES BACK: { success: true, data } or { success: false, error }.
   */
  const login = async (username, password) => {
    error.value   = null
    loading.value = true
    try {
      const { data } = await axios.post('/login', { username, password })
      apply(data)
      checked.value = true
      return { success: true, data }
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Invalid credentials.'
      return { success: false, error: err.response?.data ?? error.value }
    } finally {
      loading.value = false
    }
  }

   /**
   * WHAT IT DOES: Logs out and clears everything.
   *
   * USED WHEN: When the person presses Logout.
   *
   * GIVES BACK: Nothing.
   */
  const logout = async () => {
    loading.value = true
    try {
      await axios.post('/logout')
    } catch (_) {
      // the session is gone either way
    } finally {
      reset()
      loading.value = false
      error.value   = null
    }
  }


  // The school the person is working in now, with its theme and logo.
const activeSchool = computed(() =>
  isGuardian.value
    ? wards.value.find((w) => w.ward.id === wardId.value)?.school ?? null
    : schools.value.find((s) => s.id === schoolId.value) ?? null
)

   /**
   * WHAT IT DOES: Staff pick the school they are working in, then the store reloads from /me.
   *
   * USED WHEN: From the picker, for a staff member who belongs to several schools.
   *
   * GIVES BACK: true or false.
   */


  const switchSchool = async (id) => {
  error.value = null
  try {
    await axios.post('/switch-school', { school_id: id })
    schoolId.value = id
    return true
  } catch (err) {
    error.value = err.response?.data?.message ?? 'Could not switch school.'
    return false
  }
}

  /**
   * WHAT IT DOES: A guardian picks which child they are looking at, then the store reloads from /me.
   *
   * USED WHEN: From the picker, and when a guardian changes child.
   *
   * GIVES BACK: true or false.
   */

  const switchWard = async (id) => {
  error.value = null
  const entry = wards.value.find((w) => w.ward.id === id)
  if (!entry?.school) return false
  try {
    await axios.post('/switch-ward', { ward_id: id })   // moves the session, returns nothing
    wardId.value = id
    schoolId.value = entry.school.id
    return true
  } catch (err) {
    error.value = err.response?.data?.message ?? 'Could not select that child.'
    return false
  }
}



  // ─── Register ─────────────────────────────────────────────────────────
  const register = async (form) => {
  error.value   = null
  loading.value = true
  const rawForm = JSON.parse(JSON.stringify(form))
  const sometimesFields = ['role', 'day', 'month', 'year']
  const cleanForm = Object.fromEntries(
  Object.entries(rawForm).filter(([key, value]) => {
    if (sometimesFields.includes(key) && (value === '' || value === null || value === undefined)) {
      return false  // drop it entirely
    }
    return true  // keep everything else including empty nullable fields
  })
)

const endpoint = ['staff', 'admin'].includes(guard.value)
  ? '/staff/register'
  : '/register'

  try {
    const { data } = await axios.post(endpoint, cleanForm)

    console.log('register response:', data)
   
    return { success: true, data }   // ← inclulde data now

  } catch (err) {
    const serverError = err.response?.data
    error.value = serverError?.message ?? 'Registration failed. Please try again.'
    return { success: false, error: serverError ?? error.value }
  } finally {
    loading.value = false
  }
}



 return {
    user, guard, checked, loading, error,
    schoolId, schools, needsSchool, wardId, wards, needsWard,
    isLoggedIn, isGuest, isStaff, isStudent, isGuardian, isAdmin, isAffiliate,
    role, authtype, world, needsPick,
    me, login, logout, switchSchool, switchWard, register,activeSchool,
  }
})
