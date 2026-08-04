<template>
  <UButton
    variant="outline"
    icon="i-heroicons-key-16-solid"
    :loading="form.busy"
    @click="resetPassword"
    :label="$t('admin.password_reset.label')"
  />
</template>

<script setup>
const props = defineProps({
    user: { type: Object, required: true }
})

const { t } = useI18n()

const form = useForm({
  user_id: props.user.id
})

const resetPassword = () => {
    return useAlert().confirm(
        t("admin.password_reset.confirm"),
        () => {
            form
                .patch('/moderator/send-password-reset-email')
                .then(async (data) => {
                    useAlert().success(data.message)
                })
                .catch((error) => {
                    useAlert().error(error.data?.message || t('admin.password_reset.error'))
                })
        })
}
</script>