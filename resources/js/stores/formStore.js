import { defineStore } from 'pinia'
import { reactive }    from 'vue'
import { useForm }     from 'vee-validate'
import * as yup        from 'yup'

export const useFormStore = defineStore('formStore', () => {

  // ─── Master Default Values ────────────────────────────────────────────
  const masterDefaults = reactive({
    // Identity
    first_name:            '',
    surname:               '',
    username:              '',
    email:                 '',
    phone:                 '',
    password:              '',
    password_confirmation: '',
    sex:                '',
    dob_day:               '',
    dob_month:             '',
    dob_year:              '',
    address:               '',
    dob:                   '',

    // Customer
    location:              '',

    // Staff
    role:                  '',

    // School (kept for portability)
    full_name:             '',
    admission_number:      '',
    class_level:           '',
    guardian_name:         '',
    guardian_phone:        '',
    staff_id:              '',
    qualification:         '',
    specialization:        '',
    years_of_experience:   '',
    school_name:           '',
    school_code:           '',
    school_address:        '',

    // Generic
    title:                 '',
    description:           '',
    note:                  '',
    amount:                '',
    quantity:              '',
  })


  // ─── Master Schema ────────────────────────────────────────────────────
  const masterSchema = yup.object({
    // Identity
    first_name: yup.string()
      .required('First name is required'),

    surname: yup.string()
      .required('Surname is required'),

    username: yup.string()
      .required('Username is required')
      .min(3, 'Username must be at least 3 characters'),

    email: yup.string()
      .email('Enter a valid email'),

    phone: yup.string()
      .required('Phone number is required')
      .min(10, 'Enter a valid phone number'),

    password: yup.string()
      .required('Password is required')
      .min(6, 'Password must be at least 6 characters'),

    password_confirmation: yup.string()
      .required('Please confirm your password')
      .oneOf([yup.ref('password')], 'Passwords do not match'),

    sex: yup.string(),
    dob_day:   yup.number().min(1).max(31),
    dob_month: yup.number().min(1).max(12),
    dob_year:  yup.number().min(1900).max(new Date().getFullYear()),
    address:   yup.string(),
    location:  yup.string(),
    role:      yup.string(),

    // School
    full_name:           yup.string(),
    admission_number:    yup.string(),
    class_level:         yup.string(),
    guardian_name:       yup.string(),
    guardian_phone:      yup.string().min(10),
    staff_id:            yup.string(),
    qualification:       yup.string(),
    specialization:      yup.string(),
    years_of_experience: yup.string(),
    school_name:         yup.string(),
    school_code:         yup.string(),
    school_address:      yup.string(),

    // Generic
    title:       yup.string(),
    description: yup.string(),
    note:        yup.string(),
    amount:      yup.number().min(0),
    quantity:    yup.number().min(1),
  })


  // ─── Build Form ───────────────────────────────────────────────────────
  // Pass only the field keys you need for this specific form
  // Returns a full vee-validate form instance
  const buildForm = (fieldList = []) => {
    const initialValues = {}
    const schemaShape   = {}

    fieldList.forEach(key => {
      initialValues[key] = masterDefaults[key] ?? ''
      if (masterSchema.fields[key]) {
        schemaShape[key] = masterSchema.fields[key]
      }
    })

    return useForm({
      initialValues,
      validationSchema: yup.object(schemaShape),
    })
  }


  // ─── Apply Laravel Server Errors ─────────────────────────────────────
  // Laravel returns { errors: { field: ['message'] } }
  const applyServerErrors = (formInstance, errorBag) => {
    const processed = {}
    const errors    = errorBag?.errors ?? errorBag

    for (const key in errors) {
      processed[key] = Array.isArray(errors[key])
        ? errors[key][0]
        : errors[key]
    }

    formInstance.setErrors(processed)
  }


  // ─── Reset a form back to defaults ───────────────────────────────────
  const resetForm = (formInstance, fieldList = []) => {
    const values = {}
    fieldList.forEach(key => { values[key] = masterDefaults[key] ?? '' })
    formInstance.resetForm({ values })
  }


  return {
    masterDefaults,
    masterSchema,
    buildForm,
    applyServerErrors,
    resetForm,
  }
})