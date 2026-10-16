import { defineStore } from "pinia";
import * as yup from "yup";
import { useForm } from "vee-validate";
import { reactive } from "vue";

export const useGlobalFormStore = defineStore("globalFormStore", () => {

  // -----------------------------------
  // MASTER DEFAULT VALUES (ONE SOURCE)
  // -----------------------------------
  const masterDefaults = {
    // Common fields
    fullName: "",
    email: "",
    phone: "",
    password: "",
    confirmPassword: "",
    gender: "",
    dob: "",
    address: "",
    country: "",

    // Student-only
    admissionNumber: "",
    classLevel: "",
    guardianName: "",
    guardianPhone: "",

    // Teacher-only
    staffId: "",
    qualification: "",
    specialization: "",
    yearsOfExperience: "",

    // School-only
    schoolName: "",
    schoolCode: "",
    schoolAddress: "",
  };
  
const formValues = reactive({ ...masterDefaults });
  // -----------------------------------
  // MASTER SCHEMA (ALL POSSIBLE RULES)
  // -----------------------------------
  const masterSchema = yup.object({
    fullName: yup.string().required(),
    email: yup.string().email().required(),
    phone: yup.string().min(10),
    password: yup.string().min(6),
    confirmPassword: yup.string().oneOf([yup.ref("password")], "Passwords must match"),

    dob: yup.string(),
    gender: yup.string(),
    address: yup.string(),
    country: yup.string(),

    admissionNumber: yup.string(),
    classLevel: yup.string(),
    guardianName: yup.string(),
    guardianPhone: yup.string(),

    staffId: yup.string(),
    qualification: yup.string(),
    specialization: yup.string(),
    yearsOfExperience: yup.string(),

    schoolName: yup.string(),
    schoolCode: yup.string(),
    schoolAddress: yup.string(),
  });

  // -----------------------------------
  // DYNAMIC BUILDING FUNCTION
  // -----------------------------------
  function buildForm(fieldList = []) {
    const filteredDefaults = {};
    const filteredShape = {};

    // pick only needed defaults & schema
    fieldList.forEach((field) => {
      
      filteredDefaults[field] = formValues[field] ?? masterDefaults[field];
      filteredShape[field] = masterSchema.fields[field];
    });

    const schema = yup.object(filteredShape);

    const form = useForm({
      initialValues: filteredDefaults,
      validationSchema: schema
    });

    return form;
  }
  
  function setFormValues(values) {
    formValues.value = values;
  }
  function applyServerErrors(form, errorBag) {
  // Laravel typically returns { errors: { field: ["msg"] } }
  // We convert that into { email: "msg" }
  const processed = {};

  for (const key in errorBag.errors) {
    processed[key] = errorBag.errors[key][0]; // take first message
  }

  form.setErrors(processed);
}

  return {
    masterDefaults,
    masterSchema,
    buildForm,
    formValue,
    applyServerErrors,
    setFormValues
  };
},

{
  persist: true, // localStorage persistence
});

