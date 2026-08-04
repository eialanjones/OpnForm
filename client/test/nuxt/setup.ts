import { computed, ref } from 'vue'
import { vi } from 'vitest'
import { config } from '@vue/test-utils'

// nuxt.config disables every module when VITEST=true, so @nuxtjs/i18n never registers and
// neither useI18n nor $t exists here. Rather than stub them into returning raw keys — which
// would silently break every assertion on rendered copy — resolve against the real English
// messages, so components under test read exactly as they did before the i18n migration.
const enMessages: Record<string, unknown> = {}
for (const loaded of Object.values(
  import.meta.glob('../../i18n/lang/en{.json,/*.json}', { eager: true, import: 'default' }),
)) {
  Object.assign(enMessages, loaded as Record<string, unknown>)
}

const translate = (key: string, params?: Record<string, unknown> | number) => {
  const message = key.split('.').reduce<unknown>(
    (node, part) => (node && typeof node === 'object' ? (node as Record<string, unknown>)[part] : undefined),
    enMessages,
  )
  if (typeof message !== 'string') return key
  if (!params || typeof params !== 'object') return message
  return message.replace(/\{(\w+)\}/g, (raw, name) => (name in params ? String(params[name]) : raw))
}

globalThis.useI18n = () => ({
  t: translate,
  te: (key: string) => translate(key) !== key,
  locale: ref('en'),
  locales: computed(() => [
    { code: 'pt', iso: 'pt-BR' },
    { code: 'en', iso: 'en-US' },
  ]),
  setLocale: vi.fn(),
})

config.global.mocks.$t = translate
config.global.mocks.$te = (key: string) => translate(key) !== key

vi.mock('~/middleware/auth', () => ({
  default: () => {},
}))

vi.mock('~/middleware/auth.js', () => ({
  default: () => {},
}))

globalThis.useAuthStore = () => ({
  token: null,
  initStore: vi.fn(),
  clearToken: vi.fn(),
})

globalThis.useQueryClient = () => ({
  getQueryData: vi.fn(),
  clear: vi.fn(),
})

globalThis.useAuth = () => ({
  user: () => ({ suspense: vi.fn() }),
})

globalThis.useWorkspaces = () => ({
  list: () => ({ suspense: vi.fn() }),
})

globalThis.useOverlay = () => ({
  create: () => ({
    open: vi.fn(),
    close: vi.fn(),
  }),
})

globalThis.useRoute = () => ({
  query: {},
})

globalThis.useRouter = () => ({
  replace: vi.fn(),
  push: vi.fn(),
})

globalThis.useRouteQuery = () => ref(null)

globalThis.useIsAuthenticated = () => ({
  isAuthenticated: computed(() => false),
})

globalThis.useGtm = () => ({
  trackEvent: vi.fn(),
})

globalThis.navigateTo = vi.fn()

globalThis.useCookie = () => ({
  value: null,
})
