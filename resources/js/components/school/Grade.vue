<template>
  <div class="results">
    <div class="header bg-secondary ">
      <h1>Results</h1>
    </div>
    
    <div class="content">
      <div class="box bg-secondary"></div>
      <div class="box bg-primary"></div>
      <div class="box bg-secondary"></div>
    </div>
  </div>
</template>

<style scoped>
.results {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.header {
  padding: 1rem;
}

.content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding: 1rem;
}

.box {
  width: 100%;
  flex: 1;
  border-radius: 8px;
}


</style>
