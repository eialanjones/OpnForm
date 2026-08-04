<template>
  <VForm size="sm">
    <div class="space-y-4">
      <div class="flex flex-col flex-wrap items-start justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h3 class="text-lg font-medium text-neutral-900">{{ $t('form_blocks.sections.security_access') }}</h3>
          <p class="mt-1 text-sm text-neutral-500">
            {{ $t('form_blocks.security.description') }}
          </p>
        </div>
      </div>

      <TextInput
        name="password"
        :form="form"
        class="mt-4 max-w-xs"
        :label="$t('form_blocks.security.password_label')"
        placeholder="********"
        :help="$t('form_blocks.security.password_help')"
      />
      <DateInput
        :with-time="true"
        name="closes_at"
        class="mt-4 max-w-xs"
        :form="form"
        :label="$t('form_blocks.security.closing_date_label')"
        :help="$t('form_blocks.security.closing_date_help')"
        :required="false"
      />
      <div
        v-if="form.closes_at || form.visibility == 'closed'"
        class="bg-neutral-50 border rounded-lg px-4 py-2"
      >
        <rich-text-area-input
          name="closed_text"
          :form="form"
          :allow-fullscreen="true"
          :label="$t('form_blocks.closed_form.text_label')"
          :help="$t('form_blocks.closed_form.text_help')"
          :required="false"
          wrapper-class="mb-0"
        />
      </div>
      <text-input
        name="max_submissions_count"
        native-type="number"
        :min="1"
        :form="form"
        :label="$t('form_blocks.security.max_submissions_label')"
        :placeholder="$t('form_blocks.security.max_submissions_placeholder')"
        class="mt-4 max-w-xs"
        :help="$t('form_blocks.security.max_submissions_help')"
        :required="false"
      />
      <div
        v-if="form.max_submissions_count && form.max_submissions_count > 0"
        class="bg-neutral-50 border rounded-lg px-4 py-2"
      >
        <rich-text-area-input
          wrapper-class="mb-0"
          :allow-fullscreen="true"
          name="max_submissions_reached_text"
          :form="form"
          :label="$t('form_blocks.security.max_submissions_reached_label')"
          :help="$t('form_blocks.security.max_submissions_reached_help')"
          :required="false"
        />
      </div>

      <h4 class="font-semibold mt-4 border-t pt-4">
        {{ $t('form_blocks.security.section_heading') }}
      </h4>
      <p class="text-neutral-500 text-sm">
        {{ $t('form_blocks.security.section_description') }}
      </p>
      <div
        v-if="hasCaptcha"
        class="flex items-start gap-6 flex-wrap"
      >
        <ToggleSwitchInput
          name="use_captcha"
          :form="form"
          class="mt-4"
          :label="$t('form_blocks.security.bot_protection_label')"
          :help="$t('form_blocks.security.bot_protection_help')"
        />
        <FlatSelectInput
          v-if="form.use_captcha"
          name="captcha_provider"
          :form="form"
          :options="captchaOptions"
          class="mt-4 w-80"
          :label="$t('form_blocks.security.captcha_provider_label')"
        />
      </div>
    </div>
  </VForm>
</template>

<script setup>
const workingFormStore = useWorkingFormStore()
const { content: form } = storeToRefs(workingFormStore)
const config = useRuntimeConfig()

const hasCaptcha = computed(() => {
  return config.public.hCaptchaSiteKey || config.public.reCaptchaSiteKey
})

const captchaOptions = computed(() => {
  const options = []
  
  if (config.public.reCaptchaSiteKey) {
    options.push({ name: 'reCAPTCHA', value: 'recaptcha' })
  }
  
  if (config.public.hCaptchaSiteKey) {
    options.push({ name: 'hCaptcha', value: 'hcaptcha' })
  }
  
  return options
})
</script>
