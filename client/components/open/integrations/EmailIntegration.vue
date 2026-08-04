<template>
  <IntegrationWrapper
    v-model="props.integrationData"
    :integration="props.integration"
    :form="form"
  >
    <p class="text-neutral-500 text-sm mb-3">
      {{ $t('integrations.email.smtp_notice') }}
      <a
        class="underline cursor-pointer"
        @click="openEmailsModal"
      >
        {{ $t('integrations.email.smtp_link') }}
      </a>
    </p>

    <MentionInput
      :form="integrationData"
      :mentions="form.properties"
      :disable-mention="!form.is_pro"
      :disabled="!form.is_pro"
      name="data.send_to"
      required
      :label="$t('integrations.email.send_to_label')"
    >
      <template #help>
        <InputHelp>
        <span v-if="form.is_pro">
          {{ $t('integrations.email.send_to_help_pro') }}
        </span>
        <span v-else>
          {{ $t('integrations.email.send_to_help_free') }}
          <a
            class="underline cursor-pointer"
            @click="openSubscriptionModal"
          >{{ $t('integrations.email.send_to_help_free_link') }}</a>
        </span>
        </InputHelp>
      </template>
    </MentionInput>
    <div class="flex space-x-4 mt-4">
      <MentionInput
        :form="integrationData"
        :mentions="form.properties"
        name="data.sender_name"
        :label="$t('integrations.email.sender_name_label')"
        class="flex-1"
      />
      <text-input
        v-if="selfHosted"
        :form="integrationData"
        name="data.sender_email"
        :label="$t('integrations.email.sender_email_label')"
        :help="$t('integrations.email.sender_email_help')"
        class="flex-1"
      />
    </div>
    <MentionInput
      :form="integrationData"
      :mentions="form.properties"
      required
      name="data.subject"
      :label="$t('integrations.email.subject_label')"
    />
    <rich-text-area-input
      :form="integrationData"
      :enable-mentions="true"
      :enable-image="true"
      :mentions="form.properties"
      name="data.email_content"
      :label="$t('integrations.email.content_label')"
      class="mt-4"
    />
    <collapse
      v-model="showEmailAppearance"
      class="mt-4 w-full border rounded-lg bg-gray-50 dark:bg-neutral-900 pr-4"
    >
      <template #title>
        <div class="flex gap-x-3 items-start pr-12 p-4">
          <div
            class="transition-colors"
            :class="{
              'text-blue-600': showEmailAppearance,
              'text-gray-300 dark:text-neutral-500': !showEmailAppearance,
            }"
          >
            <Icon
              name="heroicons:paint-brush-16-solid"
              size="24"
            />
          </div>
          <div class="grow">
            <h4 class="font-semibold flex items-center gap-2">
              {{ $t('integrations.email.appearance_title') }}
              <ProTag :upgrade-modal-title="$t('integrations.email.appearance_upgrade_modal_title')" />
            </h4>
            <p class="text-gray-400 dark:text-neutral-500 text-xs">
              {{ $t('integrations.email.appearance_description') }}
            </p>
          </div>
        </div>
      </template>
      <div class="border-t dark:border-neutral-700 p-4 space-y-4">
        <div
          v-if="emailAppearanceLocked"
          class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900 dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-100"
        >
          {{ $t('integrations.email.appearance_locked') }}
          <a
            class="underline cursor-pointer"
            @click="openSubscriptionModal"
          >
            {{ $t('integrations.email.appearance_locked_link') }}
          </a>
        </div>
        <image-input
          :form="integrationData"
          :disabled="emailAppearanceLocked"
          name="data.logo_url"
          :label="$t('integrations.email.logo_label')"
          :help="$t('integrations.email.logo_help')"
        />
        <div class="grid grid-cols-2 gap-4 mt-4">
          <div>
            <label class="text-neutral-700 dark:text-neutral-300 font-semibold text-xs mb-2 block">{{ $t('integrations.email.font_family_label') }}</label>
            <UButton
              color="neutral"
              block
              size="lg"
              variant="outline"
              :disabled="emailAppearanceLocked"
              @click="showGoogleFontPicker = true"
            >
              <span :style="{ 'font-family': (integrationData.data.font_family ? integrationData.data.font_family + ', sans-serif' : null) }">
                {{ integrationData.data.font_family || $t('common.labels.default') }}
              </span>
            </UButton>
            <GoogleFontPicker
              :show="showGoogleFontPicker"
              :font="integrationData.data.font_family || null"
              @close="showGoogleFontPicker = false"
              @apply="onApplyFont"
            />
          </div>
          <ColorInput
            :form="integrationData"
            :disabled="emailAppearanceLocked"
            name="data.font_color"
            :label="$t('integrations.email.font_color_label')"
            :help="$t('integrations.email.font_color_help')"
          />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <ColorInput
            :form="integrationData"
            :disabled="emailAppearanceLocked"
            name="data.outer_background_color"
            :label="$t('integrations.email.outer_background_label')"
            :help="$t('integrations.email.outer_background_help')"
          />
          <ColorInput
            :form="integrationData"
            :disabled="emailAppearanceLocked"
            name="data.inner_background_color"
            :label="$t('integrations.email.inner_background_label')"
            :help="$t('integrations.email.inner_background_help')"
          />
        </div>
      </div>
    </collapse>

    <toggle-switch-input
      :form="integrationData"
      name="data.include_submission_data"
      class="mt-4"
      :label="$t('integrations.email.include_submission_data_label')"
      :help="$t('integrations.email.include_submission_data_help')"
    />
    <toggle-switch-input
      v-if="integrationData.data.include_submission_data"
      :form="integrationData"
      name="data.include_hidden_fields_submission_data"
      class="mt-4"
      :label="$t('integrations.email.include_hidden_fields_label')"
      :help="$t('integrations.email.include_hidden_fields_help')"
    />
    <toggle-switch-input
      v-if="form.editable_submissions"
      :form="integrationData"
      name="data.link_edit_submission"
      class="mt-4"
      :label="$t('integrations.email.edit_submission_link_label')"
    />
    <MentionInput
      :form="integrationData"
      :mentions="form.properties"
      class="mt-4"
      name="data.reply_to"
      :label="$t('integrations.email.reply_to_label')"
      :help="$t('integrations.email.reply_to_help')"
    />
  </IntegrationWrapper>
</template>

<script setup>
import IntegrationWrapper from "./components/IntegrationWrapper.vue"
import GoogleFontPicker from "~/components/open/editors/GoogleFontPicker.vue"
import Collapse from "~/components/app/Collapse.vue"
import ProTag from "~/components/app/ProTag.vue"

const props = defineProps({
  integration: { type: Object, required: true },
  form: { type: Object, required: true },
  integrationData: { type: Object, required: true },
  formIntegrationId: { type: Number, required: false, default: null },
})

const { t } = useI18n()
const selfHosted = computed(() => useFeatureFlag('self_hosted'))
const { openWorkspaceSettings } = useAppModals()
const { data: user } = useAuth().user()

const showEmailAppearance = ref(false)
const showGoogleFontPicker = ref(false)
const emailAppearanceLocked = computed(() => !props.form.is_pro)

function onApplyFont(val) {
  if (props.integrationData.data) {
    props.integrationData.data.font_family = val
  }
  showGoogleFontPicker.value = false
}

function openEmailsModal () {
  openWorkspaceSettings('emails')
}

function openSubscriptionModal () {
  useAppModals().openSubscriptionModal({
    modal_title: t('integrations.email.upgrade_modal_title'),
    modal_description: t('integrations.email.upgrade_modal_description')
  })
}

onBeforeMount(() => {
  for (const [keyname, defaultValue] of Object.entries({
    send_to: user.value.email || '',
    sender_name: "Forms Mentorfy",
    subject: t('integrations.email.default_subject'),
    email_content: t('integrations.email.default_content'),
    include_submission_data: true,
    include_hidden_fields_submission_data: false,
    logo_url: null,
    font_family: null,
    font_color: null,
    outer_background_color: '#f0f0f0',
    inner_background_color: '#ffffff',
  })) {
    if (props.integrationData.data[keyname] === undefined) {
      props.integrationData.data[keyname] = defaultValue
    }
  }
})
</script>
