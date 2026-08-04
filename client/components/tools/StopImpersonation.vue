<template>
  <UTooltip
    v-if="isImpersonating"
    :text="$t('form_logic.impersonation.stop')"
  >
    <UButton
      color="neutral"
      variant="outline"
      icon="i-heroicons-arrow-left-start-on-rectangle-20-solid"
      @click="reverseImpersonation"
    />
  </UTooltip>
</template>

<script setup>
import { useQueryClient } from '@tanstack/vue-query'
const authStore = useAuthStore()
const queryClient = useQueryClient()

const isImpersonating = computed(() => authStore.isImpersonating)

function reverseImpersonation() {
  authStore.stopImpersonating()
  queryClient.invalidateQueries()
}
</script>

