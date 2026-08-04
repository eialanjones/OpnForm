<template>
  <UAlert
    v-if="showBanner"
    class="mt-8 p-4"
    icon="i-heroicons-sparkles-solid"
    color="primary"
    variant="subtle"
    :title="$t('app_shell.upgrade_banner.title')"
    :description="$t('app_shell.upgrade_banner.description')"
    :actions="[
      {
        label: $t('app_shell.upgrade_banner.upgrade_now'),
        onClick: () => openSubscriptionModal({modal_title: $t('app_shell.upgrade_banner.modal_title')})
      },
      {
        label: $t('common.actions.close'),
        color: 'neutral',
        variant: 'outline',
        onClick: dismissBanner
      }
    ]"
  />
</template>

<script setup>
const COOKIE_NAME = 'upgrade_banner_dismissed'
const COOKIE_EXPIRY_DAYS = 7

// Composables
const { openSubscriptionModal } = useAppModals()

// Cookie state
const dismissedCookie = useCookie(COOKIE_NAME, {
  default: () => false,
  maxAge: COOKIE_EXPIRY_DAYS * 24 * 60 * 60 * 1000, // 7 days in milliseconds
  sameSite: 'lax',
  secure: true,
  httpOnly: false
})

// Computed
const { current: workspace } = useCurrentWorkspace()
const isSelfHosted = computed(() => useFeatureFlag('self_hosted'))

const showBanner = computed(() => {
  return (
    !dismissedCookie.value &&
    workspace.value &&
    !workspace.value.is_pro &&
    !isSelfHosted.value
  )
})

const dismissBanner = () => {
  dismissedCookie.value = true
}
</script> 