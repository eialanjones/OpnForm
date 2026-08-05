import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { mockNuxtImport } from '@nuxt/test-utils/runtime'
import MentorfySsoPage from '~/pages/auth/mentorfy.vue'
import checkAuthMiddleware from '~/middleware/01.check-auth.global.js'
import { getOpnRequestsOptions } from '~/composables/useOpnApi.js'

const nuxtMocks = vi.hoisted(() => ({
  queryClient: null as Record<string, any> | null,
  handleAuthSuccess: vi.fn(() => Promise.resolve()),
  handleTokenExpiry: vi.fn(() => Promise.resolve()),
  opnFetch: vi.fn(() => Promise.resolve({
    token: 'fresh-token',
    expires_in: 3600,
    user: { id: 123 },
  })),
  route: {
    query: {
      token: 'short-mentorfy-token',
      redirect: '/forms/create',
    },
  },
  router: {
    replace: vi.fn(() => Promise.resolve()),
  },
}))

vi.mock('@tanstack/vue-query', () => ({
  useQueryClient: () => nuxtMocks.queryClient,
}))

mockNuxtImport('opnFetch', () => nuxtMocks.opnFetch)
mockNuxtImport('useRoute', () => () => nuxtMocks.route)
mockNuxtImport('useRouter', () => () => nuxtMocks.router)
mockNuxtImport('useAuthFlow', () => () => ({
  handleAuthSuccess: nuxtMocks.handleAuthSuccess,
  handleTokenExpiry: nuxtMocks.handleTokenExpiry,
}))

vi.mock('~/lib/utils.js', () => ({
  customDomainUsed: () => false,
  getDomain: vi.fn(),
  getHost: vi.fn(),
}))

describe('Mentorfy SSO flow', () => {
  let authStore: Record<string, any>
  let appStore: Record<string, any>
  let queryClient: Record<string, any>
  let router: Record<string, any>

  beforeEach(() => {
    vi.clearAllMocks()
    authStore = {
      token: null,
      admin_token: null,
      clearTokens: vi.fn(),
      clearToken: vi.fn(),
      updateUser: vi.fn(),
      initStore: vi.fn(),
    }
    appStore = {
      isUnauthorizedError: true,
      quickLoginModal: true,
      quickRegisterModal: false,
      resetAuthModals: vi.fn(() => {
        appStore.isUnauthorizedError = false
        appStore.quickLoginModal = false
        appStore.quickRegisterModal = false
      }),
    }
    queryClient = {
      clear: vi.fn(),
      getQueryData: vi.fn(),
    }
    nuxtMocks.queryClient = queryClient
    router = nuxtMocks.router
    nuxtMocks.route.query = {
      token: 'short-mentorfy-token',
      redirect: '/forms/create',
    }
    nuxtMocks.router.replace.mockResolvedValue(undefined)
    nuxtMocks.handleAuthSuccess.mockResolvedValue(undefined)
    nuxtMocks.handleTokenExpiry.mockResolvedValue(undefined)
    nuxtMocks.opnFetch.mockResolvedValue({
      token: 'fresh-token',
      expires_in: 3600,
      user: { id: 123 },
    })

    vi.stubGlobal('useAuthStore', () => authStore)
    vi.stubGlobal('useAppStore', () => appStore)
    vi.stubGlobal('useRuntimeConfig', () => ({
      apiSecret: null,
      privateApiBase: null,
      public: {
        apiBase: 'https://forms.mentorfy.test/api',
        appUrl: 'https://forms.mentorfy.test',
      },
    }))
    vi.stubGlobal('useNuxtApp', () => ({
      $i18n: { locale: { value: 'pt' } },
    }))
    vi.stubGlobal('useFeatureFlag', () => false)
  })

  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('drops stale client state and exchanges without the previous bearer', async () => {
    mount(MentorfySsoPage, {
      global: {
        stubs: { Loader: true },
      },
    })

    await flushPromises()

    expect(authStore.clearTokens).toHaveBeenCalledOnce()
    expect(authStore.updateUser).toHaveBeenCalledWith(null)
    expect(queryClient.clear).toHaveBeenCalledOnce()
    expect(appStore.resetAuthModals).toHaveBeenCalledOnce()
    expect(nuxtMocks.opnFetch).toHaveBeenCalledWith('/auth/mentorfy/exchange', expect.objectContaining({
      auth: false,
      handleUnauthorized: false,
      body: { token: 'short-mentorfy-token' },
    }))
    expect(nuxtMocks.handleAuthSuccess).toHaveBeenCalledWith(expect.objectContaining({
      token: 'fresh-token',
    }), 'mentorfy-sso')
    expect(router.replace).toHaveBeenCalledWith('/forms/create')
  })

  it('skips the global auth bootstrap on an external auth bridge', async () => {
    const authStoreSpy = vi.fn(() => authStore)
    vi.stubGlobal('useAuthStore', authStoreSpy)

    await checkAuthMiddleware(
      { meta: { skipAuthBootstrap: true } } as any,
      {} as any,
    )

    expect(authStoreSpy).not.toHaveBeenCalled()
  })

  it('does not attach auth or open expiry UX for a public exchange 401', async () => {
    authStore.token = 'old-token'
    const options = getOpnRequestsOptions('/auth/mentorfy/exchange', {
      method: 'POST',
      auth: false,
      handleUnauthorized: false,
    })

    expect(options.headers.Authorization).toBeUndefined()
    expect(options).not.toHaveProperty('auth')
    expect(options).not.toHaveProperty('handleUnauthorized')

    await options.onResponseError({ response: { status: 401 } } as any)

    expect(nuxtMocks.handleTokenExpiry).not.toHaveBeenCalled()
  })

  it('ignores a late 401 after another login replaces the request token', async () => {
    authStore.token = 'old-token'
    const options = getOpnRequestsOptions('/user', {})

    expect(options.headers.Authorization).toBe('Bearer old-token')

    authStore.token = 'fresh-token'
    await options.onResponseError({ response: { status: 401 } } as any)

    expect(nuxtMocks.handleTokenExpiry).not.toHaveBeenCalled()
  })

  it('expires the session when the 401 belongs to the current token', async () => {
    authStore.token = 'current-token'
    const options = getOpnRequestsOptions('/user', {})

    await options.onResponseError({ response: { status: 401 } } as any)

    expect(nuxtMocks.handleTokenExpiry).toHaveBeenCalledWith('current-token')
  })
})
