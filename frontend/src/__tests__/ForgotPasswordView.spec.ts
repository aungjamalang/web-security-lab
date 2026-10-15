import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import ForgotPasswordView from '../views/ForgotPasswordView.vue'

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

describe('ForgotPasswordView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    mocks.requestPasswordReset.mockResolvedValue(
      'If an account exists for that email, a password reset link will be sent.',
    )
  })

  it('requests a reset link and displays the generic success message', async () => {
    const wrapper = mount(ForgotPasswordView)

    await wrapper.get('#email').setValue('test@example.test')
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(mocks.requestPasswordReset).toHaveBeenCalledWith(
      'test@example.test',
    )
    expect(wrapper.text()).toContain(
      'If an account exists for that email, a password reset link will be sent.',
    )
  })

  it('displays validation errors returned by the API', async () => {
    mocks.requestPasswordReset.mockRejectedValue({
      isAxiosError: true,
      response: {
        data: {
          message: 'The given data was invalid.',
          errors: {
            email: ['The email field must be a valid email address.'],
          },
        },
      },
    })

    const wrapper = mount(ForgotPasswordView)

    await wrapper.get('#email').setValue('invalid@example.test')
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain(
      'The email field must be a valid email address.',
    )
  })
})
