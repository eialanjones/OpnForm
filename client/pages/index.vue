<template>
  <div>
    <section
      class="bg-gradient-to-b relative from-white to-neutral-100 py-8 sm:py-16"
    >
      <div class="absolute inset-0">
        <img
          class="w-full h-full object-cover object-top"
          src="/img/pages/ai_form_builder/background-pattern.svg"
          :alt="$t('marketing.landing.background_alt')"
        >
      </div>

      <div
        class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto relative -mb-32 md:-mb-52 lg:-mb-72"
      >
        <div class="max-w-4xl mx-auto text-center">
          <h1
            class="text-4xl sm:text-5xl lg:text-6xl font-semibold text-neutral-900 tracking-tight"
          >
            {{ $t('marketing.landing.hero_title_start') }}
            <span
              class="bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-blue-400"
            >{{ $t('marketing.landing.hero_title_highlight') }}</span>
            <br>
            {{ $t('marketing.landing.hero_title_end') }}
          </h1>
          <p
            class="mt-4 sm:mt-5 text-base leading-7 sm:text-xl sm:leading-9 font-medium text-neutral-500"
          >
            {{ $t('marketing.landing.hero_description') }}
            <span class="font-semibold">{{ $t('marketing.landing.hero_description_highlight') }}</span>!
          </p>

          <div class="mt-8 flex justify-center">
            <UButton
              v-if="!authenticated"
              class="mr-1"
              :to="{ name: 'forms-create-guest' }"
              trailing-icon="i-heroicons-arrow-right-20-solid"
              :label="$t('marketing.landing.create_form_cta')"
            />
            <UButton
              v-else
              class="mr-1"
              :to="{ name: 'forms-create' }"
              trailing-icon="i-heroicons-arrow-right-20-solid"
              :label="$t('marketing.landing.create_form_cta')"
            />
          </div>

          <div class="justify-center flex gap-2 mt-10">
            <div class="flex items-center text-neutral-400 text-sm">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="2"
                stroke="currentColor"
                class="w-4 h-4 mr-1 ticks"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M4.5 12.75l6 6 9-13.5"
                />
              </svg>
              <span>{{ $t('marketing.landing.bullets.unlimited_forms') }}</span>
            </div>
            <div class="flex items-center text-neutral-400 text-sm">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="2"
                stroke="currentColor"
                class="w-4 h-4 mr-1 ticks"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M4.5 12.75l6 6 9-13.5"
                />
              </svg>
              <span>{{ $t('marketing.landing.bullets.unlimited_fields') }}</span>
            </div>
            <div class="flex text-neutral-400 text-sm">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="2"
                stroke="currentColor"
                class="w-4 h-4 mr-1 ticks"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M4.5 12.75l6 6 9-13.5"
                />
              </svg>
              <span>{{ $t('marketing.landing.bullets.unlimited_responses') }}</span>
            </div>
          </div>
        </div>

        <div
          class="w-full mt-12 relative px-6 mx-auto max-w-4xl sm:px-10 lg:px-0 z-10 flex items-center justify-center"
        >
          <div
                          class="-m-2 rounded-xl bg-blue-900/5 p-2 backdrop-blur-xs ring ring-inset ring-blue-900/10 lg:-m-4 lg:rounded-2xl lg:p-4 w-full"
          >
            <img
              src="/img/pages/welcome/product-cover.jpg"
              sizes="320px sm:650px lg:896px"
              :alt="$t('marketing.landing.product_screenshot_alt')"
              loading="lazy"
                                class="rounded-sm w-full shadow-2xl ring ring-neutral-900/10"
            >
          </div>
        </div>
      </div>
    </section>

    <div class="flex flex-col bg-neutral-50 dark:bg-notion-dark">
      <div
        class="bg-white dark:bg-notion-dark-light pt-32 md:pt-52 lg:pt-72 pb-8"
      >
        <div class="md:max-w-5xl md:mx-auto w-full">
          <features class="pb-8" />
        </div>
      </div>

      <ai-feature class="bg-white -mb-56" />

      <more-features class="pt-56" />

      <pricing-table
        v-if="useFeatureFlag('billing.enabled')"
        class="pb-20"
        :home-page="true"
      >
        <template #pricing-table>
          <li class="flex gap-x-3">
            <NuxtLink
              :to="{ name: 'pricing' }"
              class="flex gap-3"
            >
              <div class="w-5" />
              {{ $t('marketing.landing.read_more_pricing') }}
            </NuxtLink>
          </li>
        </template>
      </pricing-table>

      <!--      <div class="pt-20 pb-5 text-center bg-white dark:bg-notion-dark-light">-->
      <!--        <h3 class="font-semibold text-3xl">See what people are saying</h3>-->
      <!--        <p class="w-full mt-2 mb-8">-->
      <!--          These are the stories of our customers who have joined us with great pleasure when using this crazy feature.-->
      <!--        </p>-->
      <!--        <testimonials/>-->
      <!--      </div>-->

      <templates-slider class="max-w-full mb-12" />

      <div class="w-full bg-blue-900 p-12 md:p-24 text-center">
        <h4 class="font-semibold text-3xl text-white">
          {{ $t('marketing.landing.cta_title') }}
        </h4>
        <p class="text-neutral-300 my-8">
          {{ $t('marketing.landing.cta_description') }}
        </p>
        <div class="mt-6 flex justify-center">
          <TrackClick
            name="welcome_create_form_click"
          >
            <UButton
              :to="{ name: 'forms-create-guest' }"
              trailing-icon="i-heroicons-arrow-right-20-solid"
              :label="$t('marketing.landing.create_form_cta')"
            />
          </TrackClick>
        </div>

      </div>

      <open-form-footer class="dark:border-t border-t" />
    </div>
  </div>
</template>

<script setup>
import Features from "~/components/pages/welcome/Features.vue"
import MoreFeatures from "../components/pages/welcome/MoreFeatures.vue"
import PricingTable from "../components/pages/pricing/PricingTable.vue"
import AiFeature from "../components/pages/welcome/AiFeature.vue"
import TemplatesSlider from "../components/pages/welcome/TemplatesSlider.vue"
import TrackClick from "~/components/global/TrackClick.vue"
import { useIsAuthenticated } from '~/composables/useAuthFlow'

definePageMeta({
  layout: "default",
  middleware: ['root-redirect']
})

const { isAuthenticated: authenticated } = useIsAuthenticated()
</script>

<style lang="scss" scoped>
.customer-logo-container {
  max-width: 130px;
  width: 100%;
}

.ticks {
  color: #de5d00;
}

@screen md {
  #macbook-video {
    position: absolute;
    max-width: 84.8% !important;
    right: 0px;
    top: 6.8%;
  }
}
</style>
