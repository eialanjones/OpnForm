<template>
  <div class="flex">
    <TrackClick
      name="url_form_prefill_click"
      :properties="{
        form_id: form.id,
        form_slug: form.slug,
      }"
    >
      <UButton
        variant="outline"
        color="neutral"
        icon="i-heroicons-link"
        @click="showUrlFormPrefillModal = true"
        :label="$t('form_pages.url_prefill.trigger')"
      />
    </TrackClick>

    <UModal
      v-model:open="isModalOpen"
      :ui="{ content: 'sm:max-w-2xl' }"
    >
      <template #header>
        <div class="flex items-center w-full gap-4 px-2">
          <h2 class="font-semibold">
            {{ $t('form_pages.url_prefill.modal_title') }}
          </h2>
        </div>
        <UButton
          color="neutral"
          variant="outline"
          icon="i-heroicons-question-mark-circle"
          size="sm"
          @click="crisp.openHelpdeskArticle('how-to-use-url-form-pre-fill-1juyi21')"
        >
          {{ $t('form_pages.shared.help') }}
        </UButton>
      </template>

      <template #body>
        <div ref="content">
          <p>
            {{ $t('form_pages.url_prefill.description') }}
          </p>

          <h3 class="mt-6 border-t text-xl font-semibold mb-4 pt-6">
            {{ $t('form_pages.shared.how_it_works') }}
          </h3>

          <p>
            {{ $t('form_pages.url_prefill.instructions') }}
          </p>

          <div class="rounded-lg p-5 bg-neutral-100 dark:bg-neutral-900 mt-4">
            <OpenForm
              v-if="formManager"
              :form-manager="formManager"
              @submit="generateUrl"
            >
              <template #submit-btn="{loading}">
                <UButton
                  class="mt-4"
                  :loading="loading"
                  @click="generateUrl"
                  :label="$t('form_pages.url_prefill.generate_button')"
                />
              </template>
            </OpenForm>
          </div>

          <h3 class="mt-6 text-xl font-semibold mb-4 pt-6">
            {{ $t('form_pages.url_prefill.result_heading') }}
          </h3>
          <FormUrlPrefill
            :form="form"
            :form-data="prefillFormData || {}"
            :extra-query-param="extraQueryParam"
          />
        </div>
      </template>
    </UModal>
  </div>
</template>

<script setup>
import FormUrlPrefill from "~/components/open/forms/components/FormUrlPrefill.vue"
import OpenForm from "~/components/open/forms/OpenForm.vue"
import { FormMode } from "~/lib/forms/FormModeStrategy.js"
import { useFormManager } from '~/lib/forms/composables/useFormManager'
import TrackClick from "~/components/global/TrackClick.vue"

const props = defineProps({
  form: { type: Object, required: true },
  extraQueryParam: { type: String, default: "" },
})

const crisp = useCrisp()

// State variables
const prefillFormData = ref(null)
const showUrlFormPrefillModal = ref(false)
const content = ref(null)

// Modal state
const isModalOpen = computed({
  get() {
    return showUrlFormPrefillModal.value
  },
  set(value) {
    showUrlFormPrefillModal.value = value
  }
})

// Set up form manager with proper mode
let formManager = null
const setupFormManager = () => {
  if (!props.form) return null
  
  formManager = useFormManager(props.form, FormMode.PREFILL, {
    darkMode: false
  })
  formManager.initialize()
  
  return formManager
}

// Initialize form manager
formManager = setupFormManager()

// Watch for form changes to reinitialize form manager
watch(() => props.form, (newForm) => {
  if (newForm) {
    formManager = setupFormManager()
  } else {
    formManager = null
  }
}, { deep: true })

// Method to generate URL
const generateUrl = () => {
  if (!formManager) return
  
  const formData = formManager.data.value
  
  prefillFormData.value = formData
  
  nextTick().then(() => {
    if (content.value) {
      content.value.parentElement.parentElement.parentElement.scrollTop =
        content.value.offsetHeight
    }
  })
}
</script>
