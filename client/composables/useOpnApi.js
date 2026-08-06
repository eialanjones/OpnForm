import { getDomain, getHost, customDomainUsed } from "~/lib/utils.js"

function addAuthHeader(request, options, token) {
  if (token) {
    options.headers = {
      Authorization: `Bearer ${token}`,
      ...options.headers,
    }
  }
}

function addPasswordToFormRequest(request, options) {
  if (!request || !request.startsWith("/forms/")) return

  const slug = request.split("/")[2]

  const passwordCookie = useCookie("password-" + slug, {
    maxAge: 60 * 60 * 24 * 30,
  }) // 30 days
  if (slug !== undefined && slug !== "" && passwordCookie.value !== undefined) {
    options.headers["form-password"] = passwordCookie.value
  }
}

/**
 * Add custom domain header if custom domain is used
 */
function addCustomDomainHeader(request, options) {
  if (!customDomainUsed()) return
  options.headers["x-custom-domain"] = getDomain(getHost())
}

/**
 * Laravel picks its locale from Accept-Language (App\Http\Middleware\SetLocale), so this is
 * what makes server-side validation messages and Carbon relative dates ("há 2 dias") come
 * back in the language currently on screen instead of the browser's preference.
 */
function addLocaleHeader(request, options) {
  const locale = useNuxtApp().$i18n?.locale?.value
  if (!locale) return
  options.headers["Accept-Language"] = locale
}

export function getOpnRequestsOptions(request, opts) {
  const config = useRuntimeConfig()
  const authStore = useAuthStore()
  const shouldAuthenticate = opts.auth !== false
  const shouldHandleUnauthorized = opts.handleUnauthorized ?? shouldAuthenticate
  const requestToken = shouldAuthenticate ? authStore.token : null

  // `auth` and `handleUnauthorized` are client-only controls. Do not leak them
  // into ofetch, where they have no meaning.
  const requestOptions = { ...opts }
  delete requestOptions.auth
  delete requestOptions.handleUnauthorized

  if (requestOptions.body && requestOptions.body instanceof FormData) {
    requestOptions.headers = {
      charset: "utf-8",
      ...requestOptions.headers,
    }
  }

  requestOptions.headers = { accept: "application/json", ...requestOptions.headers }

  // Authenticate requests coming from the server
  if (import.meta.server && config.apiSecret) {
    requestOptions.headers["x-api-secret"] = config.apiSecret
  }

  // SSR acts on behalf of the browser. The API pins every JWT to the User Agent
  // that minted it (User::getJWTCustomClaims) and AuthenticateJWT rejects any
  // request that arrives under a different one — so a server-side call carrying
  // Nitro's own agent reads as a stolen token, and the reload that triggers it
  // lands on the login page. Forward the browser's agent so the check validates
  // what it exists to validate.
  if (import.meta.server) {
    const forwardedUserAgent = useRequestHeaders(["user-agent"])["user-agent"]
    if (forwardedUserAgent) {
      requestOptions.headers["user-agent"] = forwardedUserAgent
    }
  }

  addAuthHeader(request, requestOptions, requestToken)
  addPasswordToFormRequest(request, requestOptions)
  addCustomDomainHeader(request, requestOptions)
  addLocaleHeader(request, requestOptions)

  if (!requestOptions.baseURL) {
    // Use privateApiBase only on server side, fallback to public.apiBase on client
    requestOptions.baseURL = (import.meta.server && config.privateApiBase) || config.public.apiBase
  }

  return {
    async onResponseError({ response }) {
      const { status } = response
      if (status === 401 && shouldHandleUnauthorized && requestToken) {
        // Do not run token-expiry UX during SSR. Server-side bootstrap requests can
        // fail for context-specific reasons and should be retried client-side first.
        // Also ignore a late response belonging to a token that has already been
        // replaced by another login.
        if (import.meta.client && authStore.token === requestToken) {
          const { handleTokenExpiry } = useAuthFlow()
          await handleTokenExpiry(requestToken)
        }
      } else if (status === 420) {
        // If invalid domain, redirect to main domain
        console.warn("Invalid response from back-end - redirecting to main domain")
        window.location.href =
          config.public.appUrl + "?utm_source=failed_custom_domain_redirect"
      } else if (status >= 500) {
        console.error("Request error", status)
      }
    },
    ...requestOptions,
  }
}

export const opnFetch = (request, opts = {}) => {
  return $fetch(request, getOpnRequestsOptions(request, opts))
}

export const useOpnApi = (request, opts = {}) => {
  return useFetch(request, getOpnRequestsOptions(request, opts))
}
