<template>
  <div class="fee-panel border rounded p-3 bg-light">
    <h6 class="fw-bold mb-3 text-primary">Fee Payment Panel</h6>

    <!-- Student Select -->
    <div class="mb-3">
      <label class="form-label small fw-semibold">Select Student</label>
      <select v-model="selectedStudentId" class="form-select form-select-sm">
        <option disabled value="">-- Choose Student --</option>
        <option v-for="s in students" :key="s.id" :value="s">
          {{ s.name }} 
        </option>
      </select>
    </div>

    <!-- Amount Paid -->
    <div class="mb-3">
      <label class="form-label small fw-semibold">Amount Paid (₦)</label>
      <input
        v-model.number="amountPaid"
        type="number"
        class="form-control form-control-sm"
        placeholder="Enter amount paid"
      />
    </div>

    <!-- Total Owed -->
    <div class="mb-3">
      <label class="form-label small fw-semibold">Total Owed (₦)</label>
      <div class="form-control form-control-sm bg-white">
        ₦{{ totalOwed.toLocaleString() }}
      </div>
    </div>

    <!-- Term Select -->
    <div class="mb-3">
      <label class="form-label small fw-semibold">Select Term</label>
      <select v-model="selectedTerm" class="form-select form-select-sm">
        <option disabled value="">-- Choose Term --</option>
        <option>First Term</option>
        <option>Second Term</option>
        <option>Third Term</option>
      </select>
    </div>

    <!-- Session Select -->
    <div class="mb-3">
      <label class="form-label small fw-semibold">Select Session</label>
      <select v-model="selectedSession" class="form-select form-select-sm">
        <option disabled value="">-- Choose Session --</option>
        <option>2023/2024</option>
        <option>2024/2025</option>
        <option>2025/2026</option>
      </select>
    </div>

    
  !-- Submit Button -->
    <div class="text-end">
      <button
        class="btn btn-success btn-sm"
        @click="submitPayment"
        :disabled="loading"
      >
        <span v-if="!loading">Submit Payment</span>
        <span v-else>Submitting...</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useClassStore } from "@/stores/classStore";
const studentStore = useStudentStore();
const { students, fetchStudents } = studentStore;

// Mock student list (could be fetched from API)
const students = ref([
  { id: 1, name: "John Doe", class: "JSS1" },
  { id: 2, name: "Mary Ann", class: "JSS2" },
  { id: 3, name: "Sam Okoro", class: "JSS3" },
]);// classStore.students

const selectedStudentId = ref("");
const amountPaid = ref("");
const totalOwed = ref(25000);
const selectedTerm = ref("");
const selectedSession = ref("");

// Logic for payment
const submitPayment = async () => {
  if (!selectedStudentId.value || !selectedTerm.value || !selectedSession.value || !amountPaid.value) {
    alert("Please complete all fields before submitting.");
    return;
  }

  // ✅ Proper JSON payload
  const payload = {
    studentId: selectedStudentId.value,
    amountPaid: amountPaid.value,
    term: selectedTerm.value,
    session: selectedSession.value,
  };

  try {
    loading.value = true;
    const res = await fetch("/api/payments", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
      },
      body: JSON.stringify(payload),
    });

    const data = await res.json();

    if (!res.ok) throw new Error(data.message || "Failed to submit payment.");

    alert(`Payment successful: ₦${amountPaid.value.toLocaleString()} for ${selectedTerm.value}`);
    amountPaid.value = "";
  } catch (err) {
    console.error("Payment submission error:", err);
    alert("Error submitting payment: " + err.message);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  if (!students.length) fetchStudents();
});
</script>

<style scoped>
.fee-panel {
  max-width: 400px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}
.form-label {
  margin-bottom: 2px;
}
</style>
