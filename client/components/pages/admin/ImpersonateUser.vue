<template>
  <UButton
    icon="i-heroicons-eye-16-solid"
    :loading="loading"
    @click="impersonate"
    :label="$t('admin.impersonate.label')"
  />
</template>

<script setup>

import { useQueryClient } from '@tanstack/vue-query'

const props = defineProps({
  user: { type: Object, required: true }
})

const { t } = useI18n()
const authStore = useAuthStore()
const queryClient = useQueryClient()
const loading = ref(false)

const { user } = useAuth()
const { data: userData } = user()

const impersonate = () => {
  loading.value = true
  authStore.startImpersonating()
  opnFetch(`/moderator/impersonate/${props.user.id}`).then(async (data) => {
    loading.value = false

    // Save the token with its expiration time.
    authStore.setToken(data.token, data.expires_in)
    await queryClient.invalidateQueries()

    useAlert().success(t('admin.impersonate.success', { name: userData.value.name }))
    useRouter().push({ name: 'home' })
  })
    .catch((error) => {
      useAlert().error(error.data?.message || t('admin.impersonate.error'))
      loading.value = false
    })
}
</script>
