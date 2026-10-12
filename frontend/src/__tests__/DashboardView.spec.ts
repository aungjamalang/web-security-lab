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
  routerReplace: vi.fn(),
}))

vi.mock('@/stores/auth', () => ({
  useAuthStore: () => ({
    user: mocks.user,
    isLoadingUser: mocks.isLoadingUser,
    fetchUser: mocks.fetchUser,
    logout: mocks.logout,
    resendVerificationEmail: mocks.resendVerificationEmail,
  }),
}))

vi.mock('vue-router', () => ({
  useRouter: () => ({
    replace: mocks.routerReplace,
  }),
}))

describe('DashboardView email verification', () => {
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
  })

  it('shows a notice and resend button for unverified users', () => {
    const wrapper = mount(DashboardView)

    expect(wrapper.text()).toContain(
      'Your email address has not been verified yet.',
    )

    expect(wrapper.text()).toContain(
      'Resend verification email',
    )
  })

  it('shows verified status and hides the resend button', () => {
    mocks.user = {
      id: 1,
      name: 'Test User',
      email: 'test@example.test',
      email_verified_at: '2026-10-12T12:00:00.000000Z',
    }

    const wrapper = mount(DashboardView)

    expect(wrapper.text()).toContain(
      'Your email address is verified.',
    )

    expect(wrapper.text()).not.toContain(
      'Resend verification email',
    )
  })

  it('resends the email and displays the success message', async () => {
    const wrapper = mount(DashboardView)

    await wrapper
      .get('button')
      .trigger('click')

    await flushPromises()

    expect(
      mocks.resendVerificationEmail,
    ).toHaveBeenCalledOnce()

    expect(wrapper.text()).toContain(
      'Verification link sent.',
    )
  })

  it('shows an error when resending fails', async () => {
    mocks.resendVerificationEmail.mockRejectedValue(
      new Error('Request failed'),
    )

    const wrapper = mount(DashboardView)

    await wrapper
      .get('button')
      .trigger('click')

    await flushPromises()

    expect(wrapper.text()).toContain(
      'Unable to send the verification email. Please try again.',
    )
  })
})