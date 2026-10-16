<template>
  <Form :initial-values="form.values" :validation-schema="form.schema" @submit="onSubmit">
    <CarouselReusableLite1
      :slides="studentFormSlides"
    />
    <div class="d-flex justify-content-end mt-3">
      <button class="btn btn-primary" type="submit">Submit</button>
    </div>
  </Form>
</template>

<script setup>
import { useGlobalFormStore } from "@/stores/globalFormStore";
import FormField from "@/components/FormField.vue";
import CarouselReusableLite1 from "@/components/CarouselReusableLite1.vue";

const globalFormStore = useGlobalFormStore();

// ----------------------
// Slide Definitions
// ----------------------
const studentFormSlides = [
{StudentFormCard1},{StudentFormCard1}
];

const allFieldNames = [
  "fullName",
  "gender",
  "address",
  "email",
  "dob",
  "phone",
];

const form = globalFormStore.buildForm(allFieldNames);

// ----------------------
// Submit Handler
// ----------------------
async function onSubmit(values) {
  try {
    console.log("Submitted:", values);
    // await api.submitForm(values);
  } catch (error) {
    // Apply Laravel-style server errors
    globalFormStore.applyServerErrors(form, error.response.data);
  }
}
</script>