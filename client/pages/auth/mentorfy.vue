<template>
  <div class="flex min-h-screen items-center justify-center">
    <div class="text-center">
      <Loader v-if="!error" class="mx-auto h-6 w-6 text-nt-blue" />
      <p v-if="!error" class="mt-4 text-sm text-gray-500">
        Entrando…
      </p>

      <div v-else class="max-w-sm">
        <p class="text-sm text-red-600">{{ error }}</p>
        <p class="mt-2 text-xs text-gray-500">
          Volte para a Mentorfy e abra a área de formulários novamente.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * Ponte de SSO da Mentorfy.
 *
 * Recebe o token curto na query, troca por um JWT do OpnForm e grava no mesmo
 * cookie que o login normal usa — daí para frente a aplicação não sabe (nem
 * precisa saber) que a sessão veio de fora.
 *
 * O token é de uso único: recarregar esta página falha de propósito. Por isso a
 * mensagem de erro manda voltar para a Mentorfy em vez de sugerir refresh.
 */
definePageMeta({
  layout: 'default',
  middleware: [], // pública: é justamente aqui que a sessão nasce
})

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const error = ref(null)

onMounted(async () => {
  const token = route.query.token

  if (!token) {
    error.value = 'Link de acesso inválido.'
    return
  }

  try {
    const response = await opnFetch('/auth/mentorfy/exchange', {
      method: 'POST',
      body: { token },
    })

    authStore.setToken(response.token, response.expires_in)
    authStore.setUser(response.user)

    // Limpa o token da barra de endereço antes de seguir: ele já foi consumido,
    // mas não há motivo para deixá-lo no histórico do browser.
    const target = sanitizeRedirect(route.query.redirect) || '/home'
    await router.replace(target)
  } catch (e) {
    error.value = e?.data?.message || 'Não foi possível entrar.'
  }
})

/** Só caminho relativo. "//evil.com" seria absoluto para o browser. */
function sanitizeRedirect(value) {
  if (typeof value !== 'string') return null
  if (!value.startsWith('/') || value.startsWith('//')) return null
  return value
}
</script>
