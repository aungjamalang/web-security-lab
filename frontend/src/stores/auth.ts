import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'
import api from '@/lib/axios'

export interface AuthUser {
    id: number
    name: string
    email: string
    email_verified_at: string | null
}

export const useAuthStore = defineStore('auth', () => {
    const user = ref<AuthUser | null>(null)
    const initialized = ref(false)
    const isLoadingUser = ref(false)

    async function fetchUser(force = false): Promise<AuthUser | null> {
        if (initialized.value && !force) {
            return user.value
        }

        isLoadingUser.value = true

        try {
            const response = await api.get<AuthUser>('/api/user')

            user.value = response.data
            initialized.value = true

            return user.value
        } catch (error: unknown) {
            if (axios.isAxiosError(error) && error.response?.status === 401) {
                user.value = null
                initialized.value = true

                return null
            }

            throw error
        } finally {
            isLoadingUser.value = false
        }
    }

    async function login(credentials: {
        email: string
        password: string
    }): Promise<{ user: AuthUser | null; twoFactorRequired: boolean; challengeToken?: string }> {
        await api.get('/sanctum/csrf-cookie')

        const response = await api.post<{
            two_factor_required?: boolean
            challenge_token?: string
        }>('/api/v1/auth/login', credentials)

        if (response.data.two_factor_required) {
            return {
                user: null,
                twoFactorRequired: true,
                challengeToken: response.data.challenge_token,
            }
        }

        initialized.value = false
        return {
            user: await fetchUser(true),
            twoFactorRequired: false,
        }
    }

    async function verifyTwoFactorLogin(challengeToken: string, code: string): Promise<AuthUser | null> {
        await api.post('/api/v1/auth/two-factor/login', {
            challenge_token: challengeToken,
            code,
        })

        initialized.value = false
        return await fetchUser(true)
    }

    async function register(data: {
        name: string
        email: string
        password: string
        password_confirmation: string
    }): Promise<string> {
        await api.get('/sanctum/csrf-cookie')

        const response = await api.post<{ message: string }>(
            '/api/v1/auth/register',
            data,
        )

        return response.data.message
    }

    async function logout(): Promise<void> {
        await api.post('/api/v1/auth/logout')

        user.value = null
        initialized.value = true
    }

    async function resendVerificationEmail(): Promise<string> {
        const response = await api.post<{ message: string }>(
            '/api/v1/auth/email/verification-notification',
        )

        return response.data.message
    }

    async function changePassword(data: {
        current_password: string
        password: string
        password_confirmation: string
    }): Promise<string> {
        const response = await api.put<{ message: string }>(
            '/api/v1/auth/password',
            data,
        )

        return response.data.message
    }

    async function requestPasswordReset(email: string): Promise<string> {
        await api.get('/sanctum/csrf-cookie')

        const response = await api.post<{ message: string }>(
            '/api/v1/auth/forgot-password',
            { email },
        )

        return response.data.message
    }

    async function resetPassword(data: {
        token: string
        email: string
        password: string
        password_confirmation: string
    }): Promise<string> {
        await api.get('/sanctum/csrf-cookie')

        const response = await api.post<{ message: string }>(
            '/api/v1/auth/reset-password',
            data,
        )

        return response.data.message
    }

    return {
        user,
        initialized,
        isLoadingUser,
        fetchUser,
        register,
        login,
        verifyTwoFactorLogin,
        logout,
        resendVerificationEmail,
        changePassword,
        requestPasswordReset,
        resetPassword,
    }
})