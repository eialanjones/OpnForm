<template>
  <form
    v-if="isWorkspaceAdmin"
    class="my-2"
    @submit.prevent="addUser"
  >
    <text-input
      v-model="newUser"
      name="email"
      :label="$t('common.labels.email')"
      :required="true"
      :disabled="disabled"
      :placeholder="$t('admin.add_user_to_workspace.email_placeholder')"
    />
    <select-input
      v-model="newUserRole"
      name="newUserRole"
      :options="roleOptions"
      :disabled="disabled"
      :placeholder="$t('admin.add_user_to_workspace.role_placeholder')"
      :label="$t('admin.add_user_to_workspace.role_label')"
      :required="true"
    />
    <div class="flex justify-center mt-2">
      <UButton
        type="submit"
        :disabled="disabled"
        :loading="addMutation.isPending.value"
        icon="i-heroicons-envelope"
      >
        {{ $t('admin.add_user_to_workspace.invite_user') }}
      </UButton>
    </div>
  </form>
</template>

<script setup>
defineProps({
  isWorkspaceAdmin: { type: Boolean, default: false },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const { t } = useI18n()
const { currentId } = useCurrentWorkspace()
const { addUser: addUserMutation } = useWorkspaceUsers()

const roleOptions = computed(() => [
  {name: t("admin.roles.user"), value: "user"},
  {name: t("admin.roles.admin"), value: "admin"},
  {name: t("admin.roles.readonly"), value: "readonly"}
])

const newUser = ref("")
const newUserRole = ref("user")

const addMutation = addUserMutation(currentId)

const addUser = () => {
  if (!newUser.value) return
  
  addMutation.mutateAsync({
    email: newUser.value,
    role: newUserRole.value,
  }).then((data) => {
    newUser.value = ""
    newUserRole.value = "user"
    useAlert().success(data.message)
    // No need to emit 'fetchUsers' - the mutation handles cache updates automatically
  }).catch((error) => {
    useAlert().error(t("admin.add_user_to_workspace.add_error", { message: error.data.message }))
  })
}
</script>
