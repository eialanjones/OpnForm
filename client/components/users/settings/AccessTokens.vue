<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col flex-wrap items-start justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <h3 class="text-lg font-medium text-neutral-900">{{ $t('user_settings.access_tokens.heading') }}</h3>
        <p class="mt-1 text-sm text-neutral-500">
          {{ $t('user_settings.access_tokens.description') }}
        </p>
      </div>

      <div class="flex shrink-0 items-center gap-2">
        <UButton
          :label="$t('user_settings.access_tokens.api_docs_button')"
          icon="i-heroicons-book-open"
          variant="outline"
          color="primary"
          :to="opnformConfig.links.api_docs"
          target="_blank"
        />

        <UButton
          :label="$t('user_settings.access_tokens.create_button')"
          icon="i-heroicons-plus"
          :loading="loading"
          @click="accessTokenModal = true"
        />
      </div>
    </div>

    <!-- Tokens List -->
    <div class="space-y-4">
      <div v-if="tokens?.length === 0 && !loading" class="text-center py-12">
        <UIcon 
          name="i-heroicons-key" 
          class="w-12 h-12 text-neutral-400 mx-auto mb-4" 
        />
        <h4 class="text-lg font-medium text-neutral-900 mb-2">
          {{ $t('user_settings.access_tokens.empty_title') }}
        </h4>
        <p class="text-neutral-500 mb-4">
          {{ $t('user_settings.access_tokens.empty_description') }}
        </p>
        <UButton
          :label="$t('user_settings.access_tokens.create_first_button')"
          icon="i-heroicons-plus"
          @click="accessTokenModal = true"
        />
      </div>

      <UTable 
        v-if="tokens?.length > 0"
        v-model:column-pinning="columnPinning"
        :data="tokens" 
        :columns="tableColumns"
        :loading="loading"
        class="w-full"
      >
        <template #name-cell="{ row: { original: item } }">
          <span class="font-semibold">{{ item.name }}</span>
        </template>

        <template #abilities-cell="{ row: { original: item } }">
          <AbilitiesBadges :abilities="item.abilities" />
        </template>

        <template #actions-cell="{ row: { original: item } }">
          <div class="flex justify-end">
            <UButton 
              color="error" 
              variant="soft"
              icon="i-heroicons-trash"
              square
              size="sm"
              @click="deleteToken(item)"
            />
          </div>
        </template>
      </UTable>
    </div>

    <!-- API Information -->
    <div class="space-y-4 pt-8 border-t border-neutral-200">
      <div>
        <h3 class="text-lg font-medium text-neutral-900">{{ $t('user_settings.access_tokens.api_info.heading') }}</h3>
        <p class="text-sm text-neutral-500 mt-1">
          {{ $t('user_settings.access_tokens.api_info.description') }}
        </p>
      </div>

      <div class="bg-neutral-50 border border-neutral-200 rounded-lg p-4">
        <div class="space-y-3">
          <div class="flex items-start gap-3">
            <UIcon name="i-heroicons-information-circle" class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" />
            <div>
              <h4 class="text-sm font-medium text-neutral-900">{{ $t('user_settings.access_tokens.api_info.getting_started_title') }}</h4>
              <p class="text-sm text-neutral-600 mt-1">
                {{ $t('user_settings.access_tokens.api_info.getting_started_description') }} <code class="bg-neutral-200 px-1 rounded text-xs">Bearer YOUR_TOKEN</code>
              </p>
            </div>
          </div>
          
          <div class="flex items-start gap-3">
            <UIcon name="i-heroicons-shield-check" class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" />
            <div>
              <h4 class="text-sm font-medium text-neutral-900">{{ $t('user_settings.access_tokens.api_info.security_title') }}</h4>
              <p class="text-sm text-neutral-600 mt-1">
                {{ $t('user_settings.access_tokens.api_info.security_description') }}
              </p>
            </div>
          </div>
          
          <div class="flex items-start gap-3">
            <UIcon name="i-heroicons-clock" class="w-5 h-5 text-yellow-500 mt-0.5 flex-shrink-0" />
            <div>
              <h4 class="text-sm font-medium text-neutral-900">{{ $t('user_settings.access_tokens.api_info.rate_limits_title') }}</h4>
              <i18n-t
                keypath="user_settings.access_tokens.api_info.rate_limits_description"
                scope="global"
                tag="p"
                class="text-sm text-neutral-600 mt-1"
              >
                <template #link>
                  <a href="https://docs.opnform.com/api-reference/introduction#rate-limits" target="_blank" class="text-blue-500 hover:underline">{{ $t('user_settings.access_tokens.api_info.rate_limits_link') }}</a>
                </template>
              </i18n-t>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Access Token Modal -->
    <UsersSettingsAccessTokenModal
      v-model="accessTokenModal"
      @close="accessTokenModal = false"
    />
  </div>
</template>

<script setup>
import opnformConfig from '~/opnform.config.js'
import AbilitiesBadges from '~/components/users/settings/access-tokens/AbilitiesBadges.vue'

const accessTokenModal = ref(false)
const alert = useAlert()
const { t } = useI18n()

// Use TanStack Query instead of Pinia store
const { list, remove: removeToken } = useTokens()

// Fetch tokens
const { data: tokens, isLoading: loading } = list({})

// Delete token mutation
const deleteTokenMutation = removeToken()

// Column pinning state
const columnPinning = ref({
  left: [],
  right: ['actions']
})

// Table columns configuration
const tableColumns = [
  {
    id: 'name',
    accessorKey: 'name',
    header: t('common.labels.name'),
    enableSorting: true
  },
  {
    id: 'abilities',
    accessorKey: 'abilities',
    header: t('user_settings.access_tokens.abilities'),
    enableSorting: false
  },
  {
    id: 'actions',
    header: '',
    enableSorting: false,
    enableHiding: false
  }
]

const deleteToken = (token) => {
  alert.confirm(t('user_settings.access_tokens.delete_confirm'), () => {
    deleteTokenMutation.mutateAsync(token.id).then(() => {
      alert.success(t('user_settings.access_tokens.delete_success'))
    }).catch(() => {
      alert.error(t('user_settings.access_tokens.delete_error'))
    })
  })
}
</script> 