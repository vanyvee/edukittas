import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useAuthStore = defineStore('auth', () => {

  // ─── State ────────────────────────────────────────────────────────────
  const user    = ref(null)
  const guard    = ref(null)
 // const role    = ref(null)
  const checked = ref(false)  // has me() finished at least once?
  const loading = ref(false)
  const error   = ref(null)

  // ─── Computed ─────────────────────────────────────────────────────────
  const isLoggedIn       = computed(() => !!user.value)
  const isStaff          = computed(() => guard.value === 'web')
  const isCustomer       = computed(() => guard.value === 'customer')
  const isAdmin          = computed(() => guard.value === 'admin')
  const isGuest          = computed(() => !user.value)
  const isCeo            = computed(() => user.value?.role === 'ceo')
  const isCook           = computed(() => user.value?.role === 'cook')
  const isCashier        = computed(() => user.value?.role === 'cashier')
  const isSupervisor     = computed(() => user.value?.role === 'supervisor')
  const isWaiter         = computed(() => user.value?.role === 'waiter')
  const canViewAnalytics = computed(() => ['ceo', 'supervisor'].includes(user.value?.role))
  const canCancelOrder   = computed(() => user.value?.role === 'ceo')
  const canMarkReady     = computed(() => ['ceo', 'supervisor'].includes(user.value?.role))

  // ─── Me — call on app boot ────────────────────────────────────────────
  const me = async () => {
    try {
      const { data } = await axios.get('/me')
      user.value = data.user ?? null
      guard.value = data.guard ?? null
    } catch (_) {
      user.value = null
    } finally {
      checked.value = true  // always flip — success or fail
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
  // ─── Login ────────────────────────────────────────────────────────────
  const login = async (username, password) => {
    error.value   = null
    loading.value = true

    try {
      const { data } = await axios.post('/login', { username, password })
      console.log('response:', data)
      user.value    = data.user
      guard.value = data.guard
      checked.value = true
      return { success: true }

    } catch (err) {
      error.value = err.response?.data?.message ?? 'Invalid credentials.'
      return { success: false, error: err.response?.data ?? error.value }

    } finally {
      loading.value = false
    }
  }

  // ─── Logout ───────────────────────────────────────────────────────────
 const logout = async () => {
  loading.value = true
  try {
    await axios.post('/logout')
  } catch (_) {
    // never block logout
  } finally {
    user.value    = null
    guard.value = null
    // checked stays true — we know the auth state, it's just "no user"
    loading.value = false
    error.value   = null
  }
}

  return {
    user, guard, checked, loading, error,
    isLoggedIn, isStaff, isCustomer, isGuest,
    isCeo, isCook, isCashier, isSupervisor,isAdmin,
    canViewAnalytics, canCancelOrder, canMarkReady, isWaiter,
    me, register, login, logout,
  }
})