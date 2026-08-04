<template>
  <UModal
    v-model:open="isOpen"
    :ui="{ content: 'sm:max-w-5xl' }"
    title=""
    :close="false"
  >
    <template #body>
      <div class="overflow-hidden">
        <SlidingTransition
          :style="transitionContainerStyle"
          direction="horizontal"
          :step="currentStep"
          :speed="transitionDurationMs"
        >
          <div
            :key="currentStep"
            class="w-full"
          >
            <div
              v-if="currentStep === 1"
              key="step1"
              class="flex flex-col items-center px-4 rounded-2xl relative"
              ref="step1Ref"
            >
              <main class="flex flex-col mt-4 max-w-full text-center w-[591px] max-md:mt-10">
                <img
                  src="/img/subscription-modal-icon.svg"
                  :alt="$t('marketing.subscription_modal.icon_alt')"
                  class="self-center max-w-full aspect-[0.98] w-[107px]"
                >
                <section class="flex flex-col mt-2 max-md:max-w-full">
                  <h1 class="text-2xl font-bold tracking-tight leading-9 text-slate-800 max-md:max-w-full">
                    {{ modalTitle }}
                  </h1>
                  <p class="mt-4 text-base leading-6 text-slate-500 max-md:max-w-full">
                    {{ modalDescription }}
                  </p>
                </section>
              </main>
              <div class="mt-8 mb-4 flex items-center justify-center">
                <MonthlyYearlySelector
                  v-model="isYearly"
                />
              </div>
              <section class="flex flex-col w-full max-w-[800px] max-md:max-w-full">
                <div class="bg-white max-md:max-w-full">
                  <div class="flex gap-2 max-md:flex-col max-md:gap-0">
                    <article
                      v-if="!isSubscribed"
                      class="flex flex-col w-6/12 max-md:ml-0 max-md:w-full m-auto"
                    >
                      <div class="flex flex-col grow justify-between p-6 w-full bg-blue-50 rounded-2xl max-md:px-5 max-md:mt-2">
                        <div class="flex flex-col items-center">
                          <div class="flex gap-2 py-px">
                            <h2 class="my-auto text-xl font-semibold tracking-tighter leading-5 text-slate-900">
                              {{ $t('marketing.subscription_modal.plan_pro') }}
                            </h2>
                            <span
                              v-if="isYearly"
                              class="justify-center px-2 py-1 text-xs font-semibold tracking-wide text-center text-emerald-600 uppercase bg-emerald-50 rounded-md"
                            >
                              {{ $t('marketing.billing_period.save_20') }}
                            </span>
                          </div>
                          <div class="flex flex-col justify-end mt-4 leading-[100%]">
                            <p class="text-2xl font-semibold tracking-tight text-slate-900 text-center">
                              <template v-if="isYearly">
                                $16
                              </template>
                              <template v-else>
                                $19
                              </template>
                            </p>
                            <p class="text-xs text-slate-500">
                              {{ isYearly ? $t('marketing.subscription_modal.per_month_billed_yearly') : $t('marketing.subscription_modal.per_month_billed_monthly') }}
                            </p>
                          </div>
                        </div>
                        <TrackClick
                          v-if="!user?.is_subscribed"
                          name="upgrade_modal_start_trial"
                          :properties="{plan: 'default', period: isYearly?'yearly':'monthly'}"
                          class="w-full"
                        >
                          <UButton
                            class="relative border border-white border-opacity-20 h-10 inline-flex px-4 items-center rounded-lg text-sm font-semibold w-full justify-center mt-4"
                            @click.prevent="onSelectPlan('default')"
                            :label="$t('marketing.pricing_table.get_pro')"
                          />
                        </TrackClick>
                        <UButton
                          v-else
                          :loading="billingLoading"
                          :to="{ name: 'redirect-billing-portal' }"
                          target="_blank"
                          class="relative border border-white border-opacity-20 h-10 inline-flex px-4 items-center rounded-lg text-sm font-semibold w-full justify-center mt-4"
                          :label="$t('marketing.subscription_modal.manage_plan')"
                        />
                      </div>
                    </article>
                  </div>
                </div>
              </section>
              <section class="flex flex-col self-stretch mt-12 max-md:mt-10 max-md:max-w-full">
                <div class="justify-center max-md:pr-5 max-md:max-w-full">
                  <div class="flex gap-5 max-md:flex-col max-md:gap-0">
                    <article class="flex flex-col w-[33%] max-md:ml-0 max-md:w-full">
                      <div class="flex flex-col grow text-base leading-6 text-slate-500 max-md:mt-10">
                        <Icon
                          name="mdi:star-outline"
                          class="w-5 h-5 text-blue-500"
                        />
                        <p class="mt-2">
                          <strong class="font-semibold text-slate-800">{{ $t('marketing.subscription_modal.features.branding_title') }}</strong>
                          <span class="text-slate-500"> {{ $t('marketing.subscription_modal.features.branding_description') }}</span>
                        </p>
                      </div>
                    </article>
                    <article class="flex flex-col ml-5 w-[33%] max-md:ml-0 max-md:w-full">
                      <div class="flex flex-col grow text-base leading-6 text-slate-500 max-md:mt-10">
                        <Icon
                          name="ion:brush-outline"
                          class="w-5 h-5 text-blue-500"
                        />
                        <p class="mt-2">
                          <strong class="font-semibold text-slate-800">{{ $t('marketing.subscription_modal.features.customization_title') }}</strong>
                          <span class="text-slate-500"> {{ $t('marketing.subscription_modal.features.customization_description') }}</span>
                        </p>
                      </div>
                    </article>
                    <article class="flex flex-col  w-[33%] max-md:ml-0 max-md:w-full">
                      <div class="flex flex-col grow text-base leading-6 text-slate-500 max-md:mt-10">
                        <Icon
                          name="icons8:upload-2"
                          class="w-5 h-5 text-blue-500"
                        />
                        <p class="mt-2">
                          <strong class="font-semibold text-slate-800">{{ $t('marketing.subscription_modal.features.uploads_title') }}</strong>
                          <span class="text-slate-500"> {{ $t('marketing.subscription_modal.features.uploads_description') }}</span>
                        </p>
                      </div>
                    </article>
                  </div>
                </div>
                <div class="justify-center mt-12 max-md:pr-5 max-md:mt-10 max-md:max-w-full">
                  <div class="flex gap-5 max-md:flex-col max-md:gap-0">
                    <article class="flex flex-col w-[33%] max-md:ml-0 max-md:w-full">
                      <div class="flex flex-col grow text-base leading-6 text-slate-500 max-md:mt-10">
                        <Icon
                          name="heroicons:bell"
                          class="w-5 h-5 text-blue-500"
                        />
                        <p class="mt-2">
                          <strong class="font-semibold text-slate-800">{{ $t('marketing.subscription_modal.features.integrations_title') }}</strong>
                          <span class="text-slate-500"> {{ $t('marketing.subscription_modal.features.integrations_description') }}</span>
                        </p>
                      </div>
                    </article>
                    <article class="flex flex-col ml-5 w-[33%] max-md:ml-0 max-md:w-full">
                      <div class="flex flex-col grow text-base leading-6 text-slate-500 max-md:mt-10">
                        <Icon
                          name="heroicons:globe-alt"
                          class="w-5 h-5 text-blue-500"
                        />
                        <p class="mt-2">
                          <strong class="font-semibold text-slate-800">{{ $t('marketing.subscription_modal.features.domain_title') }}</strong>
                          <span class="text-slate-500"> {{ $t('marketing.subscription_modal.features.domain_description') }}</span>
                        </p>
                      </div>
                    </article>
                    <article class="flex flex-col ml-5 w-[33%] max-md:ml-0 max-md:w-full">
                      <div class="flex flex-col grow text-base leading-6 text-slate-500 max-md:mt-10">
                        <Icon
                          name="mdi:pencil-outline"
                          class="w-5 h-5 text-blue-500"
                        />
                        <p class="mt-2">
                          <strong class="font-semibold text-slate-800">{{ $t('marketing.subscription_modal.features.editable_title') }}</strong>
                          <span class="text-slate-500"> {{ $t('marketing.subscription_modal.features.editable_description') }}</span>
                        </p>
                      </div>
                    </article>
                  </div>
                </div>
              </section>
              <footer
                class="justify-center py-1.5 mt-12 text-base font-medium leading-6 text-center text-blue-500 max-md:mt-10"
              >
                <UButton
                  class="font-bold"
                  :to="{ name: 'pricing' }"
                  target="_blank"
                  trailing-icon="heroicons:arrow-small-right"
                  variant="link"
                  :label="$t('marketing.subscription_modal.see_full_comparison')"
                />
              </footer>
            </div>
            <section
              v-else-if="currentStep === 2"
              key="step2"
              class="flex flex-col items-center px-6 pb-4 bg-white rounded-2xl w-full"
            >
              <div class="flex gap-2 max-md:flex-wrap">
                <div class="flex justify-center items-center p-2 rounded-[1000px]">
                  <Icon
                    name="heroicons:chevron-left-16-solid"
                    class="h-6 w-6 cursor-pointer"
                    @click.prevent="goBackToStep1"
                  />
                </div>
                <h1 class="flex-1 my-auto text-xl font-bold leading-8 text-center text-slate-800 max-md:max-w-full">
                  {{ isSubscribed ? $t('marketing.subscription_modal.confirm_upgrade') : $t('marketing.subscription_modal.confirm_subscription') }}
                </h1>
              </div>
              <div class="flex-grow w-full max-w-sm">
                <div
                  v-if="!isSubscribed"
                  class="bg-blue-50 rounded-md p-4 border border-blue-200 flex flex-col my-4 gap-1"
                >
                  <div class="flex w-full">
                    <p class="text-blue-500 capitalize font-medium flex-grow">
                      {{ $t('marketing.subscription_modal.plan_line', { plan: currentPlan == 'default' ? $t('marketing.subscription_modal.plan_pro') : $t('marketing.subscription_modal.plan_team') }) }}
                    </p>
                    <UBadge
                      :color="isYearly?'success':'warning'"
                      variant="subtle"
                    >
                      {{ !isYearly ? $t('marketing.subscription_modal.no_discount') : $t('marketing.subscription_modal.discount_applied') }}
                    </UBadge>
                  </div>

                  <p class="text-sm leading-5 text-slate-500">
                    <span
                      v-if="isYearly"
                      class="font-medium line-through mr-2"
                      v-text="'$19'"
                    />
                    <span
                      class="font-medium"
                      :class="{'text-green-700':isYearly}"
                      v-text="isYearly ? '$16' : '$19'"
                    />
                    <span
                      class="text-xs"
                      :class="{'text-green-700':isYearly}"
                    >
                      {{ isYearly ? $t('marketing.subscription_modal.per_month_billed_yearly') : $t('marketing.subscription_modal.per_month_billed_monthly') }}
                    </span>
                  </p>
                  <div v-if="shouldShowUpsell">
                    <v-form size="sm">
                      <toggle-switch-input
                        name=""
                        v-model="isYearly"
                        :label="$t('marketing.subscription_modal.yearly_upsell')"
                        size="sm"
                        wrapper-class="mb-0"
                      />
                    </v-form>
                  </div>
                </div>
                <text-input
                  ref="companyName"
                  :label="$t('marketing.subscription_modal.company_name_label')"
                  name="name"
                  :required="true"
                  :form="form"
                  :help="$t('marketing.subscription_modal.company_name_help')"
                />
                <text-input
                  :label="$t('marketing.subscription_modal.invoicing_email_label')"
                  name="email"
                  native-type="email"
                  :required="true"
                  :form="form"
                  :help="$t('marketing.subscription_modal.invoicing_email_help')"
                />
                <div
                  class="flex gap-2 mt-6 w-full"
                >
                <TrackClick
                  name="upgrade_modal_confirm_submit"
                  class="grow flex"
                  :properties="{plan: currentPlan.value, period: isYearly?'yearly':'monthly'}"
                >
                    <UButton
                        block
                      size="md"
                      class="w-auto flex-grow"
                      :loading="form.busy || loading"
                      :disabled="form.busy || loading"
                      :to="checkoutUrl"
                      target="_blank"
                    >
                      {{ isSubscribed ? $t('common.actions.upgrade') : $t('marketing.subscription_modal.subscribe') }}
                    </UButton>
                  </TrackClick>
                <UButton
                    size="md"
                    color="neutral"
                    variant="outline"
                    @click="goBackToStep1"
                  >
                    {{ $t('common.actions.back') }}
                  </UButton>
                </div>
              </div>
            </section>
          </div>
        </SlidingTransition>
      </div>
    </template>
  </UModal>
</template>

<script setup>
import SlidingTransition from '~/components/global/transitions/SlidingTransition.vue'
import TrackClick from '~/components/global/TrackClick.vue'

import { useCheckoutUrl } from '@/composables/components/stripe/useCheckoutUrl'
import { authApi } from '~/api'
import { computed, watchEffect } from 'vue'
import { useElementSize } from '@vueuse/core'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  modal_title: {
    type: String,
    default: null
  },
  modal_description: {
    type: String,
    default: null
  },
  plan: {
    type: String,
    default: 'default'
  },
  yearly: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close'])

const { t } = useI18n()
const router = useRouter()

const modalTitle = computed(() => props.modal_title || t('marketing.subscription_modal.default_title'))
const modalDescription = computed(() => props.modal_description || t('marketing.subscription_modal.default_description'))

const currentPlan = ref(props.plan)
const currentStep = ref(1)
const isYearly = ref(props.yearly)
const loading = ref(false)
const billingLoading = ref(false)
const shouldShowUpsell = ref(false)
const form = useForm({
  name: '',
  email: ''
})

const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('close', value)
})

const closeModal = () => {
  isOpen.value = false
}

const subscribeBroadcast = useBroadcastChannel('subscribe')
const broadcastData = subscribeBroadcast.data
const confetti = useConfetti()
const { isAuthenticated: authenticated } = useIsAuthenticated()
const { data: user } = useAuth().user()
const isSubscribed = computed(() => user.value.is_pro)
const currency = 'usd'

const transitionDurationMs = 300
// Measure Step 1 height and apply as fixed height to the container
const step1Ref = ref(null)
const { height: step1Height } = useElementSize(step1Ref)
const cachedStep1Height = ref(0)
watchEffect(() => {
  if (step1Height?.value) {
    cachedStep1Height.value = step1Height.value
  }
})
const transitionContainerStyle = computed(() => {
  const h = cachedStep1Height.value
  return h ? { height: h + 'px' } : {}
})

const checkoutUrl = useCheckoutUrl(
  computed(() => form.name),
  computed(() => form.email),
  currentPlan,
  isYearly,
  currency
)

// When opening modal with a plan already (and user not subscribed yet) - skip first step
watch(() => props.modelValue, () => {
  currentStep.value = 1
  
  // Update user data when modal opens
  if (props.modelValue) {
    updateUser()
    
    if (props.plan) {
      if (user.value.is_subscribed) {
        return
      }
      isYearly.value = props.yearly
      shouldShowUpsell.value = !isYearly.value
      currentPlan.value = props.plan
      currentStep.value = 2
    }
  }
})

watch(broadcastData, () => {
  if (import.meta.server || !props.modelValue || !broadcastData.value || !broadcastData.value.type) {
    return
  }

  if (broadcastData.value.type === 'success') {
    // Now we need to reload workspace and user
          authApi.user.get().then((_userData) => {
       useAuth().invalidateUser()

      try {
        const eventData = {
          plan: user.value.has_enterprise_subscription ? 'enterprise' : 'pro'
        }
        useAmplitude().logEvent('subscribed', eventData)
        useCrisp().pushEvent('subscribed', eventData)
        useGtm().trackEvent({ event: 'subscribed', ...eventData })
        if (import.meta.client && window.rewardful) {
          window.rewardful('convert', { email: user.value.email })
        }
        console.log('Subscription registered 🎊')
      } catch (error) {
        console.error('Failed to register subscription event 😔',error)
      }
    })
    const { invalidateAll } = useWorkspaces()
    invalidateAll() // Refresh all workspace data

    if (user.value.has_enterprise_subscription) {
      useAlert().success(t('marketing.subscription_modal.success_team'))
    } else {
      useAlert().success(t('marketing.subscription_modal.success_pro'))
    }
    confetti.play()
    closeModal()
  } else {
    useAlert().error(t('marketing.subscription_modal.confirm_error'))
    currentStep.value = 1
    shouldShowUpsell.value = true
  }
  subscribeBroadcast.close()
})

onMounted(() => {
  updateUser()
})

// Update form with user data - sets company name to user name by default
const updateUser = () => {
  if (user.value) {
    // Set company name to user name by default
    if (user.value.name && !form.name) {
      form.name = user.value.name
    }
    
    // Set email if available
    if (user.value.email && !form.email) {
      form.email = user.value.email
    }
  }
}

// Watch for user changes
watch(user, () => {
  updateUser()
}, { immediate: true })

const onSelectPlan = (planName) => {
  if (!authenticated.value) {
    closeModal()
    router.push({ name: "register" })
    return
  }

  loading.value = false
  currentPlan.value = planName
  shouldShowUpsell.value = !isYearly.value
  currentStep.value = 2
}

const goBackToStep1 = () => {
  currentStep.value = 1
}
</script>
