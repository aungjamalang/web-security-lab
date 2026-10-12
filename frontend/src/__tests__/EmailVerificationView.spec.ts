import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createMemoryHistory, createRouter } from 'vue-router'
import EmailVerificationView from '../views/EmailVerificationView.vue'

const LoginPlaceholder = {
  template: '<div>Login placeholder</div>',
}

async function mountAt(path: string) {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      {
        path: '/email/verification-result',
        component: EmailVerificationView,
      },
      {
        path: '/login',
        component: LoginPlaceholder,
      },
    ],
  })

  await router.push(path)
  await router.isReady()

  return mount(EmailVerificationView, {
    global: {
      plugins: [router],
    },
  })
}

describe('EmailVerificationView', () => {
  it('shows success when email verification succeeds', async () => {
    const wrapper = await mountAt(
      '/email/verification-result?status=success',
    )

    expect(wrapper.text()).toContain('Email verified!')
    expect(wrapper.text()).toContain(
      'Your email address has been verified successfully.',
    )
  })

  it('shows an error for invalid or expired verification links', async () => {
    const wrapper = await mountAt(
      '/email/verification-result?status=error',
    )

    expect(wrapper.text()).toContain('Verification unsuccessful')
    expect(wrapper.text()).toContain(
      'This verification link is invalid or has expired.',
    )
  })
})
