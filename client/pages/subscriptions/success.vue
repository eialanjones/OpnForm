<template>
  <div class="flex flex-col min-h-screen">
    <div
      class="w-full md:max-w-3xl md:mx-auto px-4 mb-10 md:pb-20 md:pt-16 text-center flex-grow"
    >
      <h1 class="text-4xl font-semibold">
        {{ $t('marketing.subscription_success.title') }}
      </h1>
      <h4 class="text-xl mt-6">
        {{ $t('marketing.subscription_success.description') }}
      </h4>
      <div class="text-center">
        <Loader class="h-6 w-6 text-blue-500 mx-auto mt-20" />
      </div>
    </div>
    <open-form-footer />
  </div>
</template>


<script setup>
import { useBroadcastChannel } from '@vueuse/core'
import { authApi } from "~/api"

definePageMeta({
  middleware: 'auth'
})

const { t } = useI18n()

useOpnSeoMeta({
  title: t('marketing.subscription_success.meta_title')
})

const confetti = useConfetti()
const { data: user } = useAuth().user()
const subscribeBroadcast = useBroadcastChannel('subscribe')

const interval = ref(null)

const redirectIfSubscribed = () => {
  if (user.value.is_subscribed) {
    subscribeBroadcast.post({ 'type': 'success' })
    window.close()
  }
}
const checkSubscription = () => {
  // Fetch the user.
              return authApi.user.get().then((_data) => {
     useAuth().invalidateUser()
    redirectIfSubscribed()
  }).catch((error) => {
    console.error(error)
    clearInterval(interval.value)
  })
}

onMounted(() => {
  redirectIfSubscribed()
  interval.value = setInterval(() => checkSubscription(), 5000)
})

onBeforeUnmount(() => {
  clearInterval(interval.value)
})

onUnmounted(() => {
  // stop confettis after 2 sec
  setTimeout(() => {
    confetti.stop()
  }, 2000)
})
</script>