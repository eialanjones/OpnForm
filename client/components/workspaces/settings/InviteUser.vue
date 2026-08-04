<template>
  <UModal
    v-model:open="isOpen"
    :content="{
      onPointerDownOutside: (event) => { if (event.target?.closest('.crisp-client')) {return event.preventDefault()}}
    }"
  >
    <template #header>
      <div class="flex items-center w-full gap-4 px-2">
        <h2 class="font-semibold">
          {{ $t('workspace.invite_user.title') }}
        </h2>
      </div>
      <UButton
        color="neutral"
        variant="outline"
        icon="i-heroicons-question-mark-circle"
        size="sm"
        @click="crisp.openHelpdeskArticle('how-to-invite-users-team-members-to-my-workspace-qyw16g')"
      >
        {{ $t('workspace.actions.help') }}
      </UButton>
    </template>

    <template #body>
      <template v-if="paidPlansEnabled && !hasActiveLicense">
        <UAlert
          v-if="workspace.is_pro"
          icon="i-heroicons-credit-card"
          color="primary"
          variant="subtle"
          :title="$t('workspace.invite_user.billable_title')"
        >
          <template #description>
            <i18n-t
              keypath="workspace.invite_user.billable_description"
              scope="global"
              tag="span"
            >
              <template #billing>
                <NuxtLink
                  target="_blank"
                  class="underline cursor-pointer"
                  @click="openBilling"
                >
                  {{ $t('workspace.invite_user.billing_link') }}
                </NuxtLink>
              </template>
              <template #pricing>
                <NuxtLink
                  target="_blank"
                  class="underline"
                  :to="{name:'pricing'}"
                >
                  {{ $t('workspace.invite_user.pricing_link') }}
                </NuxtLink>
              </template>
            </i18n-t>
          </template>
        </UAlert>
        <UAlert
          v-else
          icon="i-heroicons-user-group-20-solid"
          class="mb-4"
          color="warning"
          variant="subtle"
          :title="$t('workspace.pro.required_title')"
          :description="$t('workspace.invite_user.pro_required_description')"
          :actions="[{
            label: $t('workspace.pro.upgrade_to_pro'),
            color: 'warning',
            variant: 'solid',
            onClick: () => openSubscriptionModal({
              modal_title: $t('workspace.invite_user.upgrade_modal_title'),
              modal_description: $t('workspace.invite_user.upgrade_modal_description')
            })
          }]"
        />
      </template>

      <VForm
        size="sm"
        class="my-2"
        @submit.prevent="addUser"
      >
        <TextInput
          :form="inviteUserForm"
          name="email"
          :label="$t('common.labels.email')"
          :required="true"
          :disabled="!workspace.is_pro"
          :placeholder="$t('workspace.invite_user.email_placeholder')"
        />
        <FlatSelectInput
          :form="inviteUserForm"
          name="role"
          :options="roleOptions"
          :disabled="!workspace.is_pro"
          :placeholder="$t('workspace.invite_user.role_placeholder')"
          :label="$t('workspace.fields.role')"
          :required="true"
        />
        <div class="flex justify-center mt-4">
          <UButton
            type="submit"
            :disabled="!workspace.is_pro"
            :loading="inviteUserMutation.isPending.value"
            icon="i-heroicons-envelope"
          >
            {{ $t('workspace.invite_user.submit') }}
          </UButton>
        </div>
      </VForm>
    </template>
  </UModal>
</template>

<script setup>
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  }
})

const { data: user } = useAuth().user()
const { addUser: addUserMutation } = useWorkspaceUsers()

// Local computed for active license check
const hasActiveLicense = computed(() => {
  return user.value !== null && user.value !== undefined && user.value.active_license !== null
})
const crisp = useCrisp()
const { openSubscriptionModal: openModal } = useAppModals()
const { current: workspace, currentId: workspaceId } = useCurrentWorkspace()
const alert = useAlert()
const { t } = useI18n()

const emit = defineEmits(['update:modelValue', 'user-added'])

const roleOptions = computed(() => [
  {name: t('workspace.roles.user'), value: "user"},
  {name: t('workspace.roles.admin'), value: "admin"},
  {name: t('workspace.roles.readonly'), value: "readonly"}
])

// Modal state
const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

// Create mutation during setup
const inviteUserMutation = addUserMutation(workspaceId)

// Methods
const closeModal = () => {
  isOpen.value = false
}

const openSubscriptionModal = () => {
  openModal({ modal_title: t('workspace.invite_user.upgrade_modal_title') })
}

const paidPlansEnabled = ref(useFeatureFlag('billing.enabled'))

const inviteUserForm = useForm({
  email: '',
  role: 'user'
})

const openBilling = () => {
  closeModal()
  useAppModals().openUserSettings('billing')
}

const addUser = () => {
  if (!workspaceId.value) return

  inviteUserMutation.mutateAsync({
    email: inviteUserForm.email,
    role: inviteUserForm.role
  }).then((data) => {
    inviteUserForm.reset()
    alert.success(data.message || t('workspace.invite_user.success'))
    emit('user-added')
    closeModal()
  }).catch((error) => {
    alert.error(error.response?.data?.message || t('workspace.invite_user.error'))
  })
}
</script>
