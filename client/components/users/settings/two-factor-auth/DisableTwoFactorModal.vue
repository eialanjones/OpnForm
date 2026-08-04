<template>
  <UModal
    v-model:open="isOpen"
    :ui="{ content: 'sm:max-w-md' }"
  >
    <template #header>
      <h2 class="text-lg font-semibold">{{ $t('user_settings.two_factor.disable_modal.title') }}</h2>
    </template>

    <template #body>
      <div class="space-y-4">
        <UAlert
          color="warning"
          variant="subtle"
          :description="$t('user_settings.two_factor.disable_modal.warning')"
        />

        <div>
          <p class="text-sm font-medium text-neutral-900 mb-2">
            {{ $t('user_settings.two_factor.enter_code_prompt') }}
          </p>
          <div class="flex justify-center mb-4">
            <UPinInput
              v-model="code"
              :length="6"
              type="number"
              otp
              size="lg"
              @complete="handleDisable"
            />
          </div>
          <div class="text-center">
            <UButton
              variant="link"
              size="sm"
              @click="showRecoveryCode = !showRecoveryCode"
            >
              {{ $t('user_settings.two_factor.use_recovery_code') }}
            </UButton>
          </div>
        </div>

        <VTransition name="fadeHeight">
          <div v-if="showRecoveryCode" class="mt-4">
            <TextInput
              v-model="recoveryCode"
              :label="$t('user_settings.two_factor.recovery_code_label')"
              :placeholder="$t('user_settings.two_factor.recovery_code_placeholder')"
              @keyup.enter="handleDisable"
            />
          </div>
        </VTransition>
      </div>
    </template>

    <template #footer>
      <div class="flex justify-between w-full gap-2">
        <UButton
          color="neutral"
          variant="outline"
          @click="handleClose"
        >
          {{ $t('common.actions.cancel') }}
        </UButton>
        <UButton
          color="error"
          :loading="loading"
          :disabled="code.length !== 6 && !recoveryCode"
          @click="handleDisable"
        >
          {{ $t('user_settings.two_factor.disable_button') }}
        </UButton>
      </div>
    </template>
  </UModal>
</template>

<script setup>
const props = defineProps({
  show: { type: Boolean, required: true },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'disable'])

const isOpen = computed({
  get: () => props.show,
  set: (value) => {
    if (!value) emit('close')
  }
})

const code = ref([])
const recoveryCode = ref('')
const showRecoveryCode = ref(false)

const handleDisable = () => {
  const codeValue = code.value.length === 6 ? code.value.join('') : null
  const finalCode = codeValue || recoveryCode.value
  
  if (finalCode) {
    emit('disable', finalCode)
  }
}

const handleClose = () => {
  code.value = []
  recoveryCode.value = ''
  showRecoveryCode.value = false
  emit('close')
}

watch(() => props.show, (show) => {
  if (!show) {
    code.value = []
    recoveryCode.value = ''
    showRecoveryCode.value = false
  }
})
</script>

