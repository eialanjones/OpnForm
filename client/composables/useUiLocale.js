/**
 * Locale of the admin UI (dashboard, form editor, settings).
 *
 * Portuguese is the product default and browser detection is disabled, so the only thing
 * that changes the UI language is an explicit choice by the user. That choice is kept in
 * a cookie and replayed on every visit by `plugins/locale.client.js`.
 *
 * This is deliberately separate from the *form* language: a public form always renders in
 * its own `form.language`, whatever the owner picked in the editor.
 */
export const UI_LOCALE_COOKIE = "mentorfy_ui_locale"

// Only the locales the builder is actually translated into. Every other locale in
// nuxt.config exists for the public form renderer only.
export const UI_LOCALES = [
  { code: "pt", label: "Português", flag: "🇧🇷" },
  { code: "en", label: "English", flag: "🇺🇸" },
]

export function useUiLocale() {
  const { locale, setLocale } = useI18n()

  const cookie = useCookie(UI_LOCALE_COOKIE, {
    maxAge: 60 * 60 * 24 * 365,
    sameSite: "lax",
    path: "/",
  })

  // The public form overrides the locale while it is mounted; the stored choice is what
  // the builder must come back to.
  const uiLocale = computed(() => {
    const stored = cookie.value
    return UI_LOCALES.some(l => l.code === stored) ? stored : "pt"
  })

  const current = computed(() => UI_LOCALES.find(l => l.code === uiLocale.value) ?? UI_LOCALES[0])

  async function setUiLocale(code) {
    if (!UI_LOCALES.some(l => l.code === code)) return
    cookie.value = code
    await setLocale(code)
  }

  /** Restore the builder language after a preview forced the form's own language. */
  async function restoreUiLocale() {
    if (locale.value !== uiLocale.value) {
      await setLocale(uiLocale.value)
    }
  }

  return { locale, uiLocale, current, locales: UI_LOCALES, setUiLocale, restoreUiLocale }
}
