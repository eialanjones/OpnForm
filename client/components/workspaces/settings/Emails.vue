<template>
  <div class="space-y-4">
    <div class="flex flex-col flex-wrap items-start justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <h3 class="text-lg font-medium text-neutral-900">{{ $t('workspace.emails.title') }}</h3>
        <p class="mt-1 text-sm text-neutral-500">
          {{ $t('workspace.emails.description') }}
        </p>
      </div>

      <UButton
        :label="$t('workspace.actions.help')"
        icon="i-heroicons-question-mark-circle"
        variant="outline"
        color="neutral"
        @click="crisp.openHelpdeskArticle('how-to-send-emails-using-your-own-domain-name-and-email-address-13kkcif')"
      />
    </div>

    <UAlert
      v-if="!workspace.is_pro"
      icon="i-heroicons-user-group-20-solid"
      class="mb-4"
      color="warning"
      variant="subtle"
      :title="$t('workspace.pro.required_title')"
      :description="$t('workspace.emails.pro_required_description')"
      :actions="[{
        label: $t('workspace.pro.try_pro'),
        color: 'warning',
        variant: 'solid',
        onClick: () => openSubscriptionModal()
      }]"
    />

    <VForm size="sm">
      <form
        @submit.prevent="saveChanges"
      >
        <div class="max-w-sm">
          <TextInput
            :form="emailSettingsForm"
            name="host"
            :required="true"
            :disabled="!workspace.is_pro"
            :label="$t('workspace.emails.host')"
            class="mt-2"
            placeholder="smtp.example.com"
          />
          <TextInput
            :form="emailSettingsForm"
            name="port"
            :required="true"
            :disabled="!workspace.is_pro"
            :label="$t('workspace.emails.port')"
            placeholder="587"
          />
          <OptionSelectorInput
            :form="emailSettingsForm"
            name="encryption"
            :disabled="!workspace.is_pro"
            :label="$t('workspace.emails.encryption')"
            :options="encryptionOptions"
            :columns="3"
            seamless
          />
          <TextInput
            :form="emailSettingsForm"
            name="username"
            :required="true"
            :disabled="!workspace.is_pro"
            :label="$t('workspace.emails.username')"
            :placeholder="$t('workspace.emails.username')"
          />
          <TextInput
            :form="emailSettingsForm"
            name="password"
            native-type="password"
            :required="true"
            :disabled="!workspace.is_pro"
            :label="$t('common.labels.password')"
            :placeholder="$t('common.labels.password')"
          />
          <TextInput
            :form="emailSettingsForm"
            name="sender_address"
            :disabled="!workspace.is_pro"
            :label="$t('workspace.emails.sender_address')"
            placeholder="sender@example.com"
          />
        </div>

        <div class="mt-4 flex items-center justify-between w-full max-w-sm flex-wrap gap-2">
          <UButton
            type="submit"
            :loading="emailSettingsForm.busy"
            :disabled="!workspace.is_pro"
          >
            {{ $t('workspace.emails.save_button') }}
          </UButton>
          <UButton
            color="neutral"
            variant="outline"
            :loading="emailSettingsForm.busy"
            :disabled="!workspace.is_pro"
            @click="clearEmailSettings"
          >
            {{ $t('workspace.emails.clear_button') }}
          </UButton>
        </div>
      </form>
    </VForm>
  </div>
</template>

<script setup>
const alert = useAlert()

const { current: workspace } = useCurrentWorkspace()

const { openSubscriptionModal: openModal } = useAppModals()
const crisp = useCrisp()
const { t } = useI18n()

const openSubscriptionModal = () => {
  openModal({ modal_title: t('workspace.emails.subscription_modal_title') })
}

const encryptionOptions = computed(() => [
  { name: 'tls', label: t('workspace.emails.encryption_tls') },
  { name: 'ssl', label: t('workspace.emails.encryption_ssl') },
  { name: 'none', label: t('common.labels.none') }
])

const emailSettingsForm = useForm({
  host: '',
  port: '',
  encryption: 'tls',
  username: '',
  password: '',
  sender_address: ''
})

onMounted(() => {
  initEmailSettings()
})

watch(
  () => workspace,
  () => {
    initEmailSettings()
  },
)

const clearEmailSettings = () => {
  emailSettingsForm.reset()
  saveChanges()
}

const saveChanges = () => {
  // Update the workspace Email Settings
  emailSettingsForm
    .put("/open/workspaces/" + workspace.value.id + "/email-settings", {
      data: {
        host: emailSettingsForm?.host,
        port: emailSettingsForm?.port,
        encryption: emailSettingsForm?.encryption === 'none' ? null : emailSettingsForm?.encryption,
        username: emailSettingsForm?.username,
        password: emailSettingsForm?.password,
        sender_address: emailSettingsForm?.sender_address,
      },
    })
    .then((_data) => {
      // Cache is updated automatically by TanStack Query mutations
      alert.success(t('workspace.emails.saved'))
    })
    .catch((error) => {
      alert.error(t('workspace.emails.save_error', { message: error.response.data.message }))
    })
}

const initEmailSettings = () => {
  if (!workspace || !workspace.value.settings.email_settings) return
  const emailSettings = workspace.value?.settings?.email_settings
  emailSettingsForm.host = emailSettings?.host
  emailSettingsForm.port = emailSettings?.port
  emailSettingsForm.encryption = emailSettings?.encryption === null ? 'none' : (emailSettings?.encryption || 'tls')
  emailSettingsForm.username = emailSettings?.username
  emailSettingsForm.password = emailSettings?.password
  emailSettingsForm.sender_address = emailSettings?.sender_address
}
</script> 