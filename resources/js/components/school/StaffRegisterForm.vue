<script setup>
/**
 * Register a staff member for the school you are working in (leaders only: the route is behind can:manage-staff).
 * You do NOT choose a username or password. The person gets an email with a signed link and sets them
 * there; their account turns active then (see pages/staff/activate/[uuid].vue).
 *
 * One endpoint does it all: POST /staff.
 *   - New person         -> 'created'; an email goes out.
 *   - Already has an account (same phone or email) -> 'confirmation_required'. This form asks you, and if you
 *     agree it posts the SAME form again with confirmed=1 and the person is only added to your school.
 */
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import BaseButton from './BaseButton.vue'
import BaseCard from './BaseCard.vue'
import RegField from './register/RegField.vue'
import LgaPicker from './register/LgaPicker.vue'
import ImagePicker from './register/ImagePicker.vue'

const emit = defineEmits(['registered'])

// ─── dropdown lists ─────────────────────────────────────────────────────
const lgas = ref([])
const departments = ref([])
const qualifications = ref([])
const loadingLists = ref(true)
const listError = ref('')

async function loadLists() {
  loadingLists.value = true
  listError.value = ''
  try {
    const { data } = await axios.get('/staff/options')
    lgas.value = data.lgas
    departments.value = data.departments
    qualifications.value = data.qualifications
  } catch {
    listError.value = 'Could not load the form lists. Check your connection and try again.'
  } finally {
    loadingLists.value = false
  }
}
onMounted(loadLists)

const ROLES = [
  { id: 'teacher',        label: 'Teacher' },
  { id: 'bursar',         label: 'Bursar' },
  { id: 'vice principal', label: 'Vice principal' },
  { id: 'principal',      label: 'Principal' },
  { id: 'ictAdmin',       label: 'ICT admin' },
  { id: 'director',       label: 'Director' },
  { id: 'security',       label: 'Security' },
  { id: 'other',          label: 'Other' },
]

// ─── form ───────────────────────────────────────────────────────────────
const blank = () => ({
  surname: '', first_name: '', other_name: '', sex: '', dob: '',
  phone: '', email: '', recovery_phone: '', address: '', lg_id: '',
  role: 'teacher', department_ids: [], qualification: '',
  salary: '', account_number: '', bank_name: '',
})
const form = reactive(blank())
const passport = ref(null)

const errors = ref({})
const banner = ref('')
const e = (k) => (Array.isArray(errors.value[k]) ? errors.value[k][0] : (errors.value[k] ?? ''))

const submitting = ref(false)
const resending = ref(false)
const outcome = ref(null)   // { type: 'created' | 'confirm' | 'added' | 'exists', ... }

const filled = (v) => String(v ?? '').trim() !== ''

function validate() {
  const er = {}
  const need = (k, msg = 'Required') => { if (!filled(form[k])) er[k] = [msg] }
  ;['surname', 'first_name', 'address', 'dob'].forEach((k) => need(k))
  need('sex', 'Choose one')
  need('lg_id', 'Pick the state and LGA')
  if (!form.department_ids.length) er.department_ids = ['Choose at least one department']
  need('qualification', 'Choose one')
  if (!/^\d{10,11}$/.test(form.phone.replace(/\D/g, ''))) er.phone = ['Enter 10 or 11 digits']
  if (filled(form.recovery_phone) && !/^\d{10,11}$/.test(form.recovery_phone.replace(/\D/g, ''))) er.recovery_phone = ['Enter 10 or 11 digits']
  if (!/^\S+@\S+\.\S+$/.test(form.email)) er.email = ['Enter a valid email: the invitation goes here']
  if (filled(form.salary) && !/^\d{1,7}$/.test(form.salary)) er.salary = ['Digits only, up to 7']
  if (filled(form.account_number) && !/^\d{10}$/.test(form.account_number)) er.account_number = ['Exactly 10 digits']
  return er
}

function buildFormData(confirmed) {
  const fd = new FormData()
  for (const [k, v] of Object.entries(form)) if (k !== 'department_ids' && v !== '' && v != null) fd.append(k, v)
  form.department_ids.forEach((id) => fd.append('department_ids[]', id))
  if (passport.value) fd.append('passport', passport.value)
  if (confirmed) fd.append('confirmed', '1')
  return fd
}

// `confirmed` is true only when the admin said yes on the confirmation screen
async function submit(confirmed = false) {
  banner.value = ''
  if (!confirmed) {
    const er = validate()
    errors.value = er
    if (Object.keys(er).length) { banner.value = 'Please fix the highlighted fields.'; return }
  }

  submitting.value = true
  try {
    const { data } = await axios.post('/staff', buildFormData(confirmed))
    switch (data.status) {
      case 'created':
        outcome.value = { type: 'created', uuid: data.id, invited: data.invited, email: form.email }
        emit('registered', data)
        break
      case 'confirmation_required':
        outcome.value = { type: 'confirm', person: data.user, inSchool: data.in_school }
        break
      case 'added':
        outcome.value = { type: 'added', name: data.name, emailed: data.emailed }
        emit('registered', data)
        break
      default:
        outcome.value = { type: 'exists' }
    }
  } catch (err) {
    const r = err.response
    if (r?.status === 422) {
      errors.value = r.data.errors ?? {}
      banner.value = 'Please fix the highlighted fields.'
      outcome.value = null
    } else if (r?.status === 403) {
      banner.value = 'You do not have permission to register staff.'
    } else {
      banner.value = r?.data?.message ?? 'Could not reach the server. Check your connection and try again.'
    }
  } finally {
    submitting.value = false
  }
}

async function resend() {
  resending.value = true
  try {
    const { data } = await axios.post(`/staff/${outcome.value.uuid}/invitation`)
    outcome.value = { ...outcome.value, invited: data.invited }
  } catch {
    banner.value = 'Could not send the email. Please try again.'
  } finally {
    resending.value = false
  }
}

function reset() {
  Object.assign(form, blank())
  passport.value = null
  errors.value = {}
  banner.value = ''
  outcome.value = null
}

// back to the form with everything still filled in
const backToForm = () => { banner.value = ''; outcome.value = null }

const MAX_DEPARTMENTS = 2
const toggleDepartment = (id) => {
  const i = form.department_ids.indexOf(id)
  if (i !== -1) form.department_ids.splice(i, 1)
  else if (form.department_ids.length < MAX_DEPARTMENTS) form.department_ids.push(id)
}
const eDepartments = () => e('department_ids') || e('department_ids.0') || e('department_ids.1')

const fullName = (p) => [p.surname, p.first_name, p.other_name].filter(Boolean).join(' ')
</script>

<template>
  <div class="w-full max-w-xl mx-auto p-3 sm:p-4">
    <BaseCard padding="md">

      <!-- ═══ Registered, invitation sent (or not) ═══ -->
      <div v-if="outcome?.type === 'created'" class="flex flex-col items-center text-center gap-3 py-4">
        <div class="text-5xl">{{ outcome.invited ? '📧' : '⚠️' }}</div>
        <h2 class="text-xl font-bold">{{ outcome.invited ? 'Invitation sent' : 'Registered, but the email did not go out' }}</h2>
        <p v-if="outcome.invited" class="text-sm opacity-80">
          We emailed <strong>{{ outcome.email }}</strong>. They choose their own username and password from the link,
          and their account becomes active then. The link works for 7 days.
        </p>
        <template v-else>
          <p class="text-sm opacity-80">The account is saved, but the email to {{ outcome.email }} could not be sent.</p>
          <p v-if="banner" class="text-sm" style="color: var(--danger-500)" role="alert">{{ banner }}</p>
          <BaseButton :loading="resending" @click="resend">Send the email again</BaseButton>
        </template>
        <div class="flex gap-3 w-full mt-2">
          <BaseButton v-if="outcome.invited" variant="ghost" class="flex-1" :loading="resending" @click="resend">Send again</BaseButton>
          <BaseButton class="flex-1" @click="reset">Register another</BaseButton>
        </div>
      </div>

      <!-- ═══ Already in this school ═══ -->
      <div v-else-if="outcome?.type === 'exists'" class="flex flex-col items-center text-center gap-3 py-4">
        <div class="text-5xl">ℹ️</div>
        <h2 class="text-xl font-bold">Already in this department</h2>
        <p class="text-sm opacity-80">Someone with this phone number or email is already registered in that department at your school.</p>
        <BaseButton @click="backToForm">Back to the form</BaseButton>
      </div>

      <!-- ═══ Known person: ask, then post the same form again ═══ -->
      <div v-else-if="outcome?.type === 'confirm'" class="flex flex-col items-center text-center gap-3 py-4">
        <div class="text-5xl">🙋</div>
        <h2 class="text-xl font-bold">This person already has an account</h2>
        <p class="text-sm opacity-80">
          <strong>{{ fullName(outcome.person) }}</strong>
          <span v-if="outcome.person.role"> ({{ outcome.person.role }})</span>
          <template v-if="outcome.inSchool">is already on your staff. Add them to this department as well?</template>
          <template v-else>uses this phone number or email. Add them to your school instead of creating a second account?</template>
          Their existing details and login stay as they are.
        </p>
        <p v-if="banner" class="text-sm" style="color: var(--danger-500)" role="alert">{{ banner }}</p>
        <div class="flex gap-3 w-full">
          <BaseButton variant="ghost" class="flex-1" :disabled="submitting" @click="backToForm">Cancel</BaseButton>
          <BaseButton class="flex-1" :loading="submitting" @click="submit(true)">Add to my school</BaseButton>
        </div>
      </div>

      <!-- ═══ Added ═══ -->
      <div v-else-if="outcome?.type === 'added'" class="flex flex-col items-center text-center gap-3 py-4">
        <div class="text-5xl">✅</div>
        <h2 class="text-xl font-bold">{{ outcome.name }} added</h2>
        <p class="text-sm opacity-80">They keep their existing login.
          <template v-if="outcome.emailed === true">We emailed them to say they were added.</template>
          <template v-else-if="outcome.emailed === false">The email to tell them could not be sent, so let them know yourself.</template>
        </p>
        <BaseButton @click="reset">Register another</BaseButton>
      </div>

      <!-- ═══ The form ═══ -->
      <template v-else>
        <h1 class="text-xl font-bold mb-1">Register staff</h1>
        <p class="text-sm opacity-70 mb-4">We email them a link to choose their own username and password.</p>

        <div v-if="loadingLists" class="py-10 text-center opacity-70 text-sm">Loading…</div>
        <div v-else-if="listError" class="py-8 text-center">
          <p class="text-sm mb-3" style="color: var(--danger-500)">{{ listError }}</p>
          <BaseButton variant="ghost" @click="loadLists">Try again</BaseButton>
        </div>

        <form v-else novalidate class="flex flex-col gap-4" @submit.prevent="submit(false)">

          <h2 class="text-sm font-bold opacity-80">Personal</h2>
          <div class="grid grid-cols-2 gap-3">
            <RegField label="Surname" required :error="e('surname')">
              <input v-model="form.surname" class="in" :class="{ bad: e('surname') }" maxlength="20" autocomplete="off" />
            </RegField>
            <RegField label="First name" required :error="e('first_name')">
              <input v-model="form.first_name" class="in" :class="{ bad: e('first_name') }" maxlength="20" autocomplete="off" />
            </RegField>
          </div>
          <RegField label="Other name" :error="e('other_name')">
            <input v-model="form.other_name" class="in" maxlength="20" autocomplete="off" />
          </RegField>
          <div class="grid grid-cols-2 gap-3">
            <RegField label="Sex" required :error="e('sex')">
              <select v-model="form.sex" class="in" :class="{ bad: e('sex') }">
                <option value="" disabled>Choose</option>
                <option value="m">Male</option>
                <option value="f">Female</option>
              </select>
            </RegField>
            <RegField label="Date of birth" required :error="e('dob')">
              <input v-model="form.dob" type="date" class="in" :class="{ bad: e('dob') }" />
            </RegField>
          </div>
          <RegField label="State and LGA" required :error="e('lg_id')">
            <LgaPicker v-model="form.lg_id" :lgas="lgas" :invalid="!!e('lg_id')" />
          </RegField>

          <h2 class="text-sm font-bold opacity-80 mt-2">Contact</h2>
          <RegField label="Email" required :error="e('email')" hint="The invitation is sent here">
            <input v-model="form.email" type="email" class="in" :class="{ bad: e('email') }" maxlength="50" autocomplete="off" />
          </RegField>
          <div class="grid grid-cols-2 gap-3">
            <RegField label="Phone" required :error="e('phone')">
              <input v-model="form.phone" class="in" :class="{ bad: e('phone') }" inputmode="numeric" maxlength="11" autocomplete="off" />
            </RegField>
            <RegField label="Recovery phone" :error="e('recovery_phone')">
              <input v-model="form.recovery_phone" class="in" :class="{ bad: e('recovery_phone') }" inputmode="numeric" maxlength="11" />
            </RegField>
          </div>
          <RegField label="Home address" required :error="e('address')">
            <input v-model="form.address" class="in" :class="{ bad: e('address') }" />
          </RegField>

          <h2 class="text-sm font-bold opacity-80 mt-2">Job</h2>
          <div class="grid grid-cols-2 gap-3">
            <RegField label="Role" required :error="e('role')">
              <select v-model="form.role" class="in">
                <option v-for="r in ROLES" :key="r.id" :value="r.id">{{ r.label }}</option>
              </select>
            </RegField>
          </div>
          <RegField label="Departments" required :error="eDepartments()" hint="Choose up to 2">
            <div class="flex flex-wrap gap-2">
              <button
                v-for="d in departments" :key="d.id" type="button" class="chip"
                :aria-pressed="form.department_ids.includes(d.id)"
                :disabled="!form.department_ids.includes(d.id) && form.department_ids.length >= 2"
                @click="toggleDepartment(d.id)"
              >{{ d.name }}</button>
            </div>
          </RegField>
          <RegField label="Qualification" required :error="e('qualification')">
            <select v-model="form.qualification" class="in" :class="{ bad: e('qualification') }">
              <option value="" disabled>Choose</option>
              <option v-for="q in qualifications" :key="q" :value="q">{{ q }}</option>
            </select>
          </RegField>

          <h2 class="text-sm font-bold opacity-80 mt-2">Pay details <span class="font-normal opacity-70">(optional)</span></h2>
          <div class="grid grid-cols-2 gap-3">
            <RegField label="Salary" :error="e('salary')">
              <input v-model="form.salary" class="in" :class="{ bad: e('salary') }" inputmode="numeric" maxlength="7" />
            </RegField>
            <RegField label="Bank" :error="e('bank_name')">
              <input v-model="form.bank_name" class="in" maxlength="20" />
            </RegField>
          </div>
          <RegField label="Account number" :error="e('account_number')">
            <input v-model="form.account_number" class="in" :class="{ bad: e('account_number') }" inputmode="numeric" maxlength="10" />
          </RegField>

          <ImagePicker
            v-model="passport" label="Passport photo" :max-mb="2" :min-w="150" :min-h="150"
            aspect="aspect-square" hint="Optional. JPG, PNG or WebP, up to 2 MB." :error="e('passport')" class="max-w-[10rem]"
          />

          <p v-if="banner" class="text-sm" style="color: var(--danger-500)" role="alert">{{ banner }}</p>

          <BaseButton type="submit" size="lg" block :loading="submitting">Register and send invitation</BaseButton>
        </form>
      </template>

    </BaseCard>
  </div>
</template>

<style scoped>
.chip {
  padding: .45rem .9rem; border-radius: 999px; font-size: .8rem; font-weight: 600; cursor: pointer;
  border: 1px solid color-mix(in oklch, currentColor 25%, transparent); background: transparent; color: inherit;
}
.chip[aria-pressed="true"] { background: var(--primary-500); color: var(--on-primary); border-color: transparent; }
.chip:disabled { opacity: .4; cursor: not-allowed; }
.in {
  width: 100%; padding: .65rem .8rem; border-radius: .6rem; font-size: .9rem; color: inherit; outline: none;
  background: color-mix(in oklch, currentColor 7%, transparent);
  border: 1px solid color-mix(in oklch, currentColor 25%, transparent);
}
.in:focus { border-color: var(--primary-500); box-shadow: 0 0 0 1px var(--primary-500); }
.in.bad   { border-color: var(--danger-500); }
.in option { color: var(--surface-900); background: var(--surface-300); }
</style>
