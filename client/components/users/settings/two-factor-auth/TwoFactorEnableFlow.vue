<template>
  <div class="space-y-4">
    <UButton
      color="primary"
      :loading="enabling"
      @click="handleEnable"
    >
      {{ $t('user_settings.two_factor.enable_flow.enable_button') }}
    </UButton>

    <!-- QR Code Display -->
    <VTransition name="fadeHeight">
      <div v-if="secret" class="space-y-4 p-4 border border-neutral-200 rounded-lg bg-neutral-50">
        <div>
          <p class="text-sm font-medium text-neutral-900 mb-2">
            {{ $t('user_settings.two_factor.enable_flow.scan_qr') }}
          </p>
          <div class="flex justify-center p-4 bg-white rounded-lg max-w-xs mx-auto">
            <div v-html="qrCode" class="flex" />
          </div>
        </div>

        <div>
          <p class="text-sm font-medium text-neutral-900 mb-2">
            {{ $t('user_settings.two_factor.enable_flow.manual_entry') }}
          </p>
          <CopyContent
            :content="secret"
            :label="$t('user_settings.two_factor.enable_flow.copy_secret')"
          />
        </div>

        <div>
          <p class="text-sm font-medium text-neutral-900 mb-2">
            {{ $t('user_settings.two_factor.enable_flow.confirm_code_prompt') }}
          </p>
          <div class="flex justify-center mb-4">
            <UPinInput
              v-model="code"
              :length="6"
              type="number"
              otp
              size="lg"
              @complete="handleConfirm"
            />
          </div>
          <UButton
            block
            :loading="confirming"
            :disabled="code.length !== 6"
            @click="handleConfirm"
          >
            {{ $t('user_settings.two_factor.enable_flow.confirm_button') }}
          </UButton>
        </div>
      </div>
    </VTransition>
  </div>
</template>

<script setup>
import CopyContent from '~/components/open/forms/components/CopyContent.vue'

const props = defineProps({
  enabling: { type: Boolean, default: false },
  confirming: { type: Boolean, default: false },
  secret: { type: String, default: null },
  qrCode: { type: String, default: null },
})

const emit = defineEmits(['enable', 'confirm'])

const code = ref([])

const handleEnable = () => {
  emit('enable')
}

const handleConfirm = () => {
  if (code.value.length === 6) {
    emit('confirm', code.value.join(''))
  }
}

watch(() => props.secret, (newSecret) => {
  if (!newSecret) {
    code.value = []
  }
})
</script>

