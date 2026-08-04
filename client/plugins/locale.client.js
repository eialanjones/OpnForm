import { UI_LOCALE_COOKIE } from "~/composables/useUiLocale"

/**
 * Browser language detection is off: Portuguese is the product default. This restores
 * the locale the user explicitly picked in the UI, which is the only thing allowed to
 * override that default.
 */
export default defineNuxtPlugin(async (nuxtApp) => {
  const i18n = nuxtApp.$i18n
  if (!i18n) return

  const stored = useCookie(UI_LOCALE_COOKIE).value
  if (!stored || stored === i18n.locale.value) return
  if (!i18n.locales.value.some(locale => (locale.code ?? locale) === stored)) return

  await i18n.setLocale(stored)
})
