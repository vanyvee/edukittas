<template>
  <div class="container mt-5">
    <h1>Login</h1>

    <input v-model="username" type="username" class="form-control mb-2" placeholder="username" />
    <input v-model="password" type="password" class="form-control mb-2" placeholder="Password" />

    <button @click="login" class="btn btn-primary">Login</button>

    <p v-if="error" class="text-danger mt-2">{{ error }}</p>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const BASE = 'http://127.0.0.1:8000'
const username = ref('')
const password = ref('')
const error = ref('')

const login = async () => {
  error.value = ''

  // get csrf cookie
  await fetch(`${BASE}/sanctum/csrf-cookie`, {
    credentials: 'include'
  })

  const res = await fetch(`${BASE}/login`, {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username: username.value, password: password.value })
  })

  if (!res.ok) {
    const data = await res.text()
    error.value = data
    return
  }

  window.location.href = '/'
}
</script>