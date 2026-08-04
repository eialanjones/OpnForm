<template>
  <AdminCard
    v-if="props.user.two_factor_enabled"
    :title="$t('admin.two_factor_disable.title')"
    icon="i-heroicons-shield-exclamation-20-solid"
  >
    <div class="space-y-6 flex flex-col justify-between">
      <UAlert
        icon="i-heroicons-exclamation-triangle"
        color="error"
        variant="subtle"
      >
        <template #description>
          <div>
            {{ $t('admin.two_factor_disable.warning') }}
          </div>
        </template>
      </UAlert>

      <VForm @submit.prevent="submit">
        <TextAreaInput
          :label="$t('admin.two_factor_disable.reason_label')"
          name="reason"
          :form="form"
          :required="true"
          :help="$t('admin.two_factor_disable.reason_help')"
        />
        <div class="flex space-x-2 mt-4">
          <UButton
            block
            :loading="form.busy"
            type="submit"
            class="grow"
            :label="$t('admin.two_factor_disable.title')"
          />
        </div>
      </VForm>
    </div>
  </AdminCard>
</template>

<script setup>
const props = defineProps({
  user: { type: Object, required: true }
})
const emit = defineEmits(['user-updated'])

const { t } = useI18n()
const alert = useAlert()

const form = useForm({
  user_id: props.user.id,
  reason: ''
})

async function submit() {
  try {
    let response
    response = await form.post('/moderator/disable-two-factor-authentication')
    alert.success(response.message)
    emit('user-updated', response.user)
    form.reset()
  } catch (error) {
    alert.error(error.data?.message || t('admin.errors.generic'))
  }
}
</script> 