<template>
  <div>
    <NuxtLayout>
      <div class="flex mt-6">
        <div class="w-full md:w-2/3 md:mx-auto md:max-w-md">
          <img
            :alt="$t('app_shell.error_page.image_alt')"
            src="/img/icons/plant.png"
            class="w-56 mb-5"
          >

          <h1 class="mb-6 font-semibold text-3xl text-neutral-900">
            {{ $t('app_shell.error_page.title', { code: error.statusCode || '404' }) }}
          </h1>

          <div class="links">
            <NuxtLink
              :to="{ name: 'index' }"
              class="hover:underline text-neutral-700"
            >
              {{ $t('app_shell.error_page.go_home') }}
            </NuxtLink>
          </div>
        </div>
      </div>
    </NuxtLayout>
  </div>
</template>

<script setup>
import { captureException } from '@sentry/core'

const { t } = useI18n()

useOpnSeoMeta({
  title: () => t("app_shell.error_page.seo_title"),
})

const props = defineProps({
  error: { type: Object, default: null }
})

if (props.error?.statusCode === 500) {
  // Track in Sentry 500 errors
  const exception = new Error(props.error?.message ?? props.error?.statusMessage)
  exception.code = props.error?.statusCode
  exception.stack = props.error?.stack
  captureException(exception, {
    message: props.error?.message ?? props.error?.statusMessage,
    type: '500_error',
    user_id: useAuth().user().data.value?.id
  })
}
</script>
