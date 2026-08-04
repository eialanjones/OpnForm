<template>
  <div
    v-if="field.type === 'payment'"
    class="px-4"
  >
    <EditorSectionHeader
      icon="i-heroicons-credit-card-20-solid"
      :title="$t('form_fields.payment.title')"
    />

    <select-input
      name="currency"
      :label="$t('form_fields.payment.currency_label')"
      :options="currencyList"
      :form="field"
      :required="true"
      :searchable="true"
      :disabled="stripeAccounts.length === 0"
    />
    <MentionInput
      class="mt-4"
      name="amount"
      :label="$t('form_fields.payment.amount_label')"
      native-type="number"
      :form="field"
      :mentions="form.properties"
      :required="true"
      :disabled="stripeAccounts.length === 0"
    />
    <MentionInput
      class="mt-4"
      name="prefill_name"
      :label="$t('form_fields.payment.name_on_card_label')"
      :form="field"
      :mentions="form.properties"
      :disabled="stripeAccounts.length === 0"
    />
    <MentionInput
      class="mt-4"
      name="prefill_email"
      :label="$t('form_fields.payment.billing_email_label')"
      :form="field"
      :mentions="form.properties"
      :disabled="stripeAccounts.length === 0"
    />
    
    <div v-if="stripeAccounts.length > 0">
      <select-input
        class="mt-4"
        name="stripe_account_id"
        :label="$t('form_fields.payment.stripe_account_label')"
        :options="stripeAccounts"
        :form="field"
        :required="true"
      />
      <p class="mt-4 text-sm text-center text-bold">
        {{ $t('form_fields.payment.or') }}
      </p>
    </div>
    <UButton
      class="mt-4"
      icon="i-heroicons-arrow-right"
      block
      trailing
      :loading="stripeLoading"
      @click.prevent="connectStripe"
    >
      {{ $t('form_fields.payment.connect_stripe') }}
    </UButton>
    <p class="text-sm text-neutral-500 mt-3">
      <a
        target="#"
        class="text-neutral-500 cursor-pointer text-sm"
        @click.prevent="crisp.openHelpdeskArticle('how-to-collect-payment-svig30')"
      >
        <Icon
          name="heroicons:information-circle-16-solid"
          class="h-3 w-3 mt-1"
        />
        {{ $t('form_fields.payment.learn_more') }}
      </a>
    </p>
  </div>
</template>

<script setup>
import EditorSectionHeader from '~/components/open/forms/components/form-components/EditorSectionHeader.vue'
import stripeCurrencies from "~/data/stripe_currencies.json"
import { useWindowMessage, WindowMessageTypes } from '~/composables/useWindowMessage'

const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  form: {
    type: Object,
    required: true
  }
})

const { t } = useI18n()
const crisp = useCrisp()
const oAuth = useOAuth()
const { data: providersData, refetch} = oAuth.providers()
const stripeLoading = ref(false)

// Setup window message listener for Stripe connection
const { listen, cleanup } = useWindowMessage()

onMounted(async () => {
  await oAuth.fetchOAuthProviders()

  if(props.field?.currency === undefined || props.field?.currency === null) {
    props.field.currency = 'USD'
  }
  if(props.field?.amount === undefined || props.field?.amount === null) {
    props.field.amount = 10
  }
  
  // Auto-select first Stripe account if none is selected
  if (!props.field.stripe_account_id && stripeAccounts.value.length > 0) {
    props.field.stripe_account_id = stripeAccounts.value[0].value
  }

  // Listen for Stripe connection message
  listen(async () => {
    await refetch()
    // Auto-select first Stripe account after refresh if one isn't already selected (or maybe always select the newest? for now, first)
    if (stripeAccounts.value.length > 0) {
      props.field.stripe_account_id = stripeAccounts.value[0].value
    }
    useAlert().success(t('form_fields.payment.accounts_updated'))
  }, { 
    useMessageChannel: false, 
    acknowledge: false 
  }, WindowMessageTypes.OAUTH_PROVIDER_CONNECTED)
})

onUnmounted(() => {
  // Cleanup listener (optional, as useWindowMessage handles it)
  cleanup()
})

const stripeAccounts = computed(() => (providersData.value || []).filter((item) => item.provider === 'stripe').map((item) => ({
  name: item.name + (item.email ? ' (' + item.email + ')' : ''),
  value: item.id
})))

const currencyList = computed(() => {
  return stripeCurrencies.map((item) => ({
    name: item.name,
    value: item.code
  }))
})

const connectStripe = () => {
  stripeLoading.value = true
  oAuth.connect('stripe', false, true, true)
  setTimeout(() => {
    stripeLoading.value = false
  }, 10000)
}
</script>