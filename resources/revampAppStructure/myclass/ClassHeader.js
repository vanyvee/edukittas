<template>
  <div class="header-bar d-flex">
    <!-- Back Button -->
    <button
      v-if="classStore.showBackButton"
      class="header-btn back-btn flex-fill"
      @click="classStore.reset"
    >
      ← Back
    </button>

    <!-- Other Buttons -->
    <button class="header-btn flex-fill" @click = "classStore.currentClassComponent.value = 'DefaultClassView'">My Class</button>
    <button class="header-btn flex-fill" @click = "classStore.currentClassComponent.value = 'FullClassView'">Full View</button>
    <button class="header-btn flex-fill" @click = "classStore.currentClassComponent.value = 'ClassAnalytics'">Analytic</button>
  </div>
</template>

<style scoped>
.header-bar {
  width: 100%;
  display: flex;
  border-radius: 1rem;
  overflow: hidden;
  background: linear-gradient(135deg, #f7f8fa, #e3e6ea);
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
}

.header-btn {
  border: none;
  background: transparent;
  padding: 0.9rem 0;
  font-weight: 600;
  color: #333;
  transition: all 0.3s ease;
  font-size: 0.95rem;
  letter-spacing: 0.3px;
}

.header-btn:not(:last-child) {
  border-right: 1px solid rgba(0, 0, 0, 0.08);
}

.header-btn:hover {
  background: rgba(0, 0, 0, 0.05);
  color: #000;
}

.back-btn {
  background: linear-gradient(135deg, #007bff, #0056b3);
  color: #fff;
  transition: background 0.3s ease;
}

.back-btn:hover {
  background: linear-gradient(135deg, #0062d1, #004a94);
}

/* Responsive tweak for mobile */
@media (max-width: 768px) {
  .header-btn {
    font-size: 0.85rem;
    padding: 0.7rem 0;
  }
}
</style>

