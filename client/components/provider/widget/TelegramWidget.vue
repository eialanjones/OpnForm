<template>
  <div
    id="widget_login"
    class="flex flex-col gap-4"
  >
    <i18n-t
      keypath="app_shell.telegram_widget.description"
      tag="p"
      scope="global"
      class="text-sm text-neutral-500"
    >
      <template #telegram>
        <a
          href="https://telegram.org"
          target="_blank"
          class="text-primary-500 hover:underline"
        >Telegram</a>
      </template>
    </i18n-t>
    <div class="flex justify-center">
      <UButton
        :disabled="!botId"
        icon="i-mdi-telegram"
        @click.prevent="handleAuth"
      >
        {{ $t('app_shell.telegram_widget.login_button') }}
      </UButton>
    </div>
  </div>
</template>

<script setup>
const { t } = useI18n()

defineProps({
  service: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['auth-data'])

const botId = computed(() => useFeatureFlag('services.telegram.bot_id'))

const loadTelegramWidget = () => {
  if (!botId.value) return

  const script = document.createElement('script')
  script.async = true
  script.src = 'https://telegram.org/js/telegram-widget.js'
  document.head.appendChild(script)
}

const handleAuth = () => {
  if (window.Telegram?.Login) {
    window.Telegram.Login.auth(
      { bot_id: botId.value, request_access: 'write' },
      (data) => {
        // Include intent for integration flow
        emit('auth-data', { ...data, intent: 'integration' })
      }
    )
  } else {
    useAlert().error(t('app_shell.telegram_widget.unavailable'))
  }
}

onMounted(() => {
  loadTelegramWidget()
})
</script>

