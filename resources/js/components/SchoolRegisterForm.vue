<script setup>
/**
 * Register a school: 4 steps -> School, You, Images, Review.
 * Sends ONE multipart request to POST /public/schools/register (fields + images together).
 * Emits `done` with { message, school, username } on success, `go-login` from the final screen.
 */
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'
import { useThemeStore } from '@/stores/theme'
import BaseButton from './BaseButton.vue'
import BaseCard from './BaseCard.vue'
import RegField from './register/RegField.vue'
import LgaPicker from './register/LgaPicker.vue'
import ImagePicker from './register/ImagePicker.vue'

const emit  = defineEmits(['done', 'go-login'])
const theme = useThemeStore()

// ─── lists the dropdowns need ───────────────────────────────────────────
const lgas = ref([])
const departments = ref([])
const loadingLists = ref(true)
const listError = ref('')

async function loadLists() {
  loadingLists.value = true
  listError.value = ''
  try {
    const [a, b] = await Promise.all([axios.get('/public/lgas'), axios.get('/public/departments')])
    lgas.value = a.data
    departments.value = b.data
  } catch {
    listError.value = 'Could not load the form lists. Check your connection and try again.'
  } finally {
    loadingLists.value = false
  }
}

// ─── form state ─────────────────────────────────────────────────────────
const form = reactive({
  school: {
    name: '', address: '', phone: '', email: '', dob: '', lg_id: '',
    current_session: new Date().getFullYear(), current_term: '1', theme: 'default',
  },
  user: {
    role: 'director', surname: '', first_name: '', other_name: '', sex: '', dob: '',
    phone: '', email: '', address: '', lg_id: '', username: '', password: '', password_confirmation: '',
  },
  department_ids: [],
})
const files = reactive({ logo: null, pix: null, photos: [], passport: null })

const ROLES = [
  { id: 'director',  label: 'Director' },
  { id: 'principal', label: 'Principal' },
  { id: 'ictAdmin',  label: 'ICT Admin' },
]

// ─── steps ──────────────────────────────────────────────────────────────
const STEPS = ['School', 'You', 'Images', 'Review']
const step = ref(0)
const top  = ref(null)
watch(step, () => top.value?.scrollIntoView({ behavior: 'smooth', block: 'start' }))

// ─── errors: { 'school.name': ['msg'], logo: ['msg'], ... } ─────────────
const errors = ref({})
const banner = ref('')
const e = (key) => {
  const v = errors.value[key]
  return Array.isArray(v) ? v[0] : (v ?? '')
}
const eStartsWith = (prefix) => {
  const k = Object.keys(errors.value).find((x) => x === prefix || x.startsWith(prefix + '.'))
  return k ? e(k) : ''
}
// editing anything clears the old error messages
watch(form, () => { if (Object.keys(errors.value).length) errors.value = {} }, { deep: true })
watch(files, () => { if (Object.keys(errors.value).length) errors.value = {} }, { deep: true })

const stepOf = (key) =>
  key.startsWith('school.') ? 0
  : key.startsWith('user.') || key.startsWith('department_ids') ? 1
  : 2
const firstStep = (errs) => Math.min(...Object.keys(errs).map(stepOf), 3)

const filled = (v) => String(v ?? '').trim() !== ''

function validateStep(n) {
  const er = {}
  const s = form.school, u = form.user
  const need = (key, val, msg = 'Required') => { if (!filled(val)) er[key] = [msg] }

  if (n === 0) {
    need('school.name', s.name)
    need('school.address', s.address)
    need('school.dob', s.dob)
    need('school.lg_id', s.lg_id, 'Pick the state and LGA')
    if (!/^\d{10,11}$/.test(String(s.phone).replace(/\D/g, ''))) er['school.phone'] = ['Enter 10 or 11 digits']
    if (!/^\S+@\S+\.\S+$/.test(s.email)) er['school.email'] = ['Enter a valid email']
    if (!/^\d{4}$/.test(String(s.current_session))) er['school.current_session'] = ['Enter a 4-digit year']
  }
  if (n === 1) {
    need('user.surname', u.surname)
    need('user.first_name', u.first_name)
    need('user.sex', u.sex, 'Choose one')
    need('user.dob', u.dob)
    need('user.address', u.address)
    need('user.lg_id', u.lg_id, 'Pick the state and LGA')
    if (!/^\d{10,11}$/.test(String(u.phone).replace(/\D/g, ''))) er['user.phone'] = ['Enter 10 or 11 digits']
    if (filled(u.email) && !/^\S+@\S+\.\S+$/.test(u.email)) er['user.email'] = ['Enter a valid email']
    if (!/^[A-Za-z0-9_-]{1,20}$/.test(u.username)) er['user.username'] = ['Letters, numbers, - and _ only (max 20)']
    if (u.password.length < 8) er['user.password'] = ['At least 8 characters']
    else if (u.password !== u.password_confirmation) er['user.password'] = ['The two passwords do not match']
    if (!form.department_ids.length) er['department_ids'] = ['Pick at least one department']
  }
  if (n === 2 && !files.logo) er.logo = ['Add your school logo']
  return er
}

function next() {
  const er = validateStep(step.value)
  errors.value = er
  banner.value = ''
  if (!Object.keys(er).length) step.value++
}
const back = () => { banner.value = ''; step.value = Math.max(0, step.value - 1) }

// ─── theme choice (previews live; undone if the person leaves) ──────────
let themeBefore = ''
onMounted(() => { themeBefore = theme.active; loadLists() })
onBeforeUnmount(() => theme.apply(themeBefore))
const pickTheme = (item) => { form.school.theme = item.id || 'default'; theme.apply(item.id) }

const toggleDept = (id) => {
  const i = form.department_ids.indexOf(id)
  i === -1 ? form.department_ids.push(id) : form.department_ids.splice(i, 1)
}

// ─── send ───────────────────────────────────────────────────────────────
const submitting = ref(false)
const result = ref(null)

function buildFormData() {
  const fd = new FormData()
  const put = (prefix, obj) => {
    for (const [k, v] of Object.entries(obj)) if (v !== '' && v != null) fd.append(`${prefix}[${k}]`, v)
  }
  put('school', form.school)
  put('user', form.user)
  form.department_ids.forEach((id) => fd.append('department_ids[]', id))
  if (files.logo)     fd.append('logo', files.logo)
  if (files.pix)      fd.append('pix', files.pix)
  if (files.passport) fd.append('passport', files.passport)
  files.photos.forEach((f) => fd.append('photos[]', f))
  return fd
}

async function submit() {
  banner.value = ''
  const all = { ...validateStep(0), ...validateStep(1), ...validateStep(2) }
  if (Object.keys(all).length) {
    errors.value = all
    step.value = firstStep(all)
    banner.value = 'Please fix the highlighted fields.'
    return
  }

  submitting.value = true
  try {
    const { data } = await axios.post('/public/schools/register', buildFormData())
    result.value = data
    emit('done', data)
  } catch (err) {
    const r = err.response
    if (r?.status === 422) {
      errors.value = r.data.errors ?? {}
      step.value = firstStep(errors.value)
      banner.value = 'Please fix the highlighted fields.'
    } else if (r?.status === 429) {
      banner.value = 'Too many attempts. Please wait a few minutes and try again.'
    } else {
      banner.value = r?.data?.message ?? 'Could not reach the server. Check your connection and try again.'
    }
  } finally {
    submitting.value = false
  }
}

const lgaName = (id) => {
  const l = lgas.value.find((x) => String(x.id) === String(id))
  return l ? `${l.name}, ${l.state}` : ''
}
const roleLabel = computed(() => ROLES.find((r) => r.id === form.user.role)?.label ?? '')
const deptNames = computed(() => departments.value.filter((d) => form.department_ids.includes(d.id)).map((d) => d.name).join(', '))
const themeName = computed(() => theme.themes.find((t) => (t.id || 'default') === form.school.theme)?.name ?? '')
const fileLine = (f) => (f ? `${f.name} (${(f.size / 1024 / 1024).toFixed(1)} MB)` : 'None')
</script>

<template>
  <div ref="top" class="w-full max-w-xl mx-auto p-3 sm:p-4">
    <BaseCard padding="md">

      <!-- ═══ Finished ═══ -->
      <div v-if="result" class="flex flex-col items-center text-center gap-3 py-6">
        <div class="text-5xl">🎉</div>
        <h1 class="text-xl font-bold">{{ form.school.name }} is registered</h1>
        <p class="opacity-80 text-sm">
          Log in with the username <strong>{{ result.username }}</strong> and the password you chose.
        </p>
        <BaseButton class="mt-2" size="lg" @click="emit('go-login')">Go to login</BaseButton>
      </div>

      <template v-else>
        <h1 class="text-xl font-bold">Register your school</h1>

        <!-- progress -->
        <div class="mt-3 mb-1 flex gap-1.5" aria-hidden="true">
          <span
            v-for="(s, i) in STEPS" :key="s" class="h-1.5 flex-1 rounded-full transition-colors"
            :style="{ background: i <= step ? 'var(--primary-500)' : 'color-mix(in oklch, currentColor 20%, transparent)' }"
          ></span>
        </div>
        <p class="text-xs opacity-70 mb-4">Step {{ step + 1 }} of {{ STEPS.length }}: {{ STEPS[step] }}</p>

        <div v-if="loadingLists" class="py-10 text-center opacity-70 text-sm">Loading…</div>
        <div v-else-if="listError" class="py-8 text-center">
          <p class="text-sm mb-3" style="color: var(--danger-500)">{{ listError }}</p>
          <BaseButton variant="ghost" @click="loadLists">Try again</BaseButton>
        </div>

        <form v-else novalidate @submit.prevent>

          <!-- ═══ STEP 1: School ═══ -->
          <div v-show="step === 0" class="flex flex-col gap-4">
            <RegField label="School name" required :error="e('school.name')">
              <input v-model="form.school.name" class="in" :class="{ bad: e('school.name') }" maxlength="100" autocomplete="organization" />
            </RegField>

            <RegField label="Address" required :error="e('school.address')" :hint="`${form.school.address.length}/30`">
              <input v-model="form.school.address" class="in" :class="{ bad: e('school.address') }" maxlength="30" />
            </RegField>

            <RegField label="State and LGA" required :error="e('school.lg_id')">
              <LgaPicker v-model="form.school.lg_id" :lgas="lgas" :invalid="!!e('school.lg_id')" />
            </RegField>

            <div class="grid grid-cols-2 gap-3">
              <RegField label="Phone" required :error="e('school.phone')">
                <input v-model="form.school.phone" class="in" :class="{ bad: e('school.phone') }" inputmode="numeric" maxlength="11" autocomplete="tel" />
              </RegField>
              <RegField label="Date founded" required :error="e('school.dob')">
                <input v-model="form.school.dob" type="date" class="in" :class="{ bad: e('school.dob') }" />
              </RegField>
            </div>

            <RegField label="School email" required :error="e('school.email')">
              <input v-model="form.school.email" type="email" class="in" :class="{ bad: e('school.email') }" maxlength="50" autocomplete="email" />
            </RegField>

            <div class="grid grid-cols-2 gap-3">
              <RegField label="Current session" required :error="e('school.current_session')" hint="Year, e.g. 2026">
                <input v-model="form.school.current_session" class="in" :class="{ bad: e('school.current_session') }" inputmode="numeric" maxlength="4" />
              </RegField>
              <RegField label="Current term" required :error="e('school.current_term')">
                <select v-model="form.school.current_term" class="in">
                  <option value="1">First term</option>
                  <option value="2">Second term</option>
                  <option value="3">Third term</option>
                </select>
              </RegField>
            </div>

            <RegField label="School colour" :error="e('school.theme')" hint="You can change it later in Settings.">
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                <button
                  v-for="t in theme.themes" :key="t.id || 'default'" type="button" class="theme-card"
                  :aria-pressed="(t.id || 'default') === form.school.theme" @click="pickTheme(t)"
                >
                  <span class="flex h-7 rounded-md overflow-hidden" :data-theme="t.id || 'default'" aria-hidden="true">
                    <i class="flex-1" style="background: var(--primary-500)"></i>
                    <i class="flex-1" style="background: var(--secondary-500)"></i>
                    <i class="flex-1" style="background: var(--accent-500)"></i>
                    <i class="flex-1" style="background: var(--surface-300)"></i>
                  </span>
                  <span class="text-xs font-semibold text-left">{{ t.name }}</span>
                </button>
              </div>
            </RegField>
          </div>

          <!-- ═══ STEP 2: You ═══ -->
          <div v-show="step === 1" class="flex flex-col gap-4">
            <RegField label="Your role" required :error="e('user.role')">
              <select v-model="form.user.role" class="in">
                <option v-for="r in ROLES" :key="r.id" :value="r.id">{{ r.label }}</option>
              </select>
            </RegField>

            <div class="grid grid-cols-2 gap-3">
              <RegField label="Surname" required :error="e('user.surname')">
                <input v-model="form.user.surname" class="in" :class="{ bad: e('user.surname') }" maxlength="20" autocomplete="family-name" />
              </RegField>
              <RegField label="First name" required :error="e('user.first_name')">
                <input v-model="form.user.first_name" class="in" :class="{ bad: e('user.first_name') }" maxlength="20" autocomplete="given-name" />
              </RegField>
            </div>

            <RegField label="Other name" :error="e('user.other_name')">
              <input v-model="form.user.other_name" class="in" maxlength="20" />
            </RegField>

            <div class="grid grid-cols-2 gap-3">
              <RegField label="Sex" required :error="e('user.sex')">
                <select v-model="form.user.sex" class="in" :class="{ bad: e('user.sex') }">
                  <option value="" disabled>Choose</option>
                  <option value="m">Male</option>
                  <option value="f">Female</option>
                </select>
              </RegField>
              <RegField label="Date of birth" required :error="e('user.dob')">
                <input v-model="form.user.dob" type="date" class="in" :class="{ bad: e('user.dob') }" />
              </RegField>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <RegField label="Phone" required :error="e('user.phone')">
                <input v-model="form.user.phone" class="in" :class="{ bad: e('user.phone') }" inputmode="numeric" maxlength="11" />
              </RegField>
              <RegField label="Email" :error="e('user.email')" hint="Optional">
                <input v-model="form.user.email" type="email" class="in" :class="{ bad: e('user.email') }" maxlength="50" />
              </RegField>
            </div>

            <RegField label="Home address" required :error="e('user.address')">
              <input v-model="form.user.address" class="in" :class="{ bad: e('user.address') }" />
            </RegField>

            <RegField label="State and LGA" required :error="e('user.lg_id')">
              <LgaPicker v-model="form.user.lg_id" :lgas="lgas" :invalid="!!e('user.lg_id')" />
            </RegField>

            <RegField label="Username" required :error="e('user.username')" hint="What you will log in with">
              <input v-model="form.user.username" class="in" :class="{ bad: e('user.username') }" maxlength="20" autocomplete="username" autocapitalize="none" />
            </RegField>

            <div class="grid grid-cols-2 gap-3">
              <RegField label="Password" required :error="e('user.password')" hint="At least 8 characters">
                <input v-model="form.user.password" type="password" class="in" :class="{ bad: e('user.password') }" autocomplete="new-password" />
              </RegField>
              <RegField label="Repeat password" required>
                <input v-model="form.user.password_confirmation" type="password" class="in" :class="{ bad: e('user.password') }" autocomplete="new-password" />
              </RegField>
            </div>

            <RegField label="Departments you belong to" required :error="e('department_ids') || eStartsWith('department_ids')">
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="d in departments" :key="d.id" type="button" class="chip"
                  :aria-pressed="form.department_ids.includes(d.id)" @click="toggleDept(d.id)"
                >{{ d.name }}</button>
              </div>
            </RegField>
          </div>

          <!-- ═══ STEP 3: Images ═══ -->
          <div v-show="step === 2" class="flex flex-col gap-5">
            <ImagePicker
              v-model="files.logo" label="School logo" required :max-mb="2" :min-w="100" :min-h="100"
              aspect="aspect-square" hint="Square works best. JPG, PNG or WebP, up to 2 MB."
              :error="e('logo')" class="max-w-[10rem]"
            />
            <ImagePicker
              v-model="files.pix" label="Cover image" :max-mb="5" :min-w="600" :min-h="300"
              aspect="aspect-video" hint="Wide photo of the school, at least 600×300. Up to 5 MB."
              :error="e('pix')"
            />
            <ImagePicker
              v-model="files.photos" label="More photos" multiple :max="4" :max-mb="5" :min-w="400" :min-h="300"
              aspect="aspect-[4/3]" hint="Up to 4 photos, at least 400×300 each."
              :error="eStartsWith('photos')"
            />
            <ImagePicker
              v-model="files.passport" label="Your passport photo" :max-mb="2" :min-w="150" :min-h="150"
              aspect="aspect-square" hint="Optional. Up to 2 MB." :error="e('passport')" class="max-w-[10rem]"
            />
          </div>

          <!-- ═══ STEP 4: Review ═══ -->
          <div v-show="step === 3" class="flex flex-col gap-4 text-sm">
            <section>
              <h2 class="font-bold mb-1">School</h2>
              <p>{{ form.school.name }}</p>
              <p class="opacity-80">{{ form.school.address }} · {{ lgaName(form.school.lg_id) }}</p>
              <p class="opacity-80">{{ form.school.phone }} · {{ form.school.email }}</p>
              <p class="opacity-80">Session {{ form.school.current_session }}, term {{ form.school.current_term }} · Colour: {{ themeName }}</p>
            </section>
            <section>
              <h2 class="font-bold mb-1">You</h2>
              <p>{{ form.user.surname }} {{ form.user.first_name }} {{ form.user.other_name }} · {{ roleLabel }}</p>
              <p class="opacity-80">Username: {{ form.user.username }}</p>
              <p class="opacity-80">Departments: {{ deptNames }}</p>
            </section>
            <section>
              <h2 class="font-bold mb-1">Images</h2>
              <p class="opacity-80">Logo: {{ fileLine(files.logo) }}</p>
              <p class="opacity-80">Cover: {{ fileLine(files.pix) }}</p>
              <p class="opacity-80">More photos: {{ files.photos.length }}</p>
              <p class="opacity-80">Passport: {{ fileLine(files.passport) }}</p>
            </section>
          </div>

          <p v-if="banner" class="mt-4 text-sm" style="color: var(--danger-500)" role="alert">{{ banner }}</p>

          <!-- buttons -->
          <div class="mt-6 flex gap-3">
            <BaseButton v-if="step > 0" variant="ghost" :disabled="submitting" @click="back">Back</BaseButton>
            <BaseButton v-if="step < STEPS.length - 1" class="flex-1" @click="next">Next</BaseButton>
            <BaseButton v-else class="flex-1" variant="success" :loading="submitting" @click="submit">Register school</BaseButton>
          </div>
        </form>
      </template>

    </BaseCard>
  </div>
</template>

<style scoped>
.in {
  width: 100%; padding: .65rem .8rem; border-radius: .6rem; font-size: .9rem; color: inherit; outline: none;
  background: color-mix(in oklch, currentColor 7%, transparent);
  border: 1px solid color-mix(in oklch, currentColor 25%, transparent);
}
.in:focus { border-color: var(--primary-500); box-shadow: 0 0 0 1px var(--primary-500); }
.in.bad   { border-color: var(--danger-500); }
.in option { color: var(--surface-900); background: var(--surface-300); }

.theme-card {
  display: flex; flex-direction: column; gap: .4rem; padding: .5rem; border-radius: .8rem; cursor: pointer;
  border: 1px solid color-mix(in oklch, currentColor 20%, transparent);
  background: transparent; color: inherit; -webkit-tap-highlight-color: transparent;
}
.theme-card[aria-pressed="true"] { outline: 2px solid var(--primary-500); outline-offset: 1px; }

.chip {
  padding: .45rem .9rem; border-radius: 999px; font-size: .8rem; font-weight: 600; cursor: pointer;
  border: 1px solid color-mix(in oklch, currentColor 25%, transparent); background: transparent; color: inherit;
}
.chip[aria-pressed="true"] { background: var(--primary-500); color: var(--on-primary); border-color: transparent; }
</style>
