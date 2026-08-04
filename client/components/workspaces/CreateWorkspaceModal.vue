<template>
  <UModal
    v-model:open="isOpen"
    :ui="{
      content: 'w-full max-w-md'
    }"
  >
    <template #header>
      <div class="flex items-center w-full gap-4 px-2">
        <h2 class="font-semibold">
          {{ $t('workspace.create_modal.title') }}
        </h2>
      </div>
      <UButton
        color="neutral"
        variant="outline"
        icon="i-heroicons-question-mark-circle"
        size="sm"
        @click="crisp.openHelpdeskArticle('how-many-workspaces-can-i-create-r4dvt6')"
      >
        {{ $t('workspace.actions.help') }}
      </UButton>
    </template>
    
    <template #body>
        <VForm size="sm">
          <form
            @submit.prevent="handleSubmit"
          >
            <text-input
              name="name"
              :form="form"
              :required="true"
              :disabled="form.busy"
              :label="$t('workspace.fields.workspace_name')"
              :placeholder="$t('workspace.fields.workspace_name_placeholder')"
            />
            <text-input
              name="emoji"
              class="mt-4"
              :form="form"
              :required="false"
              :disabled="form.busy"
              :label="$t('workspace.fields.emoji')"
              placeholder="🚀"
              :help="$t('workspace.fields.emoji_help')"
            />
          </form>
        </VForm>
    </template>
    <template #footer>
      <div class="flex gap-2 w-full">
        <UButton color="neutral" variant="outline" @click="closeModal">{{ $t('common.actions.cancel') }}</UButton>
        <UButton block type="submit" :loading="form.busy" @click="handleSubmit">{{ $t('workspace.create_modal.title') }}</UButton>
      </div>
    </template>
  </UModal>
</template>

<script setup>
const emit = defineEmits(['created', 'close'])

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  }
})

const { create } = useWorkspaces()
const appStore = useAppStore()
const crisp = useCrisp()
const alert = useAlert()
const { t } = useI18n()

// Modal state
const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('close', value)
})

// Form state
const form = useForm({
  name: '',
  emoji: ''
})

// Handle form submission
const createMutation = create()

const handleSubmit = () => {
  form.mutate(createMutation).then((response) => {
    const newWorkspace = response.workspace
    appStore.setCurrentId(newWorkspace.id)

    // Show success message
    alert.success(t('workspace.create_modal.success_message'), 10000, {
      title: t('workspace.create_modal.success_title')
    })

    // Emit created event and close modal
    emit('created', newWorkspace)
    closeModal()
  }).catch((error) => {
    console.error('Error creating workspace:', error)
    alert.error(error.data?.message || t('workspace.create_modal.error_message'), 10000, {
      title: t('workspace.create_modal.error_title')
    })
  })
}

// Close modal and reset form
const closeModal = () => {
  form.reset()
  isOpen.value = false
}

// Reset form when modal opens
watch(isOpen, (newValue) => {
  if (newValue) {
    form.reset()
  }
})
</script> 