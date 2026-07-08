import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useAuthStore = defineStore('auth', () => {

  // ─── State ────────────────────────────────────────────────────────────
  const user    = ref(null)
  const checked = ref(false)  // has me() finished at least once?
  const loading = ref(false)
  const error   = ref(null)

  // ─── Computed ─────────────────────────────────────────────────────────
  const isLoggedIn       = computed(() => !!user.value)
  const isStaff          = computed(() => user.value?.guard === 'staff')
  const isCustomer       = computed(() => user.value?.guard === 'customer')
  const isAdmin          = computed(() => user.value?.guard === 'admin')
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
      const { data } = await axios.get('/auth/me')
      user.value = data.user ?? null
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
  // endpoint determined by who is logged in
  // const endpoint = auth.isAdmin?.value || auth.canViewAnalytics?.value
  //   ? '/staff/register'
  //   : '/register'
const endpoint = ['staff', 'admin'].includes(user.value?.guard)
  ? '/staff/register'
  : '/register'

  try {
    const { data } = await axios.post(endpoint, cleanForm)

    console.log('register response:', data)
    // only set user if customer self-registering
    // staff registration doesn't log in as that staff
    if (!isLoggedIn.value) {
      user.value = data.user
    }
  

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
      checked.value = false
      loading.value = false
      error.value   = null
    }
  }

  return {
    user, checked, loading, error,
    isLoggedIn, isStaff, isCustomer, isGuest,
    isCeo, isCook, isCashier, isSupervisor,
    canViewAnalytics, canCancelOrder, canMarkReady, isWaiter,
    me, register, login, logout,
  }
})