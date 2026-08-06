<template>
  <VForm size="sm">
    <div class="space-y-4">
      <div class="flex flex-col flex-wrap items-start justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h3 class="text-lg font-medium text-neutral-900">{{ $t('form_blocks.sections.submission_settings') }}</h3>
          <p class="mt-1 text-sm text-neutral-500">
            {{ $t('form_blocks.submission.description') }}
          </p>
        </div>
      </div>

      <div class="flex flex-wrap items-end gap-4">
        <TextInput
          name="submit_button_text"
          :form="form"
          class="max-w-xs"
          :label="$t('form_blocks.submission.submit_button_label')"
        />
        <TextInput
          v-if="isFocused"
          v-model="focusedNextText"
          name="focused_next_button_text"
          class="max-w-xs"
          :label="$t('form_blocks.submission.next_button_label')"
          :placeholder="$t('forms.buttons.next')"
        />
      </div>
      <ToggleSwitchInput
        name="auto_save"
        :form="form"
        :label="$t('form_blocks.submission.auto_save_label')"
        :help="$t('form_blocks.submission.auto_save_help')"
        class="mt-4"
        :disabled="hasPaymentBlock"
      />
      <UAlert
        v-if="hasPaymentBlock"
        color="primary"
        variant="subtle"
        :title="$t('form_blocks.submission.payment_auto_save_notice')"
        class="max-w-md"
      />
      
      <FlatSelectInput
        :form="submissionOptions"
        name="databaseAction"
        class="mt-4 max-w-xs"
        :label="$t('form_blocks.submission.database_action_label')"
        :options="[
          { name: $t('form_blocks.submission.database_actions.create'), value: 'create' },
          { name: $t('form_blocks.submission.database_actions.update'), value: 'update' }
        ]"
        :required="true"
      />
      <div
        v-if="submissionOptions.databaseAction == 'update'"
        class="bg-neutral-50 border rounded-lg px-4 py-2"
      >
        <div
          class="w-auto max-w-lg"
        >
          <p class="mb-2 mt-2 text-neutral-500 text-sm">
            {{ $t('form_blocks.submission.update_record_help') }}
            <a
              href="#"
              class="text-blue-500 hover:underline"
              @click.prevent="crisp.openHelpdeskArticle('how-to-update-a-record-on-form-submission-1t1jwmn')"
            >
              {{ $t('form_blocks.submission.learn_more_link') }}
            </a>
          </p>
          <select-input
            v-if="filterableFields.length"
            :form="form"
            name="database_fields_update"
            :label="$t('form_blocks.submission.properties_to_check_label')"
            :options="filterableFields"
            multiple
            clearable
          />

          <toggle-switch-input
            v-model="clearEmptyFieldsOnUpdate"
            class="mt-4"
            :label="$t('form_blocks.submission.clear_empty_fields_label')"
            :help="$t('form_blocks.submission.clear_empty_fields_help')"
          />
        </div>
      </div>

      <!-- Advanced Submission Settings -->
      <div class="mb-8">
        <h4 class="font-semibold mt-4 border-t pt-4">
          {{ $t('form_blocks.submission.advanced_heading') }} <ProTag  class="ml-1"/>
        </h4>
        <p class="text-gray-500 text-sm mb-4">
          {{ $t('form_blocks.submission.advanced_description') }}
        </p>
        
        <ToggleSwitchInput
          name="enable_partial_submissions"
          :form="form"
          :help="$t('form_blocks.submission.partial_submissions_help')"
        >
          <template #label>
            <span class="text-sm">
              {{ $t('form_blocks.submission.partial_submissions_label') }}
            </span>
            <ProTag
              class="ml-1"
              :upgrade-modal-title="$t('form_blocks.submission.partial_submissions_pro_title')"
              :upgrade-modal-description="$t('form_blocks.submission.partial_submissions_pro_description')"
            />
          </template>
        </ToggleSwitchInput>
      </div>

      <ToggleSwitchInput
        class="mt-4"
        name="enable_ip_tracking"
        :form="form"
        :help="$t('form_blocks.submission.ip_tracking_help')"
      >
        <template #label>
          <span class="text-sm">
            {{ $t('form_blocks.submission.ip_tracking_label') }}
          </span>
          <ProTag
            class="ml-1"
            :upgrade-modal-title="$t('form_blocks.submission.ip_tracking_pro_title')"
            :upgrade-modal-description="$t('form_blocks.submission.ip_tracking_pro_description')"
          />
        </template>
      </ToggleSwitchInput>
      <UAlert
        v-if="form.enable_ip_tracking"
        color="neutral"
        icon="i-heroicons-shield-exclamation"
        variant="subtle"
        :title="$t('form_blocks.submission.gdpr_title')"
        :description="$t('form_blocks.submission.gdpr_description')"
        class="mt-4 max-w-md"
      />

      <!-- Post-Submission Behavior -->
      <div class="mb-8">
        <h4 class="font-semibold mt-4 border-t pt-4">
          {{ $t('form_blocks.submission.after_submission_heading') }} <pro-tag
            :upgrade-modal-title="$t('form_blocks.submission.after_submission_pro_title')"
            :upgrade-modal-description="$t('form_blocks.submission.after_submission_pro_description')"
          />
        </h4>
        <p class="text-neutral-500 text-sm mb-4">
          {{ $t('form_blocks.submission.after_submission_description') }}
        </p>

        <OptionSelectorInput
          :label="$t('form_blocks.submission.action_after_label')"
          v-model="submissionOptions.submissionMode"
          :options="[
            { name: 'default', label: $t('form_blocks.submission.actions.success_page') },
            { name: 'redirect', label: $t('form_blocks.submission.actions.redirect') }
          ]"
          option-key="name"
          :columns="2"
          class="mb-4 max-w-xs"
        />

        <div
          v-if="submissionOptions.submissionMode"
          class="bg-gray-50 border rounded-lg px-4 py-2"
        >
          <div class="w-auto max-w-lg">
            <template v-if="submissionOptions.submissionMode === 'redirect'">
              <MentionInput
                name="redirect_url"
                :form="form"
                :mentions="mentionsWithScore"
                class="w-full max-w-xs"
                :label="$t('form_blocks.submission.redirect_url_label')"
                placeholder="https://www.google.com"
                :required="true"
              />
            </template>
            <template v-else>
              <rich-text-area-input
                enable-mentions
                :mentions="mentionsWithScore"
                :allow-fullscreen="true"
                name="submitted_text"
                class="w-full"
                :form="form"
                :label="$t('form_blocks.submission.success_text_label')"
                :required="false"
                :max-char-limit="10000"
                :show-char-limit="true"
              />
              <div class="flex items-center flex-wrap gap-x-4 mt-4">
                <toggle-switch-input
                  name="re_fillable"
                  class="w-full max-w-xs"
                  :form="form"
                  :label="$t('form_blocks.submission.re_fillable_label')"
                  :help="$t('form_blocks.submission.re_fillable_help')"
                />
                <text-input
                  v-if="form.re_fillable"
                  name="re_fill_button_text"
                  :form="form"
                  :label="$t('form_blocks.submission.restart_button_label')"
                />
              </div>
            </template>
          </div>
        </div>
      </div>

      <!-- Editable Submissions Settings -->
      <div class="mb-8">
        <h4 class="font-semibold mt-4 border-t pt-4">
          {{ $t('form_blocks.submission.editable_heading') }} <ProTag
              class="ml-1"
              :upgrade-modal-title="$t('form_blocks.submission.editable_pro_title')"
              :upgrade-modal-description="$t('form_blocks.submission.editable_pro_description')"
            />
        </h4>
        <p class="text-gray-500 text-sm mb-4">
          {{ $t('form_blocks.submission.editable_description') }}
        </p>
        <toggle-switch-input
          name="editable_submissions"
          class="w-full max-w-sm"
          :help="$t('form_blocks.submission.editable_help')"
          :form="form"
          :label="$t('form_blocks.submission.editable_label')"
        />
        <text-input
          v-if="form.editable_submissions"
          name="editable_submissions_button_text"
          class="w-full max-w-64 mt-4"
          :form="form"
          :label="$t('form_blocks.submission.edit_button_label')"
          :required="true"
        />
      </div>
    </div>
  </VForm>
</template>

<script setup>
import ProTag from "~/components/app/ProTag.vue"
import { buildScoreMentions } from "~/lib/forms/scoring"

const workingFormStore = useWorkingFormStore()
const { content: form } = storeToRefs(workingFormStore)
const crisp = useCrisp()
const { t } = useI18n()

// The score is only known once the form is submitted, so it is offered here
// (thank-you text, redirect URL) and nowhere else.
const mentionsWithScore = computed(() => [
  ...(form.value?.properties ?? []),
  ...buildScoreMentions(form.value, t),
])

const submissionOptions = ref({})

const filterableFields = computed(() => {
  if (submissionOptions.value.databaseAction !== "update") return []
  return form.value.properties
    .filter((field) => {
      return (
        !field.hidden &&
        !["files", "signature", "multi_select", "matrix", 'payment', 'ai_pdf'].includes(field.type)
      )
    })
    .map((field) => {
      return {
        name: field.name,
        value: field.id,
      }
    })
})

const clearEmptyFieldsOnUpdate = computed({
  get: () => form.value.clear_empty_fields_on_update ?? false,
  set: (value) => { form.value.clear_empty_fields_on_update = value }
})

watch({
  redirect_url: form.value.redirect_url,
  database_fields_update: form.value.database_fields_update
}, () => {
  if (form.value) {
    submissionOptions.value = {
      submissionMode: form.value.redirect_url ? 'redirect' : 'default',
      databaseAction: form.value.database_fields_update ? 'update' : 'create'
    }
  }
}, { immediate: true })

watch(submissionOptions, (val) => {
  if (val.submissionMode === 'default') form.value.redirect_url = null
  if (val.databaseAction === 'create') form.value.database_fields_update = null
}, { deep: true })

const hasPaymentBlock = computed(() => {
  return form.value.properties.some(property => property.type === 'payment')
})

const isFocused = computed(() => form.value?.presentation_style === 'focused')

onMounted(() => {
  // Ensure translations is a plain, writable object (avoid writing into readonly proxies)
  const t = form.value?.translations
  if (!t || typeof t !== 'object' || Array.isArray(t)) {
    form.value.translations = {}
  } else {
    form.value.translations = { ...t }
  }
})

const focusedNextText = computed({
  get() {
    return form.value?.translations?.focused_next_button_text || ''
  },
  set(val) {
    const current = form.value?.translations && typeof form.value.translations === 'object' ? form.value.translations : {}
    // Replace the entire translations object to avoid setting into a readonly proxy
    form.value.translations = { ...current, focused_next_button_text: val }
  }
})
</script>
