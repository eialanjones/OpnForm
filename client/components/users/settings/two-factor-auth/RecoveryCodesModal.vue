<template>
  <UModal
    v-model:open="isOpen"
    :ui="{ content: 'sm:max-w-md' }"
  >
    <template #header>
      <h2 class="text-lg font-semibold">{{ $t('user_settings.two_factor.recovery_codes_modal.title') }}</h2>
    </template>

    <template #body>
      <div class="space-y-4">
        <!-- Recovery codes display -->
        <div v-if="codes.length > 0" class="space-y-4">
          <UAlert
            color="warning"
            variant="subtle"
            :description="$t('user_settings.two_factor.recovery_codes_modal.warning')"
          />

          <div class="space-y-2">
            <div
              v-for="(codeItem, index) in codes"
              :key="index"
              class="flex items-center justify-center p-2 bg-neutral-50 rounded font-mono text-sm text-center"
            >
              <span>{{ typeof codeItem === 'string' ? codeItem : codeItem.code }}</span>
              <span
                v-if="typeof codeItem === 'object' && codeItem.used_at"
                class="text-xs text-neutral-500 ml-2 font-sans"
              >
                {{ $t('user_settings.two_factor.recovery_codes_modal.used_at', { date: formatDate(codeItem.used_at) }) }}
              </span>
            </div>
          </div>

          <div class="flex gap-2">
            <UButton
              block
              color="neutral"
              variant="outline"
              @click="handleCopy"
            >
              {{ $t('user_settings.two_factor.recovery_codes_modal.copy_all') }}
            </UButton>
            <UButton
              block
              color="primary"
              @click="handleClose"
            >
              {{ $t('user_settings.two_factor.recovery_codes_modal.saved_button') }}
            </UButton>
          </div>
        </div>
      </div>
    </template>
  </UModal>
</template>

<script setup>
const props = defineProps({
  show: { type: Boolean, required: true },
  codes: { type: Array, default: () => [] },
  justRegenerated: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'copy'])

const isOpen = computed({
  get: () => props.show,
  set: (value) => {
    if (!value) emit('close')
  }
})

const handleCopy = () => {
  emit('copy')
}

const handleClose = () => {
  emit('close')
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

</script>

