<template>
  <div class="space-y-4">
    <div class="flex flex-col flex-wrap items-start justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <h3 class="text-lg font-medium text-neutral-900">{{ $t('workspace.information.title') }}</h3>
        <p class="mt-1 text-sm text-neutral-500">
          {{ $t('workspace.information.description') }}
        </p>
      </div>
    </div>

    <VForm @submit.prevent="updateProfile" size="sm">
   
        <div class="max-w-sm">
          <TextInput
            :disabled="workspace.is_readonly"
            :form="workspaceForm"
            name="name"
            :label="$t('workspace.fields.workspace_name')"
            :placeholder="$t('workspace.fields.workspace_name_placeholder')"
            :required="true"
          />
          <TextInput
            :disabled="workspace.is_readonly"
            :form="workspaceForm"
            name="emoji"
            :label="$t('workspace.fields.emoji')"
            :placeholder="$t('workspace.fields.emoji_placeholder')"
            :help="$t('workspace.fields.emoji_help')"
          />
        </div>

        <div class="mt-4">
          <UButton
            :disabled="workspace.is_readonly"
            type="submit"
            :loading="workspaceForm.busy"
            color="primary"
          >
            {{ $t('workspace.actions.save_changes') }}
          </UButton>
        </div>
    </VForm>

    <div class="pt-8 border-t border-neutral-200">
      <div 
        v-if="workspace.is_admin" 
        class="space-y-2"
      >
        <h4 class="text-red-800 font-medium">{{ $t('workspace.information.delete_title') }}</h4>
        <p class="text-neutral-500 text-sm">
          {{ $t('workspace.information.delete_description') }}
        </p>
        <UButton
          color="error"
          :loading="removeMutation.isPending.value"
          @click="confirmDeleteWorkspace"
        >
          {{ $t('workspace.information.delete_button') }}
        </UButton>
      </div>

      <div 
        v-else
        class="space-y-2"
      >
        <h4 class="text-neutral-900 font-medium">{{ $t('workspace.information.leave_title') }}</h4>
        <p class="text-neutral-500 text-sm">
          {{ $t('workspace.information.leave_description') }}
        </p>
        <UButton
          color="error"
          :loading="leaveMutation.isPending.value"
          @click="leaveWorkSpace"
        >
          {{ $t('workspace.information.leave_button') }}
        </UButton>
      </div>
    </div>
  </div>
</template>

<script setup>
const { update, remove, leave } = useWorkspaces()

const alert = useAlert()
const { closeWorkspaceSettings } = useAppModals()
const router = useRouter()
const { t } = useI18n()

const { current: workspace } = useCurrentWorkspace()

const updateMutation = update(workspace.value.id)
const removeMutation = remove()
const leaveMutation = leave()

// Workspace form
const workspaceForm = useForm({
  name: '',
  emoji: ''
})

// Update profile
const updateProfile = () => {
  workspaceForm.mutate(updateMutation).then(() => {
    useAlert().success(t('workspace.information.updated'))
  }).catch((error) => {
      console.error('Error updating workspace:', error)
  })
}

// Delete workspace confirmation
const confirmDeleteWorkspace = () => {
  alert.confirm(
    t('workspace.information.delete_confirm'),
    deleteWorkspace
  )
}

// Delete workspace
const deleteWorkspace = () => {
  removeMutation.mutateAsync(workspace.value.id).then((data) => {
      alert.success(data.message)
      closeWorkspaceSettings()
      nextTick(() => {
        router.push({ name: "home", query: {} })
      })
  }).catch((error) => {
      alert.error(error.data?.message || t('workspace.information.delete_error'))
    })
}

// Leave workspace
const leaveWorkSpace = () => {
  alert.confirm(
    t('workspace.information.leave_confirm'),
    () => {
      leaveMutation.mutateAsync(workspace.value.id).then(() => {
        alert.success(t('workspace.information.leave_success'))
        closeWorkspaceSettings()
        nextTick(() => {
          router.push({ name: "home", query: {} })
        })
      }).catch((error) => {
        console.error('Error leaving workspace:', error)
        alert.error(t('workspace.information.leave_error'))
      })
    },
  )
}


// Watch for user changes
watch(workspace, (newWorkspace) => {
  if (newWorkspace) {
    workspaceForm.fill({
      name: newWorkspace.name || '',
      emoji: newWorkspace.icon || ''
    })
  }
}, { immediate: true })
</script> 