import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { computed } from 'vue'
import { useOidcLinking } from '../../composables/useOidcLinking.js'

vi.mock('~/api', () => ({
  oidcApi: {
    link: vi.fn(),
  },
}))

describe('useOidcLinking', () => {
  let router
  let route
  let alert

  beforeEach(() => {
    router = {
      push: vi.fn(),
      replace: vi.fn(),
    }
    route = {
      query: {},
    }
    alert = {
      success: vi.fn(),
      error: vi.fn(),
    }

    globalThis.computed = computed
    globalThis.useRouter = () => router
    globalThis.useRoute = () => route
    globalThis.useAlert = () => alert
    // The unit project runs in a bare node environment with no Nuxt auto-imports, so the
    // i18n call inside the composable needs the same treatment as useRoute/useAlert.
    globalThis.useI18n = () => ({ t: (key) => key })
  })

  afterEach(() => {
    vi.clearAllMocks()
    delete globalThis.computed
    delete globalThis.useRouter
    delete globalThis.useRoute
    delete globalThis.useAlert
    delete globalThis.useI18n
  })

  it('starts linking by pushing login with token', () => {
    const { startLink } = useOidcLinking()
    startLink('token-123')

    expect(router.push).toHaveBeenCalledWith({
      name: 'login',
      query: { oidc_link_token: 'token-123' },
    })
  })

  it('links account and clears query on success', async () => {
    const { oidcApi } = await import('~/api')
    oidcApi.link.mockResolvedValue({ linked: true })

    route.query = { oidc_link_token: 'token-123', extra: 'keep' }
    const { completeLinkIfNeeded } = useOidcLinking()

    await completeLinkIfNeeded()

    expect(oidcApi.link).toHaveBeenCalledWith('token-123')
    expect(alert.success).toHaveBeenCalled()
    expect(router.replace).toHaveBeenCalledWith({
      query: { extra: 'keep' },
    })
  })

  it('shows error when linking fails', async () => {
    const { oidcApi } = await import('~/api')
    oidcApi.link.mockRejectedValue({
      response: {
        _data: {
          message: 'Link failed',
        },
      },
    })

    route.query = { oidc_link_token: 'token-123' }
    const { completeLinkIfNeeded } = useOidcLinking()

    await completeLinkIfNeeded()

    expect(alert.error).toHaveBeenCalledWith('Link failed')
    expect(router.replace).toHaveBeenCalledWith({
      query: {},
    })
  })
})
