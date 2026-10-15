import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import ResetPasswordView from '../views/ResetPasswordView.vue'

const mocks = vi.hoisted(() => ({
  requestPasswordReset: vi.fn(),
  resetPassword: vi.fn(),
  routeQuery: { token: 'test-token', email: 'test@example.test' } as Record<
    string,
    unknown
  >,
}))

vi.mock('@/stores/auth', () => ({
  useAuthStore: () => ({
    requestPasswordReset: mocks.requestPasswordReset,
    resetPassword: mocks.resetPassword,
  }),
}))

vi.mock('vue-router', () => ({
  RouterLink: { template: '<a><slot /></a>' },
  useRoute: () => ({ query: mocks.routeQuery }),
}))

describe('ResetPasswordView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    mocks.routeQuery.token = 'test-token'
    mocks.routeQuery.email = 'test@example.test'
    mocks.resetPassword.mockResolvedValue(
      'Your password has been reset successfully.',
    )
  })

  it('submits the token and new password and displays success', async () => {
    const wrapper = mount(ResetPasswordView)

    await wrapper.get('#password').setValue('NewSecurePass456!')
    await wrapper
      .get('#password-confirmation')
      .setValue('NewSecurePass456!')
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(mocks.resetPassword).toHaveBeenCalledWith({
      token: 'test-token',
      email: 'test@example.test',
      password: 'NewSecurePass456!',
      password_confirmation: 'NewSecurePass456!',
    })

    expect(wrapper.text()).toContain(
      'Your password has been reset successfully.',
    )
    expect(wrapper.text()).toContain('Return to sign in')
  })

  it('shows an error when the reset link is invalid or expired', async () => {
    mocks.resetPassword.mockRejectedValue({
      isAxiosError: true,
      response: {
        status: 422,
        data: {
          message: 'This password reset link is invalid or has expired.',
        },
      },
    })

    const wrapper = mount(ResetPasswordView)

    await wrapper.get('#password').setValue('NewSecurePass456!')
    await wrapper
      .get('#password-confirmation')
      .setValue('NewSecurePass456!')
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain(
      'This password reset link is invalid or has expired.',
    )
  })

  it('asks for a new link when token information is missing', () => {
    mocks.routeQuery.token = undefined

    const wrapper = mount(ResetPasswordView)

    expect(wrapper.text()).toContain(
      'This password reset link is missing required information.',
    )
    expect(wrapper.text()).toContain('Request a new reset link')
    expect(wrapper.find('form').exists()).toBe(false)
  })
})
