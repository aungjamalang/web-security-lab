<script setup lang="ts">
import { ref } from 'vue'
import axios from 'axios'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const email = ref('')
const password = ref('')
const twoFactorCode = ref('')
const challengeToken = ref('')
const twoFactorRequired = ref(false)
const errorMessage = ref('')
const isLoading = ref(false)

const router = useRouter()
const authStore = useAuthStore()

async function login() {
  errorMessage.value = ''
  isLoading.value = true

  try {
    if (twoFactorRequired.value) {
      const user = await authStore.verifyTwoFactorLogin(
        challengeToken.value,
        twoFactorCode.value.trim(),
      )

      if (!user) {
        errorMessage.value =
          'Your session could not be verified. Please try again.'
        return
      }

      await router.replace('/dashboard')
      return
    }

    const result = await authStore.login({
      email: email.value,
      password: password.value,
    })

    if (result.twoFactorRequired) {
      challengeToken.value = result.challengeToken ?? ''
      twoFactorRequired.value = true
      password.value = ''
      return
    }

    if (!result.user) {
      errorMessage.value =
        'Your session could not be verified. Please try again.'
      return
    }

    await router.replace('/dashboard')
  } catch (error: unknown) {
    if (axios.isAxiosError(error)) {
      const data = error.response?.data as
        | { message?: string; errors?: Record<string, string[]> }
        | undefined

      errorMessage.value = data?.errors
        ? Object.values(data.errors).flat().join(' ')
        : data?.message ?? 'Unable to log in. Please try again.'
    } else {
      errorMessage.value = 'An unexpected error occurred.'
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <main class="login-page">
    <section class="login-card">
      <h1>Welcome back</h1>
      <p class="subtitle">Sign in to Web Security Lab</p>

      <form @submit.prevent="login">
        <template v-if="!twoFactorRequired">
          <label for="email">Email address</label>
          <input id="email" v-model.trim="email" type="email" autocomplete="username" required />

          <label for="password">Password</label>
          <input id="password" v-model="password" type="password" autocomplete="current-password" required />

          <p class="forgot-password-link">
            <RouterLink to="/forgot-password">Forgot password?</RouterLink>
          </p>
        </template>

        <template v-else>
          <p>Enter the 6-digit code from your authenticator app or one of your recovery codes.</p>
          <label for="two-factor-code">Authentication or recovery code</label>
          <input id="two-factor-code" v-model.trim="twoFactorCode" type="text" autocomplete="one-time-code" required />
        </template>

        <p v-if="errorMessage" class="error" role="alert">
          {{ errorMessage }}
        </p>

        <button type="submit" :disabled="isLoading">
          {{ isLoading ? 'Verifying…' : twoFactorRequired ? 'Verify and sign in' : 'Sign in' }}
        </button>
      </form>

      <p v-if="twoFactorRequired" class="register-link">
        <button type="button" :disabled="isLoading" @click="twoFactorRequired = false; challengeToken = ''; twoFactorCode = ''">Back to sign in</button>
      </p>

      <p v-else class="register-link">
        Don't have an account?
        <RouterLink to="/register">Create an account</RouterLink>
      </p>
    </section>
  </main>
</template>

<style scoped>
.login-page {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 24px;
  background: #f3f4f6;
  font-family: Arial, sans-serif;
}

.login-card {
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

.register-link {
  margin-top: 20px;
  text-align: center;
}

.register-link a {
  color: #6c63ff;
  font-weight: 600;
}

.forgot-password-link {
  margin: 0;
  text-align: right;
}

.forgot-password-link a {
  color: #6c63ff;
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
}

.forgot-password-link a:hover {
  text-decoration: underline;
}
</style>
