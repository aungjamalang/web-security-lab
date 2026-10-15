<script setup lang="ts">
import { computed, ref } from 'vue'
import axios from 'axios'
import { RouterLink, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const authStore = useAuthStore()

const token = computed(() =>
    typeof route.query.token === 'string' ? route.query.token : '',
)

const email = computed(() =>
    typeof route.query.email === 'string' ? route.query.email : '',
)

const password = ref('')
const passwordConfirmation = ref('')
const successMessage = ref('')
const errorMessage = ref('')
const isLoading = ref(false)

async function resetPassword() {
    successMessage.value = ''
    errorMessage.value = ''
    isLoading.value = true

    try {
        successMessage.value = await authStore.resetPassword({
            token: token.value,
            email: email.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        })

        password.value = ''
        passwordConfirmation.value = ''
    } catch (error: unknown) {
        if (axios.isAxiosError(error)) {
            const data = error.response?.data as
                | { message?: string; errors?: Record<string, string[]> }
                | undefined

            errorMessage.value = data?.errors
                ? Object.values(data.errors).flat().join(' ')
                : data?.message ??
                'Unable to reset your password. Please request a new reset link.'
        } else {
            errorMessage.value =
                'An unexpected error occurred. Please try again.'
        }
    } finally {
        isLoading.value = false
    }
}
</script>

<template>
    <main class="reset-password-page">
        <section class="reset-password-card">
            <h1>Reset your password</h1>

            <p class="subtitle">
                Enter a new password for your account.
            </p>

            <template v-if="!token || !email">
                <p class="error" role="alert">
                    This password reset link is missing required information.
                    Please request a new reset link.
                </p>

                <p class="back-link">
                    <RouterLink to="/forgot-password">
                        Request a new reset link
                    </RouterLink>
                </p>
            </template>

            <template v-else>
                <p class="account-email">
                    Account: {{ email }}
                </p>

                <form v-if="!successMessage" @submit.prevent="resetPassword">
                    <label for="password">New password</label>
                    <input id="password" v-model="password" type="password" autocomplete="new-password" minlength="8"
                        required />

                    <label for="password-confirmation">
                        Confirm new password
                    </label>
                    <input id="password-confirmation" v-model="passwordConfirmation" type="password"
                        autocomplete="new-password" minlength="8" required />

                    <p v-if="errorMessage" class="error" role="alert">
                        {{ errorMessage }}
                    </p>

                    <button type="submit" :disabled="isLoading">
                        {{ isLoading ? 'Resetting password…' : 'Reset password' }}
                    </button>
                </form>

                <p v-if="successMessage" class="success" role="status">
                    {{ successMessage }}
                </p>

                <p v-if="successMessage" class="back-link">
                    <RouterLink to="/login">Return to sign in</RouterLink>
                </p>
            </template>

            <p v-if="!successMessage && token && email" class="back-link">
                <RouterLink to="/login">Back to sign in</RouterLink>
            </p>
        </section>
    </main>
</template>

<style scoped>
.reset-password-page {
    min-height: 100vh;
    display: grid;
    place-items: center;
    padding: 24px;
    background: #f3f4f6;
    font-family: Arial, sans-serif;
}

.reset-password-card {
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

.account-email {
    margin-bottom: 20px;
    color: #4b5563;
    overflow-wrap: anywhere;
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
    text-decoration: none;
}

.back-link a:hover {
    text-decoration: underline;
}
</style>