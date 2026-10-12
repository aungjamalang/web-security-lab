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
    }): Promise<AuthUser | null> {
        await api.get('/sanctum/csrf-cookie')

        await api.post('/api/v1/auth/login', credentials)

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

    return {
        user,
        initialized,
        isLoadingUser,
        fetchUser,
        register,
        login,
        logout,
        resendVerificationEmail,
    }
})