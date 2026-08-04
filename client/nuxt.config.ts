// https://nuxt.com/docs/api/configuration/nuxt-config
import runtimeConfig from "./runtimeConfig"
import sitemap from "./sitemap"

// Admin/builder translation namespaces. Each one is a separate file per locale so the
// bundles stay readable and reviewable instead of a single multi-thousand-line blob.
// Shipped for pt + en only; every other locale falls back to en for these keys.
const UI_NAMESPACES = [
  'common',
  'app_shell',
  'auth',
  'form_editor',
  'form_blocks',
  'form_logic',
  'form_fields',
  'form_share',
  'form_pages',
  'submissions',
  'integrations',
  'workspace',
  'user_settings',
  'admin',
  'marketing',
  'inputs',
  'widgets',
  'runtime',
]

export default defineNuxtConfig({
  loglevel: process.env.NUXT_LOG_LEVEL || 'info',
  devtools: {enabled: true},
  css: ['~/css/app.css'],

  // Disable certain plugins during testing
  modules: process.env.VITEST ? [] : [
      '@pinia/nuxt', 
      '@vueuse/nuxt', 
      '@vueuse/motion/nuxt', 
      '@nuxtjs/sitemap',
      '@nuxt/ui', 
      'nuxt-utm', 
      '@nuxtjs/i18n',
      '@nuxt/icon', 
      '@sentry/nuxt/module',
      '@zadigetvoltaire/nuxt-gtm',
  ],

  // Skip plugin initialization during tests
  plugins: process.env.VITEST ? [
      // Only include plugins safe for testing
  ] : [
      // Full plugin list for production/dev
  ],

  build: {
      transpile: ["vue-notion", "vue-signature-pad", "@zxing/library"],
  },

  i18n: {
      locales: [
        { code: 'ar', name: 'Arabic', iso: 'ar-EG', file: 'ar.json' },
        { code: 'bn', name: 'Bengali', iso: 'bn-BD', file: 'bn.json' },
        { code: 'ca', name: 'Valencian/Catalan', iso: 'ca-ES', file: 'ca.json' },
        { code: 'cs', name: 'Czech', iso: 'cs-CZ', file: 'cs.json' },
        { code: 'de', name: 'German', iso: 'de-DE', file: 'de.json' },
        { code: 'en', name: 'English', iso: 'en-US', files: ['en.json', ...UI_NAMESPACES.map(ns => `en/${ns}.json`)] },
        { code: 'es', name: 'Spanish', iso: 'es-ES', file: 'es.json' },
        { code: 'eu', name: 'Basque', iso: 'eu-ES', file: 'eu.json' },
        { code: 'fr', name: 'French', iso: 'fr-FR', file: 'fr.json' },
        { code: 'gl', name: 'Galician', iso: 'gl-ES', file: 'gl.json' },
        { code: 'hi', name: 'Hindi', iso: 'hi-IN', file: 'hi.json' },
        { code: 'hu', name: 'Hungarian', iso: 'hu-HU', file: 'hu.json' },
        { code: 'it', name: 'Italian', iso: 'it-IT', file: 'it.json' },
        { code: 'ja', name: 'Japanese', iso: 'ja-JP', file: 'ja.json' },
        { code: 'jv', name: 'Javanese', iso: 'jv-ID', file: 'jv.json' },
        { code: 'ko', name: 'Korean', iso: 'ko-KR', file: 'ko.json' },
        { code: 'mr', name: 'Marathi', iso: 'mr-IN', file: 'mr.json' },
        { code: 'nl', name: 'Dutch', iso: 'nl-NL', file: 'nl.json' },
        { code: 'pa', name: 'Punjabi', iso: 'pa-IN', file: 'pa.json' },
        { code: 'pl', name: 'Polish', iso: 'pl-PL', file: 'pl.json' },
        { code: 'pt', name: 'Portuguese', iso: 'pt-BR', files: ['pt.json', ...UI_NAMESPACES.map(ns => `pt/${ns}.json`)] },
        { code: 'ru', name: 'Russian', iso: 'ru-RU', file: 'ru.json' },
        { code: 'sk', name: 'Slovak', iso: 'sk-SK', file: 'sk.json' },
        { code: 'sr', name: 'Serbian', iso: 'sr-RS', file: 'sr.json' },
        { code: 'sv', name: 'Swedish', iso: 'sv-SE', file: 'sv.json' },
        { code: 'ta', name: 'Tamil', iso: 'ta-IN', file: 'ta.json' },
        { code: 'te', name: 'Telugu', iso: 'te-IN', file: 'te.json' },
        { code: 'tr', name: 'Turkish', iso: 'tr-TR', file: 'tr.json' },
        { code: 'uk', name: 'Ukrainian', iso: 'uk-UA', file: 'uk.json' },
        { code: 'ur', name: 'Urdu', iso: 'ur-PK', file: 'ur.json' },
        { code: 'vi', name: 'Vietnamese', iso: 'vi-VN', file: 'vi.json' },
        { code: 'zh', name: 'Chinese', iso: 'zh-CN', file: 'zh.json' },
      ],
      defaultLocale: 'pt',
      lazy: true,
      langDir: 'lang/',
      strategy: 'no_prefix',
      // A handful of help texts carry inline emphasis (<b>, <span class="font-semibold">)
      // that the English markup already had. Every one of them renders through v-html, so
      // the messages keep their tags instead of being split into fragments that no
      // translator could reassemble. Messages come from these JSON files only — never
      // from user input — so there is no injection surface here.
      compilation: {
          strictMessage: false,
      },
      // Portuguese is the product default. The locale is never inferred from
      // Accept-Language — it stays pt until the user picks another one in the UI,
      // which `plugins/locale.client.js` then restores from a cookie on every visit.
      detectBrowserLanguage: false
  },

  experimental: {
      inlineRouteRules: true
  },

  sentry: {
      sourceMapsUploadOptions: {
          authToken: process.env.SENTRY_AUTH_TOKEN,
          org: "opnform",
          project: "opnform-vue",
      },
  },

  sourcemap: { client: 'hidden' },

  gtag: {
      id: process.env.NUXT_PUBLIC_GOOGLE_ANALYTICS_CODE,
  },

  ui: {
    theme: {
        colors: [
            'primary',
            'secondary',
            'success',
            'error',
            'warning',
            'info',
            'neutral',
            'form'
        ]
    }
  },

  components: [
      {
          path: '~/components/forms/core',
          pathPrefix: false,
          global: true,
      },
      {
          path: '~/components/forms/heavy',
          pathPrefix: false,
          global: false,
      },
      {
          path: '~/components/global',
          pathPrefix: false,
      },
      {
          path: '~/components/pages',
          pathPrefix: false,
      },
      '~/components',
  ],

  colorMode: {
      preference: 'light',
      fallback: 'light',
      classPrefix: '',
  },

  icon: {
      customCollections: [
          {
              prefix: 'opnform',
              dir: './public/icons'
          },
      ],
      clientBundle: {
          includeCustomCollections: true,
          scan: {
              globInclude: ['**/*.vue', '**/*.json'],
          },
      },
    },

  devServer: {
    host: process.env.NUXT_HOST || 'localhost',
    port: Number(process.env.NUXT_PORT) || 3000,
  },

  sitemap,
  runtimeConfig,
  compatibilityDate: '2024-10-30'
})
