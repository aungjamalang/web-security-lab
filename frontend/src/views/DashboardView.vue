<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const errorMessage = ref('')
const isLoggingOut = ref(false)

const verificationMessage = ref('')
const verificationError = ref('')
const isResendingVerification = ref(false)

async function logout() {
  errorMessage.value = ''
  isLoggingOut.value = true

  try {
    await authStore.logout()
    await router.replace('/login')
  } catch {
    errorMessage.value = 'Logout failed. Please try again.'
  } finally {
    isLoggingOut.value = false
  }
}

async function retryProfile() {
  errorMessage.value = ''

  try {
    const user = await authStore.fetchUser(true)

    if (!user) {
      await router.replace('/login')
    }
  } catch {
    errorMessage.value =
      'Unable to load your profile. Check your connection and try again.'
  }
}

async function resendVerificationEmail() {
  verificationMessage.value = ''
  verificationError.value = ''
  isResendingVerification.value = true

  try {
    verificationMessage.value =
      await authStore.resendVerificationEmail()
  } catch {
    verificationError.value =
      'Unable to send the verification email. Please try again.'
  } finally {
    isResendingVerification.value = false
  }
}
</script>

<template>
  <main class="dashboard">
    <section class="dashboard-card">
      <p class="eyebrow">WEB SECURITY LAB</p>
      <h1>Dashboard</h1>

      <template v-if="authStore.user">
        <h2>Welcome, {{ authStore.user.name }}!</h2>

        <p>Email: {{ authStore.user.email }}</p>
        <p>User ID: {{ authStore.user.id }}</p>

        <section class="verification-section">
          <template v-if="authStore.user.email_verified_at">
            <p class="verified-status" role="status">
              ✓ Your email address is verified.
            </p>
          </template>

          <template v-else>
            <p class="verification-notice">
              Your email address has not been verified yet.
            </p>

            <button
              type="button"
              :disabled="isResendingVerification"
              @click="resendVerificationEmail"
            >
              {{
                isResendingVerification
                  ? 'Sending email…'
                  : 'Resend verification email'
              }}
            </button>

            <p
              v-if="verificationMessage"
              class="verified-status"
              role="status"
            >
              {{ verificationMessage }}
            </p>

            <p
              v-if="verificationError"
              class="error"
              role="alert"
            >
              {{ verificationError }}
            </p>
          </template>
        </section>

        <button
          type="button"
          :disabled="isLoggingOut"
          @click="logout"
        >
          {{ isLoggingOut ? 'Signing out…' : 'Sign out' }}
        </button>

        <p v-if="errorMessage" class="error" role="alert">
          {{ errorMessage }}
        </p>
      </template>

      <div v-else-if="authStore.isLoadingUser">
        <p>Loading your profile...</p>
      </div>

      <div v-else>
        <p class="error" role="alert">
          {{ errorMessage || 'Unable to load your profile.' }}
        </p>

        <button
          type="button"
          :disabled="authStore.isLoadingUser"
          @click="retryProfile"
        >
          Retry
        </button>
      </div>
    </section>
  </main>
</template>

<style scoped>
.dashboard {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 24px;
  background: #f3f4f6;
  font-family: Arial, sans-serif;
}

.dashboard-card {
  width: 100%;
  max-width: 500px;
  box-sizing: border-box;
  padding: 32px;
  border-radius: 12px;
  background: white;
  box-shadow: 0 8px 28px rgb(0 0 0 / 8%);
}

.eyebrow {
  color: #6c63ff;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 1.5px;
}

h1 {
  margin-top: 8px;
}

.verification-section {
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
}

.verification-notice {
  color: #92400e;
  line-height: 1.5;
}

.verified-status {
  color: #047857;
  line-height: 1.5;
}

button {
  margin-top: 16px;
  padding: 12px 18px;
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
  line-height: 1.5;
}
</style>