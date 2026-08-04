<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <h3 class="text-lg font-medium text-neutral-900">{{ $t('user_settings.billing.heading') }}</h3>
        <p class="mt-1 text-sm text-neutral-500">
          {{ $t('user_settings.billing.description') }}
        </p>
      </div>
    </div>

    <!-- Billing Management -->
    <template v-if="user.has_customer_id">
      <div class="space-y-4">
        <i18n-t
          v-if="usersCount"
          keypath="user_settings.billing.users_count"
          scope="global"
          tag="p"
          class="text-neutral-600"
        >
          <template #users>
            <span class="font-medium">{{ $t('user_settings.billing.users_count_value', { count: usersCount }, usersCount) }}</span>
          </template>
        </i18n-t>

        <div class="flex flex-wrap gap-3">
          <UButton
            icon="i-heroicons-credit-card"
            :loading="billingLoading"
            :to="{ name: 'redirect-billing-portal' }"
            target="_blank"
          >

            {{ $t('user_settings.billing.portal_button') }}
          </UButton>
        </div>
      </div>
    </template>

    <!-- AppSumo Billing -->
    <AppSumoBilling />
  </div>
</template>

<script setup>
import AppSumoBilling from "../../vendor/appsumo/AppSumoBilling.vue"
import { billingApi } from '~/api'

const alert = useAlert()

const { data: user } = useAuth().user()
const billingLoading = ref(false)
const usersCount = ref(0)

onMounted(() => {
  loadUsersCount()
})

const loadUsersCount = () => {
      billingApi.getUsersCount()
    .then((data) => {
      usersCount.value = data.count
    })
    .catch((error) => {
      alert.error(error.data.message)
    })
}

</script> 