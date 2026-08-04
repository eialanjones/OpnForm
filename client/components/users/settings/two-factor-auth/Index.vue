<template>
  <div class="space-y-4">
    <div>
      <h3 class="text-lg font-medium text-neutral-900">{{ $t('user_settings.two_factor.heading') }}</h3>
      <p class="text-sm text-neutral-500 mt-1">
        {{ $t('user_settings.two_factor.description') }}
      </p>
    </div>

    <!-- 2FA Status -->
    <div v-if="twoFactorEnabled" class="space-y-4">
      <UAlert
        color="success"
        variant="subtle"
        icon="i-heroicons-check-circle"
        :description="$t('user_settings.two_factor.enabled_alert')"
      />

      <div class="flex gap-2">
        <UButton
          color="neutral"
          variant="outline"
          @click="showRegenerateModal = true"
        >
          {{ $t('user_settings.two_factor.regenerate_codes_button') }}
        </UButton>
        <UButton
          color="neutral"
          variant="outline"
          @click="showDisableModal = true"
        >
          {{ $t('user_settings.two_factor.disable_button') }}
        </UButton>
      </div>
    </div>

    <!-- Enable 2FA Flow -->
    <TwoFactorEnableFlow
      v-else
      :enabling="enabling2FA"
      :confirming="confirming2FA"
      :secret="twoFactorSecret"
      :qr-code="twoFactorQrCode"
      @enable="enableTwoFactor"
      @confirm="confirmTwoFactor"
    />

    <!-- Recovery Codes Modal (shown after setup or regeneration) -->
    <RecoveryCodesModal
      :show="showRecoveryCodesModal"
      :codes="recoveryCodesList"
      :just-regenerated="justRegenerated"
      @close="closeRecoveryCodesModal"
      @copy="copyRecoveryCodes"
    />

    <!-- Regenerate Recovery Codes Modal -->
    <RegenerateRecoveryCodesModal
      :show="showRegenerateModal"
      :loading="regeneratingCodes"
      @close="closeRegenerateModal"
      @regenerate="handleRegenerateRecoveryCodes"
    />

    <!-- Disable 2FA Modal -->
    <DisableTwoFactorModal
      :show="showDisableModal"
      :loading="disabling2FA"
      @close="closeDisableModal"
      @disable="disableTwoFactor"
    />
  </div>
</template>

<script setup>
import { authApi } from '~/api/auth'
import TwoFactorEnableFlow from './TwoFactorEnableFlow.vue'
import RecoveryCodesModal from './RecoveryCodesModal.vue'
import DisableTwoFactorModal from './DisableTwoFactorModal.vue'
import RegenerateRecoveryCodesModal from './RegenerateRecoveryCodesModal.vue'

const alert = useAlert()
const { t } = useI18n()
const auth = useAuth()
const { data: user } = auth.user()

// Two-Factor Authentication
const twoFactorEnabled = computed(() => user.value?.two_factor_enabled ?? false)
const twoFactorSecret = ref(null)
const twoFactorQrCode = ref(null)
const recoveryCodesList = ref([])
const showRecoveryCodesModal = ref(false)
const showDisableModal = ref(false)
const showRegenerateModal = ref(false)

const enabling2FA = ref(false)
const confirming2FA = ref(false)
const disabling2FA = ref(false)
const regeneratingCodes = ref(false)
const justRegenerated = ref(false)

const enableTwoFactor = async () => {
  enabling2FA.value = true
  try {
    const response = await authApi.twoFactor.enable()
    twoFactorSecret.value = response.secret
    twoFactorQrCode.value = response.qr_code
  } catch (error) {
    alert.error(error.response?._data?.message || t('user_settings.two_factor.enable_error'))
  } finally {
    enabling2FA.value = false
  }
}

const confirmTwoFactor = async (code) => {
  confirming2FA.value = true
  try {
    const response = await authApi.twoFactor.confirm({ code })
    
    recoveryCodesList.value = response.recovery_codes || []
    twoFactorSecret.value = null
    twoFactorQrCode.value = null
    
    // Refresh user data
    await auth.invalidateUser()
    
    // Show recovery codes modal automatically after enabling
    justRegenerated.value = false
    showRecoveryCodesModal.value = true
    
    alert.success(t('user_settings.two_factor.enable_success'))
  } catch (error) {
    alert.error(error.response?._data?.message || error.response?._data?.errors?.code?.[0] || t('user_settings.two_factor.invalid_code'))
  } finally {
    confirming2FA.value = false
  }
}

const disableTwoFactor = async (code) => {
  disabling2FA.value = true
  try {
    await authApi.twoFactor.disable({ code })
    
    showDisableModal.value = false
    
    // Refresh user data
    await auth.invalidateUser()
    
    alert.success(t('user_settings.two_factor.disable_success'))
  } catch (error) {
    alert.error(error.response?._data?.message || error.response?._data?.errors?.code?.[0] || t('user_settings.two_factor.invalid_code'))
  } finally {
    disabling2FA.value = false
  }
}

const closeRecoveryCodesModal = () => {
  recoveryCodesList.value = []
  justRegenerated.value = false
  showRecoveryCodesModal.value = false
}

const closeRegenerateModal = () => {
  showRegenerateModal.value = false
}

const closeDisableModal = () => {
  showDisableModal.value = false
}

const handleRegenerateRecoveryCodes = async (data) => {
  regeneratingCodes.value = true
  try {
    const response = await authApi.twoFactor.regenerateRecoveryCodes(data)
    recoveryCodesList.value = response.recovery_codes || []
    justRegenerated.value = true
    showRegenerateModal.value = false
    showRecoveryCodesModal.value = true
    alert.success(t('user_settings.two_factor.regenerate_success'))
  } catch (error) {
    alert.error(error.response?._data?.message || error.response?._data?.errors?.code?.[0] || t('user_settings.two_factor.regenerate_error'))
  } finally {
    regeneratingCodes.value = false
  }
}

const { copy: copyToClipboard } = useClipboard()

const copyRecoveryCodes = () => {
  // Extract just the codes from objects
  const codes = recoveryCodesList.value.map(item => 
    typeof item === 'string' ? item : item.code
  )
  const codesText = codes.join('\n')
  copyToClipboard(codesText)
  alert.success(t('user_settings.two_factor.codes_copied'))
}
</script>

