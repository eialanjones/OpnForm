<template>
  <div class="space-y-4">
    <div class="flex flex-col flex-wrap items-start justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <h3 class="text-lg font-medium text-neutral-900">{{ $t('workspace.saml.title') }}</h3>
        <p class="mt-1 text-sm text-neutral-500">
          {{ $t('workspace.saml.description') }}
        </p>
      </div>

      <UButton
        v-if="canManageConnections"
        :label="$t('workspace.sso.add_connection')"
        icon="i-heroicons-plus"
        @click="openContactChat"
      />
    </div>

    <!-- Empty State -->
    <div class="text-center py-12">
      <UIcon 
        name="i-heroicons-shield-check" 
        class="w-12 h-12 text-neutral-400 mx-auto mb-4" 
      />
      <h4 class="text-lg font-medium text-neutral-900 mb-2">
        {{ $t('workspace.saml.empty_title') }}
      </h4>
      <p class="text-neutral-500 mb-6 max-w-md mx-auto">
        {{ $t('workspace.saml.empty_description') }}
      </p>
      <UButton
        v-if="canManageConnections"
        :label="$t('workspace.sso.add_first_connection')"
        icon="i-heroicons-plus"
        @click="openContactChat"
      />
    </div>
  </div>
</template>

<script setup>
const { current: workspace } = useCurrentWorkspace()
const { openAndShowChat } = useCrisp()
const { t } = useI18n()

const canManageConnections = computed(() => !!workspace.value && workspace.value.is_admin)

const openContactChat = () => {
  const message = t('workspace.saml.contact_message')
  openAndShowChat(message)
}
</script>
