import { defineStore } from "pinia"
import { authApi } from "~/api"

const AUTH_COOKIE_NAME = "opnform_token"
const LEGACY_AUTH_COOKIE_NAME = "token"
const ADMIN_AUTH_COOKIE_NAME = "opnform_admin_token"
const LEGACY_ADMIN_AUTH_COOKIE_NAME = "admin_token"

export const useAuthStore = defineStore("auth", {
  state: () => {
    return {
      token: null,
      admin_token: null,
      user: null,
    }
  },
  getters: {
    check: (state) => state.user !== null && state.user !== undefined,
    has_active_license: (state) => state.user !== null && state.user !== undefined && state.user.active_license !== null,
    isImpersonating: (state) =>
      state.admin_token !== null && state.admin_token !== undefined,
  },
  actions: {
    // Stores admin token temporarily for impersonation
    startImpersonating() {
      this.setAdminToken(this.token)
    },
    // Stop admin impersonation
    stopImpersonating() {
      // When stopping impersonation, we don't have expiration info for the admin token
      // Use a default long expiration (24 hours) to ensure the admin can continue working
      this.setToken(this.admin_token, 60 * 60 * 24)
      this.setAdminToken(null)
    },

    setToken(token, expiresIn) {
      // Set cookie with expiration if provided
      const cookieOptions = {}
      
      if (expiresIn) {
        // expiresIn is in seconds, maxAge also needs to be in seconds
        cookieOptions.maxAge = expiresIn
      }
      
      this.setCookie(AUTH_COOKIE_NAME, token, cookieOptions)
      this.token = token
    },

    setAdminToken(token) {
      this.setCookie(ADMIN_AUTH_COOKIE_NAME, token)
      this.admin_token = token
    },

    // Writes straight to document.cookie instead of going through useCookie.
    //
    // useCookie only flushes a new value on the next Vue scheduler tick, while
    // *creating* a useCookie ref for a cookie that isn't there yet writes an
    // expired cookie synchronously. Setting a token and navigating away in the
    // same tick — which is exactly what the Mentorfy SSO bridge does — puts
    // those two writes in a race, and when the delete wins the session survives
    // only in memory: navigation keeps working and the next reload lands on the
    // login page. Writing here is synchronous, so there is nothing to race.
    setCookie(name, value, options = {}) {
      if (!import.meta.client) return

      const embedded = window.top !== window.self
      const path = options.path ?? "/"
      const sameSite = options.sameSite ?? (embedded ? "none" : "lax")
      const secure =
        options.secure ?? (embedded ? true : window.location.protocol === "https:")

      // A null value means "drop this cookie", regardless of the max age asked for.
      const clearing = value === null || value === undefined
      const maxAge = clearing ? 0 : options.maxAge

      const parts = [
        `${name}=${clearing ? "" : encodeURIComponent(value)}`,
        `Path=${path}`,
      ]

      // No max age on a live value means a session cookie, same as before.
      if (maxAge !== undefined) parts.push(`Max-Age=${maxAge}`)
      if (sameSite) parts.push(`SameSite=${sameSite}`)
      if (secure) parts.push("Secure")

      document.cookie = parts.join("; ")
    },

    initStore(token, adminToken) {
      // Prefer explicit values from cookies, but do not clobber a live in-memory
      // token during client-side navigation when cookie reactivity lags behind.
      if (token !== undefined) {
        this.token = token ?? this.token
      }

      if (adminToken !== undefined) {
        this.admin_token = adminToken ?? this.admin_token
      }
    },

    setUser(user) {
      this.user = user
    },

    updateUser(payload) {
      this.user = payload
    },

    logout() {
      authApi.logout().catch(() => {})

      this.user = null
      
      this.clearToken()
    },

    clearToken(){
      this.setCookie(AUTH_COOKIE_NAME, null, { maxAge: 0 })
      this.setCookie(LEGACY_AUTH_COOKIE_NAME, null, { maxAge: 0 })
      this.token = null
    },

    clearTokens(){
      this.clearToken()
      this.setCookie(ADMIN_AUTH_COOKIE_NAME, null, { maxAge: 0 })
      this.setCookie(LEGACY_ADMIN_AUTH_COOKIE_NAME, null, { maxAge: 0 })
      this.admin_token = null
    },
  },
})
