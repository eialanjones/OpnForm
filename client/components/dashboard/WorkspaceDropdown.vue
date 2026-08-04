<template>
  <UPopover
    v-model:open="isDropdownOpen"
    v-if="user && workspaces && workspaces.length >= 1"
    :content="content"
    arrow
    v-bind="$attrs"
  >
    <slot :workspace="workspace" />
    
    <template #content>
      <div class="w-60 flex flex-col">
        <!-- Workspace Info Header -->
        <div v-if="workspace" class="p-3 border-b border-neutral-200">
          <div class="flex items-center gap-3">
            <WorkspaceIcon size="size-8" :workspace="workspace" />
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-neutral-800 truncate">
                {{ workspace.name }}
              </p>
              <p class="text-xs text-neutral-500">
                {{ workspacePlanText }} • {{ memberCountText }}
              </p>
            </div>
          </div>
          <div class="mt-2 flex items-center gap-2">
            <UButton
              icon="i-heroicons-cog-6-tooth"
              size="xs"
              color="neutral"
              variant="outline"
              @click="openSettings"
              :label="$t('common.actions.settings')"
            />
            <UButton
              v-if="workspace.is_admin"
              icon="i-heroicons-user-plus"
              size="xs"
              color="neutral"
              variant="outline"
              @click="openInviteUserModal"
              :label="$t('app_shell.workspace_dropdown.invite_members')"
            />
          </div>
        </div>

        <!-- Workspace List (with ScrollableContainer) -->
        <div v-if="workspaces.length > 1" class="p-1">
          <ScrollableContainer max-height-class="max-h-64" top-fade-height="h-10" bottom-fade-height="h-10">
            <div class="flex flex-col">
              <UButton
                v-for="worksp in workspaces"
                :key="worksp.id"
                class="w-full flex items-center gap-3 p-2 rounded-md text-left"
                color="neutral"
                variant="ghost"
                @click="switchWorkspace(worksp)"
                :label="worksp.name"
                size="sm"
                :trailing-icon="workspace?.id === worksp?.id ? 'i-heroicons-check' : undefined"
              >
                <template #leading>
                  <WorkspaceIcon :workspace="worksp" size="size-5" />
                </template>
              </UButton>
            </div>
          </ScrollableContainer>
        </div>

        <!-- Create Workspace Action -->
        <div class="border-t border-neutral-200 p-1">
          <UButton
            class="w-full flex items-center gap-3 p-2 rounded-md hover:bg-neutral-100 transition-colors text-left"
            icon="i-heroicons-plus"
            color="neutral"
            variant="ghost"
            @click="createNewWorkspace"
            :label="$t('app_shell.workspace_dropdown.create_workspace')"
            size="sm"
          />
        </div>
      </div>
    </template>
  </UPopover>

  <!-- Create Workspace Modal -->
  <CreateWorkspaceModal
    v-model="showCreateModal"
    @created="onWorkspaceCreated"
    @close="showCreateModal = false"
  />

  <WorkspacesSettingsInviteUser
    v-model="showInviteUserModal"
    @user-added="onUserAdded"
  />
</template>

<script setup>
import { computed, ref } from "vue"
import WorkspaceIcon from "~/components/workspaces/WorkspaceIcon.vue"
import CreateWorkspaceModal from "~/components/workspaces/CreateWorkspaceModal.vue"
import WorkspacesSettingsInviteUser from '~/components/workspaces/settings/InviteUser.vue'
import ScrollableContainer from '~/components/dashboard/ScrollableContainer.vue'


defineProps({
  content: {
    type: Object,
    default: () => ({
      side: 'bottom',
      align: 'start'
    })
  }
})

const { t } = useI18n()
const { openSubscriptionModal } = useAppModals()
const router = useRouter()
const route = useRoute()
const appStore = useAppStore()

const { data: user } = useAuth().user()
const { data: workspaces } = useWorkspaces().list()
const { current: workspace } = useCurrentWorkspace()

// Extract composable methods in setup context
const { invalidateAll: invalidateForms } = useForms()
const { invalidateAll: invalidateWorkspaces } = useWorkspaces()

// Modal state
const showCreateModal = ref(false)
const showInviteUserModal = ref(false)

// Dropdown state
const isDropdownOpen = ref(false)

// Computed text for workspace plan
const workspacePlanText = computed(() => {
  if (!workspace.value) return ''
  return workspace.value.is_pro ? t('app_shell.workspace_dropdown.pro_plan') : t('app_shell.workspace_dropdown.free_plan')
})

// Computed text for member count
const memberCountText = computed(() => {
  if (!workspace.value || !workspace.value.users_count) return t('app_shell.workspace_dropdown.member_count_one')
  const count = workspace.value.users_count
  return count === 1 ? t('app_shell.workspace_dropdown.member_count_one') : t('app_shell.workspace_dropdown.member_count_other', { count })
})

const switchWorkspace = (workspaceToSwitch) => {
  if (workspaceToSwitch.id === workspace.value.id) {
    return
  }
  appStore.setCurrentId(workspaceToSwitch.id)
  invalidateForms()
  
  if (route.name !== "home") {
    router.push({ name: "home" })
  }
}

const createNewWorkspace = () => {
  if (!user.value.is_pro && workspaces.value.length >= 1) {
    openSubscriptionModal({ modal_title: t('app_shell.workspace_dropdown.upgrade_workspaces_title'), modal_description: t('app_shell.workspace_dropdown.upgrade_workspaces_description') })
    return
  }
  showCreateModal.value = true
}

const onWorkspaceCreated = (_newWorkspace) => {
  // Member count is now included in workspace data automatically
}

const onUserAdded = () => {
  invalidateWorkspaces()
}

const openSettings = () => {
  isDropdownOpen.value = false
  useAppModals().openWorkspaceSettings('information')
}

const openInviteUserModal = () => {
  isDropdownOpen.value = false

  if (workspace.value && !workspace.value.is_pro) {
    openSubscriptionModal({ modal_title: t('app_shell.workspace_dropdown.upgrade_invite_title') })
    return
  }
  
  showInviteUserModal.value = true
}


</script>

<style></style>
