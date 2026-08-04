<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col flex-wrap items-start justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <h3 class="text-lg font-medium text-neutral-900">{{ $t('user_settings.connections.heading') }}</h3>
        <p class="mt-1 text-sm text-neutral-500">
          {{ $t('user_settings.connections.description') }}
        </p>
      </div>
      <div class="flex items-center gap-2">
        <UButton
          icon="i-heroicons-arrow-path"
          color="neutral"
          variant="soft"
          square
          size="md"
          :loading="isFetching"
          @click="refreshProviders"
        />
        <UButton
          :label="$t('user_settings.connections.connect_button')"
          icon="i-heroicons-plus"
          :loading="isFetching"
          @click="providerModal = true"
        />
      </div>
    </div>

    <!-- Providers List -->
    <div class="space-y-4">
      <div v-if="providers.length === 0 && !isFetching" class="text-center py-12">
        <UIcon 
          name="i-heroicons-link" 
          class="w-12 h-12 text-neutral-400 mx-auto mb-4" 
        />
        <h4 class="text-lg font-medium text-neutral-900 mb-2">
          {{ $t('user_settings.connections.empty_title') }}
        </h4>
        <p class="text-neutral-500 mb-4">
          {{ $t('user_settings.connections.empty_description') }}
        </p>
        <UButton
          :label="$t('user_settings.connections.connect_first_button')"
          icon="i-heroicons-plus"
          @click="providerModal = true"
        />
      </div>

      <UTable 
        v-if="providers.length > 0"
        v-model:column-pinning="columnPinning"
        :data="providers" 
        :columns="tableColumns"
        :loading="isFetching"
        class="w-full"
      >
        <template #provider-cell="{ row: { original: item } }">
          <div class="flex items-center gap-3">
            <Icon
              :name="getService(item.provider)?.icon"
              size="24px"
              class="text-blue-500"
            />
            <span class="font-semibold">{{ getService(item.provider)?.name || item.provider }}</span>
          </div>
        </template>

        <template #email-cell="{ row: { original: item } }">
          <div class="flex flex-col items-start" v-if="item.name || item.email">
            <span class="text-neutral-600" v-if="item.name">{{ item.name }}</span>
            <span class="text-neutral-400 text-xs" v-if="item.email">{{ item.email }}</span>
          </div>
          <span v-else class="text-neutral-400">
            -
          </span>
        </template>

        <template #actions-cell="{ row: { original: item } }">
          <div class="flex justify-end">
            <UButton 
              color="error" 
              variant="soft"
              icon="i-heroicons-trash"
              square
              size="sm"
              @click="disconnectProvider(item)"
            />
          </div>
        </template>
      </UTable>
    </div>

    <!-- Provider Modal -->
    <UsersSettingsConnectionModal
      v-model="providerModal"
      @close="providerModal = false"
    />
  </div>
</template>

<script setup>
const providerModal = ref(false)
const oAuth = useOAuth()
const alert = useAlert()
const { t } = useI18n()

const { data: providersData, refetch, isFetching } = oAuth.providers()
const providers = computed(() => providersData.value || [])

// Column pinning state
const columnPinning = ref({
  left: [],
  right: ['actions']
})

// Table columns configuration
const tableColumns = [
  {
    id: 'provider',
    accessorKey: 'provider',
    header: t('user_settings.connections.table_service'),
    enableSorting: true
  },
  {
    id: 'email',
    accessorKey: 'email',
    header: t('user_settings.connections.table_account'),
    enableSorting: true
  },
  {
    id: 'actions',
    header: '',
    enableSorting: false,
    enableHiding: false
  }
]

// Get service information
const getService = (providerName) => {
  return oAuth.getService(providerName)
}

// Disconnect provider mutation
const removeMutation = oAuth.remove()

// Disconnect provider
const disconnectProvider = (provider) => {
  alert.confirm(t('user_settings.connections.disconnect_confirm'), () => {
    removeMutation.mutateAsync(provider.id).then(() => {
      alert.success(t('user_settings.connections.disconnect_success'))
      refetch()
    }).catch((error) => {
      try {
        alert.error(error.data.message)
      } catch {
        alert.error(t('user_settings.connections.disconnect_error'))
      }
    })
  })
}

// Refresh providers
const refreshProviders = async () => {
  await refetch()
}

// Fetch providers on mount
await oAuth.fetchOAuthProviders()
</script> 