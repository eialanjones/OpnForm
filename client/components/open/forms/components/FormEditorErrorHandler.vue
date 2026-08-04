<template>
  <ErrorBoundary @on-error="onFormEditorError">
    <template #error="{ error, clearError }">
      <div class="flex-grow w-full flex items-center justify-center flex-col gap-4">
        <h1 class="text-blue-800 text-2xl font-medium">
          {{ $t('form_editor.error_handler.title') }}
        </h1>
        <p class="text-neutral-500 max-w-lg text-center">
          {{ $t('form_editor.error_handler.description') }}
        </p>
        <div class="flex gap-2 mt-4">
          <UButton
            icon="i-material-symbols-undo"
            @click="clearEditorError(error, clearError)"
          >
            {{ $t('form_editor.error_handler.go_back_one_step') }}
          </UButton>
          <UButton
            variant="outline"
            icon="i-heroicons-chat-bubble-left-right-16-solid"
            @click="onErrorContact(error)"
          >
            {{ $t('form_editor.error_handler.report_error') }}
          </UButton>
        </div>
      </div>
    </template>
  
    <slot />
  </ErrorBoundary>
</template>
  
<script setup>
import ErrorBoundary from '~/components/app/ErrorBoundary.vue'

const crisp = useCrisp()
const workingFormStore = useWorkingFormStore()
const form = storeToRefs(workingFormStore).content
const { t } = useI18n()

// Clear error and go back 1 step in history
const clearEditorError = (error, clearError) => {
  crisp.enableChatbot()
  workingFormStore.undo()
  clearError()
}
const onFormEditorError = (error) => {
  console.error('Form Editor Error Handled', error)
  crisp.pauseChatBot()
  const eventData = {
    message: error.message,
    // take first 200 characters
    stack: error.stack.substring(0, 100)
  }
  try {
    crisp.pushEvent('form-editor-error', eventData)
  } catch (e) {
    console.error('Failed to send event to crisp', e, eventData)
  }
}
const onErrorContact = (error) => {
  crisp.pauseChatBot()
  let errorReport = t('form_editor.error_handler.report_intro')
  if (form.value.slug) {
    errorReport += t('form_editor.error_handler.report_form', { slug: form.value.slug })
  }
  errorReport += t('form_editor.error_handler.report_details', { details: error.stack })
  try {
    crisp.openAndShowChat(errorReport)
    crisp.showMessage(t('form_editor.error_handler.report_reply'), 2000)
  } catch (e) {
    console.error('Crisp error', e)
  }
}
</script>
  