<script setup lang="ts">
import { ref } from 'vue'
import axios from 'axios'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const email = ref('')
const successMessage = ref('')
const errorMessage = ref('')
const isLoading = ref(false)

async function requestPasswordReset() {
    successMessage.value = ''
    errorMessage.value = ''
    isLoading.value = true

    try {
        successMessage.value =
            await authStore.requestPasswordReset(email.value.trim())
    } catch (error: unknown) {
        if (axios.isAxiosError(error)) {
            const data = error.response?.data as
                | { message?: string; errors?: Record<string, string[]> }
                | undefined

            errorMessage.value = data?.errors
                ? Object.values(data.errors).flat().join(' ')
                : data?.message ??
                'Unable to submit your request. Please try again.'
        } else {
            errorMessage.value = 'An unexpected error occurred. Please try again.'
        }
    } finally {
        isLoading.value = false
    }
}
</script>

<template>
    <main class="forgot-password-page">
        <section class="forgot-password-card">
            <h1>Forgot password?</h1>

            <p class="subtitle">
                Enter your email address and, if an account exists, we'll send you a
                password reset link.
            </p>

            <form @submit.prevent="requestPasswordReset">
                <label for="email">Email address</label>
                <input id="email" v-model.trim="email" type="email" autocomplete="email" required />

                <p v-if="successMessage" class="success" role="status">
                    {{ successMessage }}
                </p>

                <p v-if="errorMessage" class="error" role="alert">
                    {{ errorMessage }}
                </p>

                <button type="submit" :disabled="isLoading">
                    {{ isLoading ? 'Sending…' : 'Send reset link' }}
                </button>
            </form>

            <p class="back-link">
                <RouterLink to="/login">Back to sign in</RouterLink>
            </p>
        </section>
    </main>
</template>

<style scoped>
.forgot-password-page {
    min-height: 100vh;
    display: grid;
    place-items: center;
    padding: 24px;
    background: #f3f4f6;
    font-family: Arial, sans-serif;
}

.forgot-password-card {
    width: 100%;
    max-width: 400px;
    box-sizing: border-box;
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
    line-height: 1.5;
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

.success {
    color: #047857;
    line-height: 1.5;
}

.error {
    color: #b91c1c;
    line-height: 1.5;
}

.back-link {
    margin-top: 20px;
    text-align: center;
}

.back-link a {
    color: #6c63ff;
    font-weight: 600;
}
</style>