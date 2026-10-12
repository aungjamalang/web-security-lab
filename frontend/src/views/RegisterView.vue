<script setup lang="ts">
import { ref } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const errorMessage = ref('')
const successMessage = ref('')
const isLoading = ref(false)
const authStore = useAuthStore()

async function register() {
  errorMessage.value = ''
  successMessage.value = ''
  isLoading.value = true

  try {
    // Initialize Laravel's CSRF cookie.
    const message = await authStore.register({
        name: name.value,
        email: email.value,
        password: password.value,
        password_confirmation: passwordConfirmation.value,
    })

    successMessage.value =
      message + ' Please check your email for a verification link.'

    name.value = ''
    email.value = ''
    password.value = ''
    passwordConfirmation.value = ''
  } catch (error: unknown) {
    if (axios.isAxiosError(error)) {
      const data = error.response?.data as
        | { message?: string; errors?: Record<string, string[]> }
        | undefined

      errorMessage.value = data?.errors
        ? Object.values(data.errors).flat().join(' ')
        : data?.message ?? 'Unable to register. Please try again.'
    } else {
      errorMessage.value = 'An unexpected error occurred.'
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <main class="register-page">
    <section class="register-card">
      <h1>Create an account</h1>
      <p class="subtitle">Join Web Security Lab</p>

      <form @submit.prevent="register">
        <label for="name">Full name</label>
        <input
          id="name"
          v-model.trim="name"
          type="text"
          autocomplete="name"
          maxlength="255"
          required
        />

        <label for="email">Email address</label>
        <input
          id="email"
          v-model.trim="email"
          type="email"
          autocomplete="email"
          maxlength="255"
          required
        />

        <label for="password">Password</label>
        <input
          id="password"
          v-model="password"
          type="password"
          autocomplete="new-password"
          minlength="8"
          required
        />

        <label for="password-confirmation">Confirm password</label>
        <input
          id="password-confirmation"
          v-model="passwordConfirmation"
          type="password"
          autocomplete="new-password"
          required
        />

        <p v-if="errorMessage" class="error" role="alert">
          {{ errorMessage }}
        </p>

        <p v-if="successMessage" class="success" role="status">
          {{ successMessage }}
        </p>

        <button type="submit" :disabled="isLoading">
          {{ isLoading ? 'Creating account…' : 'Create account' }}
        </button>
      </form>

      <p class="login-link">
        Already have an account?
        <RouterLink to="/login">Sign in</RouterLink>
      </p>
    </section>
  </main>
</template>

<style scoped>
.register-page {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 24px;
  background: #f3f4f6;
  font-family: Arial, sans-serif;
}

.register-card {
  width: 100%;
  max-width: 400px;
  padding: 32px;
  border-radius: 12px;
  background: white;
  box-shadow: 0 8px 28px rgb(0 0 0 / 8%);
}

h1 {
  margin: 0 0 8px;
}

.subtitle {
  margin: 0 0 24px;
  color: #6b7280;
}

form {
  display: grid;
  gap: 12px;
}

label {
  font-weight: 600;
}

input {
  width: 100%;
  box-sizing: border-box;
  padding: 11px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
}

button {
  margin-top: 8px;
  padding: 12px;
  border: 0;
  border-radius: 6px;
  background: #6c63ff;
  color: white;
  font-weight: 600;
  cursor: pointer;
}

button:disabled {
  opacity: 0.6;
  cursor: wait;
}

.error {
  color: #b91c1c;
}

.success {
  color: #047857;
}

.login-link {
  margin-top: 20px;
  text-align: center;
}
</style>