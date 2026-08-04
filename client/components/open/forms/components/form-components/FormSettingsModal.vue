<template>
  <SettingsModal
    v-model="isOpen"
    v-model:activeTab="activeTab"
    @close="closeModal"
  >
    <SettingsModalPage
      id="general"
      :label="$t('common.labels.general')"
      icon="i-heroicons-information-circle"
    >
      <FormInformation />
    </SettingsModalPage>

    <SettingsModalPage
      id="submission"
      :label="$t('form_blocks.sections.submission_settings')"
      icon="i-heroicons-paper-airplane"
    >
      <FormSubmissionSettings />
    </SettingsModalPage>

    <SettingsModalPage
      id="security"
      :label="$t('form_blocks.sections.security_access')"
      icon="i-heroicons-shield-check"
    >
      <FormSecurityAccess />
    </SettingsModalPage>

    <SettingsModalPage
      id="seo"
      :label="$t('form_blocks.sections.seo_social_sharing')"
      icon="i-heroicons-link"
    >
      <FormCustomSeo />
    </SettingsModalPage>

    <SettingsModalPage
      id="custom-code"
      :label="$t('form_blocks.sections.custom_code')"
      icon="i-heroicons-code-bracket"
    >
      <FormCustomCode />
    </SettingsModalPage>

  </SettingsModal>
</template>

<script setup>
import SettingsModal from '~/components/pages/settings/SettingsModal.vue'
import SettingsModalPage from '~/components/pages/settings/SettingsModalPage.vue'
import FormInformation from '~/components/open/forms/components/form-components/FormInformation.vue'
import FormSubmissionSettings from '~/components/open/forms/components/form-components/FormSubmissionSettings.vue'
import FormSecurityAccess from '~/components/open/forms/components/form-components/FormSecurityAccess.vue'
import FormCustomSeo from '~/components/open/forms/components/form-components/FormCustomSeo.vue'
import FormCustomCode from '~/components/open/forms/components/form-components/FormCustomCode.vue'

const emit = defineEmits(['close', 'update:activeTab'])

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  activeTab: {
    type: String,
    default: 'general'
  }
})

// Modal state
const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('close', value)
})

// Active tab state
const activeTab = computed({
  get: () => props.activeTab,
  set: (value) => emit('update:activeTab', value)
})

// Methods
const closeModal = () => {
  isOpen.value = false
}

// Define keyboard shortcuts
defineShortcuts({
  escape: {
    handler: () => {
      closeModal()
    }
  }
})
</script> 