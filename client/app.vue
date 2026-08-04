<template>
  <AppProvider>
    <div
      id="app"
      class="bg-white dark:bg-notion-dark"
    >
      <NuxtLoadingIndicator color="#de5d00" />
      <NuxtLayout>
        <NuxtPage />
      </NuxtLayout>

      <!-- Third-party services and modals - only load when not on public form pages -->
      <ClientOnly v-if="!isPublicFormPage">
        <div
          class="fixed z-[9999] left-0 bottom-0 p-4" id="admin-actions"
        >
          <UButtonGroup size="sm">
            <ToolsStopImpersonation />
          </UButtonGroup>
        </div>

        <Clarity />
        <FeatureBase />
        <SubscriptionModal />
        <QuickRegister />
      </ClientOnly>
    </div>
  </AppProvider>
</template>

<script setup>
import FeatureBase from "~/components/vendor/FeatureBase.vue"
import Clarity from "~/components/vendor/Clarity.vue"

const config = useRuntimeConfig()
const route = useRoute()
const { t, locale, locales } = useI18n()

// Check if current page is a public form page (for performance optimization)
const isPublicFormPage = computed(() => route.name === 'forms-slug')

// SEO and head configuration
useOpnSeoMeta({
  title: () => t("app_shell.seo.default_title"),
  description: () => t("app_shell.seo.default_description"),
  ogImage: "/img/social-preview.jpg",
  robots: () => {
    return config.public.env === "production" ? null : "noindex, nofollow"
  },
})

useHead({
  titleTemplate: (titleChunk) => {
    return titleChunk ? `${titleChunk} - Forms Mentorfy` : "Forms Mentorfy"
  },
  meta: [
    {
      name: 'mobile-web-app-capable',
      content: 'yes'
    },
    {
      name: 'apple-mobile-web-app-status-bar-style',
      content: 'black-translucent'
    },
  ],
  link: [
    {
      rel: 'apple-touch-icon',
      type: 'image/png',
      sizes: '180x180',
      href: '/apple-touch-icon.png'
    }
  ],
  htmlAttrs: () => ({
    // Keep <html lang> in step with the active locale — screen readers and translation
    // prompts key off it, and it used to be hardcoded away by the ltr-only attrs.
    lang: locales.value.find(l => l.code === locale.value)?.iso ?? locale.value,
    dir: 'ltr'
  })
})
</script>
