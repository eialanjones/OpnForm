<template>
  <div class="space-y-4">
    <div class="flex flex-col flex-wrap items-start justify-between gap-4 sm:flex-row sm:items-center">
      <div class="flex-1">
        <h3 class="text-lg font-medium text-neutral-900">
          {{ $t('workspace.custom_code.title') }} <ProTag
            class="mb-2 block"
            :upgrade-modal-title="$t('workspace.custom_code.upgrade_modal_title')"
            :upgrade-modal-description="$t('workspace.custom_code.upgrade_modal_description')"
          />
        </h3>
        <p
          class="mt-1 text-sm text-neutral-500"
          v-html="$t('workspace.custom_code.description')"
        />
      </div>
      <UButton
        :label="$t('workspace.actions.help')"
        icon="i-heroicons-question-mark-circle"
        variant="outline"
        color="neutral"
        @click="crisp.openHelpdeskArticle('how-do-i-add-custom-code-to-my-form-1amadj3')"
      />
    </div>

    <UAlert
      v-if="!workspace.is_pro"
      icon="i-heroicons-user-group-20-solid"
      class="mb-4"
      color="warning"
      variant="subtle"
      :title="$t('workspace.pro.required_title')"
      :description="$t('workspace.custom_code.pro_required_description')"
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
        <div class="space-y-4">
          <div>
            <CodeInput
              :allow-fullscreen="true"
              name="custom_code"
              class="mt-4"
              :form="customCodeForm"
              :disabled="!canUseCustomCode"
              :help="customCodeHelp"
              :label="$t('workspace.custom_code.title')"
              placeholder="<script>console.log('Hello World!')</script>"
            />
          </div>

          <div class="pt-6">
            <div class="flex flex-col flex-wrap items-start justify-between gap-4 sm:flex-row sm:items-center">
              <div>
                <h3 class="text-lg font-medium text-neutral-900">
                  {{ $t('workspace.custom_code.css_title') }} <ProTag
                    class="mb-2 block"
                    :upgrade-modal-title="$t('workspace.custom_code.css_upgrade_modal_title')"
                    :upgrade-modal-description="$t('workspace.custom_code.css_upgrade_modal_description')"
                  />
                </h3>
                <p
                  class="mt-1 text-sm text-neutral-500"
                  v-html="$t('workspace.custom_code.css_description')"
                />
              </div>
              <UButton
                :label="$t('workspace.actions.help')"
                icon="i-heroicons-question-mark-circle"
                variant="outline"
                color="neutral"
                @click="crisp.openHelpdeskArticle('can-i-style-my-form-with-some-custom-css-code-1v3dlr9')"
              />
            </div>
            <CodeInput
              :allow-fullscreen="true"
              language-mode="css"
              name="custom_css"
              class="mt-4"
              :form="customCodeForm"
              :disabled="!workspace.is_pro"
              :help="$t('workspace.custom_code.css_help')"
              :label="$t('workspace.custom_code.css_title')"
              placeholder="body { background: #f8fafc }"
            />
          </div>
        </div>

        <div class="mt-4">
          <UButton
            type="submit"
            :loading="customCodeForm.busy"
            :disabled="!workspace.is_pro"
            color="primary"
          >
            {{ $t('workspace.actions.save_changes') }}
          </UButton>
        </div>
      </form>
    </VForm>
  </div>
</template>

<script setup>
import ProTag from "~/components/app/ProTag.vue"

const alert = useAlert()
const crisp = useCrisp()
const { current: workspace } = useCurrentWorkspace()
const { openSubscriptionModal: openModal } = useAppModals()
const { invalidateAll } = useWorkspaces()
const { t } = useI18n()

const openSubscriptionModal = () => {
  openModal({ modal_title: t('workspace.custom_code.subscription_modal_title') })
}

const customCodeForm = useForm({
  custom_code: '',
  custom_css: ''
})

const hasCustomDomain = computed(() => {
  return workspace.value?.custom_domains && workspace.value.custom_domains.length > 0
})

const selfHosted = computed(() => !!useFeatureFlag('self_hosted', false))
const allowSelfHosted = computed(() => !!useFeatureFlag('custom_code.enable_self_hosted', false))

const canUseCustomCode = computed(() => {
  if (!workspace.value?.is_pro) return false
  return hasCustomDomain.value || (selfHosted.value && allowSelfHosted.value)
})

const customCodeHelp = computed(() => {
  if (canUseCustomCode.value) {
    return t('workspace.custom_code.help_enabled')
  }
  if (selfHosted.value && !allowSelfHosted.value && !hasCustomDomain.value) {
    return t('workspace.custom_code.help_self_hosted_disabled')
  }
  return t('workspace.custom_code.help_requires_pro')
})

const saveChanges = () => {
  if (!workspace.value?.is_pro) return

  customCodeForm
    .put(`/open/workspaces/${workspace.value.id}/custom-code-settings`, {
      data: {
        custom_code: customCodeForm.custom_code || null,
        custom_css: customCodeForm.custom_css || null,
      },
    })
    .then((_data) => {
      alert.success(t('workspace.custom_code.saved'))
      // Invalidate workspace cache to refresh data
      invalidateAll()
    })
    .catch((error) => {
      alert.error(t('workspace.custom_code.save_error', { message: error.response?.data?.message || error.message }))
    })
}

const initCustomCode = () => {
  if (!workspace.value) return
  const settings = workspace.value.settings || {}
  customCodeForm.custom_code = settings.custom_code || ''
  customCodeForm.custom_css = settings.custom_css || ''
}

onMounted(() => {
  initCustomCode()
})

watch(
  () => workspace.value,
  () => {
    initCustomCode()
  },
  { deep: true }
)
</script>
