<template>
  <UModal
    v-model:open="showEditUserModal"
    :ui="{ width: 'lg:max-w-lg' }"
    @close="$emit('close')"
    :title="$t('admin.edit_workspace_user.title')"
  >
    <template #body>
      <UCard>
        <div class="px-4">
          <form
            @submit.prevent="updateUserRole"
          >
            <div>
              <FlatSelectInput
                v-model="userNewRole"
                name="newUserRole"
                :label="$t('admin.edit_workspace_user.new_role_for', { name: props.user.name })"
                :options="[
                  { name: $t('admin.roles.user'), value: 'user' },
                  { name: $t('admin.roles.admin'), value: 'admin' },
                  { name: $t('admin.roles.readonly'), value: 'readonly' },
                ]"
                option-key="value"
                display-key="name"
              />
            </div>

            <div class="w-full mt-6">
              <UButton
                :loading="updateMutation.isPending.value"
                class="my-3"
                block
                :label="$t('common.actions.update')"
              />
            </div>
          </form>
        </div>
      </UCard>
    </template>
  </UModal>
</template>

<script setup> 
const props = defineProps(['user', 'showEditUserModal'])
const emit = defineEmits(['close', 'fetchUsers'])

const { t } = useI18n()
const { currentId } = useCurrentWorkspace()
const { updateUserRole: updateUserRoleMutation } = useWorkspaceUsers()

const userNewRole = ref("")

const updateMutation = updateUserRoleMutation(currentId)

watch(() => props.user, () => {
  userNewRole.value = props.user.pivot.role
})

const updateUserRole = () => {
  updateMutation.mutateAsync({
    userId: props.user.id,
    data: { role: userNewRole.value }
  }).then(() => {
    useAlert().success(t("admin.edit_workspace_user.updated"))
    emit('close')
    // No need to emit 'fetchUsers' - the mutation handles cache updates automatically
  }).catch(() => {
    useAlert().error(t("admin.edit_workspace_user.update_error"))
  })
}
</script>
