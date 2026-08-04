<template>
  <UButton
    color="gray"
    variant="outline"
    :loading="loading"
    :label="$t('admin.clear_cache.label')"
    icon="i-heroicons-arrow-path-20-solid"
    @click="clearUserCache"
  />
</template>

<script setup>
import { adminApi } from '~/api'

const props = defineProps({
  user: { type: Object, required: true }
})
const emit = defineEmits(['cache-cleared'])

const { t } = useI18n()
const alert = useAlert()
const loading = ref(false)

async function clearUserCache() {
  if (!props.user?.id) return
  loading.value = true
  try {
    const response = await adminApi.clearUserCache({ user_id: props.user.id })
    alert.success(response.message || t('admin.clear_cache.success'))
    emit('cache-cleared')
  } catch (error) {
    alert.error(error.data?.message || t('admin.errors.generic'))
  } finally {
    loading.value = false
  }
}
</script>
