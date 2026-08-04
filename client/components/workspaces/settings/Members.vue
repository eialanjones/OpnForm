<template>
  <div class="space-y-4">
    <div class="flex flex-col flex-wrap items-start justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <h3 class="text-lg font-medium text-neutral-900">{{ $t('workspace.members.title') }}</h3>
        <p class="mt-1 text-sm text-neutral-500">
          {{ $t('workspace.members.description') }}
        </p>
      </div>

      <div class="flex shrink-0 items-center gap-2">
        <UButton
          v-if="!workspace.is_readonly"
          :label="$t('workspace.invite_user.submit')"
          icon="i-heroicons-user-plus-20-solid"
          :loading="isLoadingData"
          @click="showInviteUserModal = true"
        />
      </div>
    </div>

    <UTable
      class="w-full"
      :loading="isLoadingData"
      :data="combinedUsers"
      :columns="tableColumns"
      v-model:column-pinning="columnPinning"
    >
      <template #actions-cell="{ row: { original: item } }">
        <div class="space-x-2 flex justify-center">
          <template v-if="item.type == 'user'">
            <p
              v-if="item.is_current_user"
              class="text-neutral-500 text-center text-sm"
            >
              -
            </p>
            <div v-else class="flex items-center gap-1">
              <UTooltip :text="$t('workspace.members.edit_user')">
                <UButton
                  icon="i-heroicons-pencil-square"
                  color="primary"
                  variant="soft"
                  size="xs"
                  square
                  @click="editUser(item)"
                />
              </UTooltip>
              <UTooltip :text="$t('workspace.members.remove_user')">
                <UButton
                  v-if="item.type == 'user'"
                  color="error" 
                  variant="soft"
                  icon="i-heroicons-trash"
                  size="xs"
                  square
                  :loading="removeMutation.isPending.value"
                  @click="removeUserHandler(item)"
                />
              </UTooltip>
            </div>
          </template>
          <div
            v-else-if="item.type == 'invitee'"
            class="flex items-center gap-1"
          >
            <UTooltip :text="$t('workspace.members.resend_invite')">
              <UButton
                icon="i-heroicons-envelope"
                color="neutral"
                variant="soft"
                size="xs"
                :loading="resendMutation.isPending.value"
                @click="resendInviteHandler(item)"
              />
            </UTooltip>
            <UTooltip :text="$t('workspace.members.cancel_invite')">
              <UButton
                icon="i-heroicons-x-mark"
                color="error"
                variant="soft"
                size="xs"
                :loading="cancelMutation.isPending.value"
                @click="cancelInviteHandler(item)"
              />
            </UTooltip>
          </div>
        </div>
      </template>
    </UTable>

    <UModal
      v-model:open="showEditUserModal"
      @close="showEditUserModal = false"
      :title="$t('workspace.members.edit_role_title')"
    >
      <template #body>
        <form
          @submit.prevent="updateUserRole"
        >
          <div>
            <FlatSelectInput
              :form="editUserForm"
              name="role"
              :label="$t('workspace.members.new_role_for', { name: selectedUser.name })"
              :options="[
                { name: $t('workspace.roles.user'), value: 'user' },
                { name: $t('workspace.roles.admin'), value: 'admin' },
                { name: $t('workspace.roles.readonly'), value: 'readonly' },
              ]"
              option-key="value"
              display-key="name"
            />
          </div>

          <div class="flex justify-center mt-4">
            <UButton
              type="submit"
              :loading="updateMutation.isPending.value"
            >
              {{ $t('common.actions.update') }}
            </UButton>
          </div>
        </form>
      </template>
    </UModal>

    <WorkspacesSettingsInviteUser
      v-model="showInviteUserModal"
      @user-added="handleUserAdded"
    />
  </div>
</template>

<script setup>
import WorkspacesSettingsInviteUser from '~/components/workspaces/settings/InviteUser.vue'

// Composables
const { 
  users, 
  invites, 
  updateUserRole: updateUserRoleMutation, 
  removeUser: removeUserMutation,
  resendInvite: resendInviteMutation,
  cancelInvite: cancelInviteMutation
} = useWorkspaceUsers()

const alert = useAlert()
const { current: workspace, currentId: workspaceId } = useCurrentWorkspace()
const auth = useAuth()
const { t } = useI18n()

// Get current user
const { data: user } = auth.user()

// Reactive state
const showEditUserModal = ref(false)
const showInviteUserModal = ref(false)
const selectedUser = ref(null)
const editUserForm = useForm({
  role: 'user'
})

// Create all mutations during setup
const updateMutation = updateUserRoleMutation(workspaceId)
const removeMutation = removeUserMutation(workspaceId)
const resendMutation = resendInviteMutation(workspaceId)
const cancelMutation = cancelInviteMutation(workspaceId)

// Column pinning state
const columnPinning = ref({
  left: [],
  right: ['actions']
})

// Get workspace users and invites reactively
const { data: workspaceUsers, isLoading: isLoadingUsers } = users(workspaceId)
const { data: workspaceInvites, isLoading: isLoadingInvites } = invites(workspaceId)

// Combined loading state
const isLoadingData = computed(() => (isLoadingUsers?.value || isLoadingInvites?.value) ?? false)

// Transform and combine data reactively
const combinedUsers = computed(() => {
  const users = workspaceUsers?.value || []
  const invites = workspaceInvites?.value || []

  // Transform users
  const transformedUsers = users.map(d => ({
    ...d,
    id: d.id,
    is_current_user: d.id === user?.value?.id,
    name: d.name,
    email: d.email,
    status: 'accepted',
    role: d.pivot?.role,
    type: 'user'
  }))

  // Transform invites (exclude accepted ones)
  const transformedInvites = invites
    .filter(i => i.status !== 'accepted')
    .map(i => ({
      ...i,
      name: t('workspace.members.invitee'),
      email: i.email,
      status: i.status,
      type: 'invitee'
    }))

  return [...transformedUsers, ...transformedInvites]
})

// Table columns configuration
const tableColumns = computed(() => {
  const isAdmin = workspace?.value?.is_admin ?? false
  return [
    {
      id: 'name',
      accessorKey: 'name',
      header: t('common.labels.name')
    },
    {
      id: 'email',
      accessorKey: 'email',
      header: t('common.labels.email')
    },
    {
      id: 'role',
      accessorKey: 'role',
      header: t('workspace.fields.role')
    },
    ...(isAdmin ? [
      {
        id: 'actions',
        header: '',
      }] : [])
  ]
})

// User management handlers
const editUser = (user) => {
  selectedUser.value = user
  editUserForm.role = selectedUser.value.pivot?.role || selectedUser.value.role
  showEditUserModal.value = true
}

const updateUserRole = () => {
  if (!workspaceId.value || !selectedUser.value?.id) return

  updateMutation.mutateAsync({
    userId: selectedUser.value.id,
    data: { role: editUserForm.role }
  }).then((data) => {
    alert.success(data.message || t('workspace.members.role_updated'))
    showEditUserModal.value = false
  }).catch((error) => {
    alert.error(error.response?.data?.message || t('workspace.members.role_update_error'))
  })
}

const removeUserHandler = (user) => {
  if (!workspaceId.value) return

  alert.confirm(t('workspace.members.remove_confirm', { name: user.name }), () => {
    removeMutation.mutateAsync(user.id).then(() => {
      alert.success(t('workspace.members.remove_success'))
    }).catch((error) => {
      alert.error(error.response?.data?.message || t('workspace.members.remove_error'))
    })
  })
}

// Invite management handlers  
const resendInviteHandler = (invite) => {
  if (!workspaceId.value) return

  alert.confirm(t('workspace.members.resend_confirm'), () => {
    resendMutation.mutateAsync(invite.id).then(() => {
      alert.success(t('workspace.members.resend_success'))
    }).catch((error) => {
      alert.error(error.response?.data?.message || t('workspace.members.resend_error'))
    })
  })
}

const cancelInviteHandler = (invite) => {
  if (!workspaceId.value) return

  alert.confirm(t('workspace.members.cancel_confirm'), () => {
    cancelMutation.mutateAsync(invite.id).then(() => {
      alert.success(t('workspace.members.cancel_success'))
    }).catch((error) => {
      alert.error(error.response?.data?.message || t('workspace.members.cancel_error'))
    })
  })
}

// Handle user added event from invite modal
const handleUserAdded = () => {
  // TanStack Query will automatically update the cache, no manual refresh needed
  showInviteUserModal.value = false
}
</script> 