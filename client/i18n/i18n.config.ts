export default defineI18nConfig(() => ({
  legacy: false,
  // The admin/builder namespaces ship in pt and en only. Every other locale keeps its
  // own public-form translation and falls back to English for the builder strings.
  fallbackLocale: 'en',
  // Missing keys already fall back silently; keep the console quiet in production.
  silentFallbackWarn: true,
  silentTranslationWarn: true,
}))
