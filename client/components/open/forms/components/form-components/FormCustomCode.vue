<template>
  <VForm size="sm">
    <div class="space-y-4">
      <div class="flex flex-col flex-wrap items-start justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h3 class="text-lg font-medium text-neutral-900">
            {{ $t('form_blocks.sections.custom_code') }} <ProTag
              class="mb-2 block"
              :upgrade-modal-title="$t('form_blocks.custom_code.pro_title')"
              :upgrade-modal-description="$t('form_blocks.custom_code.pro_description')"
            />
          </h3>
          <p
            class="mt-1 text-sm text-neutral-500"
            v-html="$t('form_blocks.custom_code.code_injection_help_html')"
          />
        </div>
        <UButton
          :label="$t('form_blocks.actions.help')"
          icon="i-heroicons-question-mark-circle"
          variant="outline"
          color="neutral"
          @click="crisp.openHelpdeskArticle('how-do-i-add-custom-code-to-my-form-1amadj3')"
        />
      </div>

      <CodeInput
        :allow-fullscreen="true"
        name="custom_code"
        class="mt-4"
        :form="form"
        :disabled="!canUseCustomCode"
        :help="customCodeHelp"
        :label="$t('form_blocks.custom_code.code_label')"
        placeholder="<script>console.log('Hello World!')</script>"
      />

      <div class="pt-6">
        <div class="flex flex-col flex-wrap items-start justify-between gap-4 sm:flex-row sm:items-center">
          <div>
            <h3 class="text-lg font-medium text-neutral-900">
              {{ $t('form_blocks.custom_code.css_heading') }} <ProTag
                class="mb-2 block"
                :upgrade-modal-title="$t('form_blocks.custom_code.css_pro_title')"
                :upgrade-modal-description="$t('form_blocks.custom_code.css_pro_description')"
              />
            </h3>
            <p
              class="mt-1 text-sm text-neutral-500"
              v-html="$t('form_blocks.custom_code.css_injection_help_html')"
            />
          </div>
          <UButton
            :label="$t('form_blocks.actions.help')"
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
          :form="form"
          :help="$t('form_blocks.custom_code.css_input_help')"
          :label="$t('form_blocks.custom_code.css_label')"
          placeholder="body { background: #f8fafc }"
        />
      </div>
    </div>
  </VForm>
</template>

<script setup>
import ProTag from "~/components/app/ProTag.vue"

const { t } = useI18n()
const workingFormStore = useWorkingFormStore()
const { content: form } = storeToRefs(workingFormStore)
const crisp = useCrisp()

const canUseCustomCode = computed(() => workingFormStore.isCustomCodeAllowed)

const customCodeHelp = computed(() => {
  const hasCustomDomain = !!form.value?.custom_domain
  const selfHosted = !!useFeatureFlag('self_hosted', false)
  const allowSelfHosted = !!useFeatureFlag('custom_code.enable_self_hosted', false)
  if (canUseCustomCode.value) {
    return t('form_blocks.custom_code.help_enabled')
  }
  // In self-hosted mode with flag disabled (and no custom domain), show safety notice with docs link
  if (selfHosted && !allowSelfHosted && !hasCustomDomain) {
    return t('form_blocks.custom_code.help_self_hosted_disabled')
  }
  return t('form_blocks.custom_code.help_requires_custom_domain')
})

</script>
