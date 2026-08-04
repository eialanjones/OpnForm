<template>
  <div class="flex flex-col items-center justify-center min-h-screen gap-4">
    <Loader class="w-8 h-8 text-blue-500" />
    <p class="text-neutral-500">
      {{ $t('marketing.redirects.billing_portal_loading') }}
    </p>
  </div>
</template>

<script setup>
import { billingApi } from "~/api"

const { t } = useI18n()

definePageMeta({
  middleware: 'auth'
})

onMounted(async () => {
  try {
    const { portal_url } = await billingApi.getBillingPortal()
    if (!portal_url) {
      throw new Error('No portal URL returned')
    }
    window.location.href = portal_url
  } catch {
    useAlert().error(t('marketing.redirects.billing_portal_error'))
    setTimeout(() => {
      navigateTo({name: 'home'})
    }, 2000)
  }
})
</script> 