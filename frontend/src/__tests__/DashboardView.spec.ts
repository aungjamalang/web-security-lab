import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import DashboardView from '../views/DashboardView.vue'

const mocks = vi.hoisted(() => ({
  user: null as {
    id: number
    name: string
    email: string
    email_verified_at: string | null
  } | null,
  isLoadingUser: false,
  fetchUser: vi.fn(),
  logout: vi.fn(),
  resendVerificationEmail: vi.fn(),
  changePassword: vi.fn(),
  routerReplace: vi.fn(),
}))

vi.mock('@/stores/auth', () => ({
  useAuthStore: () => ({
    user: mocks.user,
    isLoadingUser: mocks.isLoadingUser,
    fetchUser: mocks.fetchUser,
    logout: mocks.logout,
    resendVerificationEmail: mocks.resendVerificationEmail,
    changePassword: mocks.changePassword,
  }),
}))

vi.mock('vue-router', () => ({
  useRouter: () => ({
    replace: mocks.routerReplace,
  }),
}))

function setVerifiedUser() {
  mocks.user = {
    id: 1,
    name: 'Test User',
    email: 'test@example.test',
    email_verified_at: '2026-10-12T12:00:00.000000Z',
  }
}

async function fillPasswordForm(
  wrapper: ReturnType<typeof mount>,
) {
  await wrapper.get('#current-password').setValue('CurrentPass123!')
  await wrapper.get('#new-password').setValue('NewSecurePass456!')
  await wrapper
    .get('#password-confirmation')
    .setValue('NewSecurePass456!')
}

describe('DashboardView', () => {
  beforeEach(() => {
    vi.clearAllMocks()

    mocks.user = {
      id: 1,
      name: 'Test User',
      email: 'test@example.test',
      email_verified_at: null,
    }

    mocks.isLoadingUser = false
    mocks.resendVerificationEmail.mockResolvedValue(
      'Verification link sent.',
    )
    mocks.changePassword.mockResolvedValue(
      'Password changed successfully.',
    )
  })

  it('shows a notice and resend button for unverified users', () => {
    const wrapper = mount(DashboardView)

    expect(wrapper.text()).toContain(
      'Your email address has not been verified yet.',
    )
    expect(wrapper.text()).toContain('Resend verification email')
    expect(wrapper.text()).not.toContain('Change password')
  })

  it('shows verified status and hides the resend button', () => {
    setVerifiedUser()

    const wrapper = mount(DashboardView)

    expect(wrapper.text()).toContain(
      'Your email address is verified.',
    )
    expect(wrapper.text()).not.toContain(
      'Resend verification email',
    )
    expect(wrapper.text()).toContain('Change password')
  })

  it('resends the email and displays the success message', async () => {
    const wrapper = mount(DashboardView)

    await wrapper.get('button').trigger('click')
    await flushPromises()

    expect(mocks.resendVerificationEmail).toHaveBeenCalledOnce()
    expect(wrapper.text()).toContain('Verification link sent.')
  })

  it('shows an error when resending fails', async () => {
    mocks.resendVerificationEmail.mockRejectedValue(
      new Error('Request failed'),
    )

    const wrapper = mount(DashboardView)

    await wrapper.get('button').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain(
      'An unexpected error occurred. Please try again.',
    )
  })

  it('changes the password and clears the form after success', async () => {
    setVerifiedUser()

    const wrapper = mount(DashboardView)
    await fillPasswordForm(wrapper)
    await wrapper.get('form.password-form').trigger('submit')
    await flushPromises()

    expect(mocks.changePassword).toHaveBeenCalledWith({
      current_password: 'CurrentPass123!',
      password: 'NewSecurePass456!',
      password_confirmation: 'NewSecurePass456!',
    })

    expect(wrapper.text()).toContain('Password changed successfully.')

    expect(
      (wrapper.get('#current-password').element as HTMLInputElement).value,
    ).toBe('')

    expect(
      (wrapper.get('#new-password').element as HTMLInputElement).value,
    ).toBe('')

    expect(
      (wrapper.get('#password-confirmation').element as HTMLInputElement)
        .value,
    ).toBe('')
  })

  it('displays password validation errors returned by the API', async () => {
    setVerifiedUser()

    mocks.changePassword.mockRejectedValue({
      isAxiosError: true,
      response: {
        data: {
          message: 'The given data was invalid.',
          errors: {
            password: ['The password confirmation does not match.'],
          },
        },
      },
    })

    const wrapper = mount(DashboardView)
    await fillPasswordForm(wrapper)
    await wrapper.get('form.password-form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain(
      'The password confirmation does not match.',
    )
  })

  it('disables the submit button while the password is changing', async () => {
    setVerifiedUser()

    let resolveRequest: ((message: string) => void) | undefined

    mocks.changePassword.mockImplementation(
      () =>
        new Promise<string>((resolve) => {
          resolveRequest = resolve
        }),
    )

    const wrapper = mount(DashboardView)
    await fillPasswordForm(wrapper)
    await wrapper.get('form.password-form').trigger('submit')

    const submitButton = wrapper.get('form.password-form button')

    expect((submitButton.element as HTMLButtonElement).disabled).toBe(true)
    expect(submitButton.text()).toContain('Changing password')

    resolveRequest?.('Password changed successfully.')
    await flushPromises()

    expect((submitButton.element as HTMLButtonElement).disabled).toBe(false)
    expect(submitButton.text()).toContain('Change password')
  })
})