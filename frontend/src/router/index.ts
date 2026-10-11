import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import LoginView from '@/views/LoginView.vue'
import RegisterView from '@/views/RegisterView.vue'
import DashboardView from '@/views/DashboardView.vue'
import NotFoundView from '@/views/NotFoundView.vue'
import EmailVerificationView from '@/views/EmailVerificationView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),

  routes: [
    {
      path: '/',
      redirect: '/dashboard',
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { guestOnly: true },
    },
    {
      path: '/register',
      name: 'register',
      component: RegisterView,
      meta: { guestOnly: true },
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: DashboardView,
      meta: { requiresAuth: true },
    },
    {
      path: '/email/verification-result',
      name: 'email-verification-result',
      component: EmailVerificationView,
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: NotFoundView,
    },
  ],
})

router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  const requiresAuth = to.matched.some(
    (record) => record.meta.requiresAuth === true,
  )

  const guestOnly = to.matched.some(
    (record) => record.meta.guestOnly === true,
  )

  if (!requiresAuth && !guestOnly) {
    return true
  }

  try {
    // Force a fresh session check while LoginView still
    // handles authentication directly through Axios.
    const user = await authStore.fetchUser(true)

    if (requiresAuth && !user) {
      return { name: 'login' }
    }

    if (guestOnly && user) {
      return { name: 'dashboard' }
    }

    return true
  } catch {
    // Allow the destination to render so it can display
    // an appropriate API or network error.
    return true
  }
})

export default router