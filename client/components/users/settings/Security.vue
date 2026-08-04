<template>
  <div class="space-y-8">
    <!-- Password Section -->
    <div class="space-y-4">
      <div>
        <h3 class="text-lg font-medium text-neutral-900">{{ $t('user_settings.security.password_heading') }}</h3>
        <p class="text-sm text-neutral-500 mt-1">
          {{ $t('user_settings.security.password_description') }}
        </p>
      </div>

      <VForm size="sm">
        <form
          @submit.prevent="updatePassword"
        >
          <div class="max-w-sm">
            <TextInput
              :form="passwordForm"
              name="current_password"
              :label="$t('user_settings.security.current_password_label')"
              native-type="password"
              :placeholder="$t('user_settings.security.current_password_placeholder')"
              :required="true"
            />

            <TextInput
              :form="passwordForm"
              name="password"
              :label="$t('user_settings.security.new_password_label')"
              native-type="password"
              :placeholder="$t('user_settings.security.new_password_placeholder')"
              :required="true"
              @focus="isPasswordFocused = true"
              @blur="isPasswordFocused = false"
            />
            <PasswordStrengthIndicator 
              v-show="isPasswordFocused" 
              :password="passwordForm.password" 
            />
            
            <TextInput
              :form="passwordForm"
              name="password_confirmation"
              :label="$t('user_settings.security.confirm_password_label')"
              native-type="password"
              :placeholder="$t('user_settings.security.confirm_password_placeholder')"
              :required="true"
            />
          </div>

          <div class="mt-4">
            <UButton
              type="submit"
              :loading="passwordForm.busy"
              color="primary"
            >
              {{ $t('user_settings.security.update_password_button') }}
            </UButton>
          </div>
        </form>
      </VForm>
    </div>

    <div class="pt-8 border-t border-neutral-200">
      <UsersSettingsTwoFactorAuth />
    </div>
  </div>
</template>

<script setup>
const alert = useAlert()
const { t } = useI18n()

// Password form
const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: ''
})

// Password field focus state
const isPasswordFocused = ref(false)

// Update password
const updatePassword = () => {
  passwordForm
    .patch('/settings/password')
    .then(() => {
      passwordForm.reset()
      alert.success(t('user_settings.security.password_updated'))
    })
    .catch((error) => {
      console.error(error)
    })
}
</script> 