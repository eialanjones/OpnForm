<template>
  <div class="space-y-4">

    <UAlert
      :icon="alertConfig.icon"
      :color="alertConfig.color"
      variant="subtle"
      :title="alertConfig.title"
      :description="alertConfig.description"
      :actions="alertConfig.actions"
    />

    <div class="flex flex-col flex-wrap items-start justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <h3 class="text-lg font-medium text-neutral-900">{{ $t('workspace.oidc.title') }}</h3>
        <p class="mt-1 text-sm text-neutral-500">
          {{ $t('workspace.oidc.description') }}
        </p>
      </div>

      <UButton
        v-if="canManageConnections && canAccessFeature"
        :label="$t('workspace.sso.add_connection')"
        icon="i-heroicons-plus"
        @click="showCreateModal = true"
      />
      <UButton
        v-else-if="canManageConnections && !canAccessFeature"
        :label="$t('workspace.sso.add_connection')"
        icon="i-heroicons-plus"
        @click="openUpgradeModal"
      />
    </div>

    <!-- Connections List -->
    <div v-if="connectionsData && connectionsData.length > 0" class="space-y-3">
      <p class="text-sm text-neutral-500 max-w-xl">
        {{ $t('workspace.oidc.connections_description') }}
      </p>
      <div class="grid gap-3 sm:grid-cols-2">
        <OidcConnectionCard
        v-for="connection in connectionsData"
        :key="connection.id"
          :connection="connection"
          :can-edit="canManageConnections && canAccessFeature"
          @edit="editConnection"
          @delete="deleteConnection"
          />
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!isConnectionsLoading" class="text-center py-12">
      <UIcon 
        name="i-heroicons-key" 
        class="w-12 h-12 text-neutral-400 mx-auto mb-4" 
      />
      <h4 class="text-lg font-medium text-neutral-900 mb-2">
        {{ $t('workspace.oidc.empty_title') }}
      </h4>
      <p class="text-neutral-500 mb-4">
        {{ $t('workspace.oidc.empty_description') }}
      </p>
      <UButton
        v-if="canManageConnections && canAccessFeature"
        :label="$t('workspace.sso.add_first_connection')"
        icon="i-heroicons-plus"
        @click="showCreateModal = true"
      />
      <UButton
        v-else-if="canManageConnections && !canAccessFeature"
        :label="$t('workspace.sso.add_first_connection')"
        icon="i-heroicons-plus"
        @click="openUpgradeModal"
      />
    </div>

    <!-- Create/Edit Modal -->
    <OidcConnectionModal
      :model-value="showCreateModal"
      :connection="editingConnection"
            :form="connectionForm"
      :is-busy="connectionForm.busy"
      @update:model-value="showCreateModal = $event"
      @save="saveConnection"
      @cancel="cancelEdit"
    />
  </div>
</template>

<script setup>
import { useOidcConnections } from '~/composables/query/useOidcConnections'
import { getOidcRequireStateDefault, getOidcRequireStateForEdit } from "~/lib/oidc/connection-options"
import OidcConnectionCard from './OidcConnectionCard.vue'
import OidcConnectionModal from './OidcConnectionModal.vue'

const { current: workspace } = useCurrentWorkspace()
const alert = useAlert()
const { openSubscriptionModal } = useAppModals()
const { t } = useI18n()

const workspaceId = computed(() => workspace.value?.id)

const canManageConnections = computed(() => !!workspace.value && workspace.value.is_admin)

// Check if feature is accessible (Pro required for cloud, free for self-hosted)
const isSelfHosted = computed(() => useFeatureFlag('self_hosted'))
const billingEnabled = computed(() => useFeatureFlag('billing.enabled'))
const canAccessFeature = computed(() => {
  // Self-hosted: always accessible
  if (isSelfHosted.value) {
    return true
  }
  // Cloud: requires Pro subscription
  return billingEnabled.value && workspace.value?.is_pro
})

const { connections, create, update, remove } = useOidcConnections(workspaceId)

// Allow viewing connections even without Pro (Pro only required for create/update/delete)
const { data: connectionsData, isLoading: isConnectionsLoading } = connections()

const alertConfig = computed(() => {
  // Cloud + Free: Beta alert with upgrade button
  if (!isSelfHosted.value && !canAccessFeature.value) {
    return {
      icon: 'i-heroicons-information-circle',
      color: 'info',
      title: t('workspace.oidc.beta_pro_title'),
      description: t('workspace.oidc.beta_pro_description'),
      actions: [
        {
          label: t('workspace.pro.upgrade_to_pro'),
          onClick: openUpgradeModal
        }
      ]
    }
  }

  // Cloud + Paid: Warning about upcoming Enterprise plan
  if (!isSelfHosted.value && canAccessFeature.value) {
    return {
      icon: 'i-heroicons-exclamation-triangle',
      color: 'warning',
      title: t('workspace.oidc.beta_pricing_title'),
      description: t('workspace.oidc.beta_pricing_description'),
      actions: []
    }
  }

  // Self-hosted: beta warning
  return {
    icon: 'i-heroicons-exclamation-triangle',
    color: 'warning',
    title: t('workspace.oidc.beta_title'),
    description: t('workspace.oidc.beta_description'),
    actions: []
  }
})

const openUpgradeModal = () => {
  openSubscriptionModal({
    modal_title: t('workspace.oidc.upgrade_modal_title'),
    modal_description: t('workspace.oidc.upgrade_modal_description')
  })
}

const showCreateModal = ref(false)
const editingConnection = ref(null)

const connectionForm = useForm({
  name: '',
  slug: '',
  issuer: '',
  client_id: '',
  client_secret: '',
  domain: '',
  enabled: true,
  options: {
    require_state: getOidcRequireStateDefault(),
    field_mappings: {
      email: '',
      name: ''
    },
    group_role_mappings: []
  }
})

// Create mutations following useWorkspaces.js pattern
const createMutation = create()
const deleteMutation = remove()

const saveConnection = () => {
  if (editingConnection.value) {
    // Update existing connection
    const updateMutation = update(editingConnection.value.id)
    connectionForm.mutate(updateMutation)
      .then(() => {
        alert.success(t('workspace.oidc.updated'))
        showCreateModal.value = false
        cancelEdit()
      })
      .catch((error) => {
        // Form handles validation errors automatically
        if (error.response?.status !== 422) {
          alert.error(error.response?._data?.message ?? t('workspace.oidc.update_error'))
        }
      })
  } else {
    // Create new connection
    connectionForm.mutate(createMutation)
      .then(() => {
        alert.success(t('workspace.oidc.created'))
        showCreateModal.value = false
        connectionForm.reset()
      })
      .catch((error) => {
        // Form handles validation errors automatically
        if (error.response?.status !== 422) {
          alert.error(error.response?._data?.message ?? t('workspace.oidc.create_error'))
        }
      })
  }
}

const editConnection = (connection) => {
  editingConnection.value = connection
  connectionForm.resetAndFill({
    name: connection.name,
    slug: connection.slug,
    issuer: connection.issuer,
    client_id: connection.client_id,
    client_secret: '', // Don't pre-fill secret
    enabled: connection.enabled,
    domain: connection.domain ?? '',
    options: {
      require_state: getOidcRequireStateForEdit(connection.options),
      field_mappings: {
        email: connection.options?.field_mappings?.email ?? '',
        name: connection.options?.field_mappings?.name ?? ''
      },
      group_role_mappings: connection.options?.group_role_mappings ?? []
    }
  })
  showCreateModal.value = true
}

const deleteConnection = (connection) => {
  alert.confirm(
    t('workspace.oidc.delete_confirm', { name: connection.name }),
    () => {
      deleteMutation.mutateAsync(connection.id)
        .then(() => {
          alert.success(t('workspace.oidc.deleted'))
        })
        .catch((error) => {
          alert.error(error.response?._data?.message ?? t('workspace.oidc.delete_error'))
        })
    }
  )
}

const cancelEdit = () => {
  editingConnection.value = null
  connectionForm.reset()
  showCreateModal.value = false
}
</script>

