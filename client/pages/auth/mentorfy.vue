<template>
  <div class="flex min-h-screen items-center justify-center">
    <div class="text-center">
      <Loader v-if="!error" class="mx-auto h-6 w-6 text-nt-blue" />
      <p v-if="!error" class="mt-4 text-sm text-gray-500">
        {{ $t('auth.sso.signing_in') }}
      </p>

      <div v-else class="max-w-sm">
        <p class="text-sm text-red-600">{{ error }}</p>
        <p class="mt-2 text-xs text-gray-500">
          {{ $t('auth.sso.error_hint') }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * Ponte de SSO da Mentorfy.
 *
 * Recebe o token curto na query, troca por um JWT do Forms Mentorfy e grava no mesmo
 * cookie que o login normal usa — daí para frente a aplicação não sabe (nem
 * precisa saber) que a sessão veio de fora.
 *
 * O token é de uso único: recarregar esta página falha de propósito. Por isso a
 * mensagem de erro manda voltar para a Mentorfy em vez de sugerir refresh.
 */
definePageMeta({
  layout: 'default',
  middleware: [], // pública: é justamente aqui que a sessão nasce
  skipAuthBootstrap: true,
})

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const appStore = useAppStore()
const queryClient = useQueryClient()
const { handleAuthSuccess } = useAuthFlow()
const error = ref(null)

onMounted(() => {
  // Mentorfy is authoritative for this navigation. Drop any previous OpnForm
  // identity before exchanging the signed token so users can safely switch
  // accounts in the same browser and no stale modal/cache survives.
  authStore.clearTokens()
  authStore.updateUser(null)
  queryClient.clear()
  appStore.resetAuthModals()

  const token = route.query.token

  if (!token) {
    error.value = t('auth.sso.invalid_link')
    return
  }

  opnFetch('/auth/mentorfy/exchange', {
      method: 'POST',
      body: { token },
      // This request authenticates with the short Mentorfy token itself. A
      // previous OpnForm bearer would make guest middleware reject it and a
      // 401 here is an SSO error, not expiry of the new browser session.
      auth: false,
      handleUnauthorized: false,
    })
    .then((response) => handleAuthSuccess(response, 'mentorfy-sso'))
    .then(() => {
      // Limpa o token da barra de endereço antes de seguir: ele já foi consumido,
      // mas não há motivo para deixá-lo no histórico do browser.
      const target = sanitizeRedirect(route.query.redirect) || '/home'
      return router.replace(target)
    })
    .catch((e) => {
      error.value = e?.data?.message || t('auth.sso.failed')
    })
})

/** Só caminho relativo. "//evil.com" seria absoluto para o browser. */
function sanitizeRedirect(value) {
  if (typeof value !== 'string') return null
  if (!value.startsWith('/') || value.startsWith('//')) return null
  return value
}
</script>
