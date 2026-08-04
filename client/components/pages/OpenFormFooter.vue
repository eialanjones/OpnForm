<template>
  <div class="w-full">
    <div class="grid md:grid-cols-3 my-8">
      <div class="flex mt-2 items-center">
        <p class="text-sm text-neutral-600 dark:text-neutral-400 text-center w-full">
          {{ $t('marketing.footer.copyright', { year: currYear, company: opnformConfig.company_name }) }}
          <span v-if="version">
            <br>{{ $t('marketing.footer.version', { version }) }}
          </span>
        </p>
      </div>
      <div class="flex justify-center mt-5 md:mt-0">
        <router-link
          :to="{ name: user ? 'home' : 'index' }"
          class="flex-shrink-0 font-semibold flex items-center"
        >
          <img
            src="/img/logo.svg"
            :alt="$t('marketing.footer.logo_alt')"
            class="w-10 h-10"
          >
          <span class="ml-2 text-xl text-black dark:text-white"> Forms Mentorfy </span>
        </router-link>
      </div>
      <div class="flex justify-center mt-5 md:mt-0">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-4 gap-y-2">
          <a
            :href="opnformConfig.links.help_url"
            target="_blank"
            class="text-neutral-600 dark:text-neutral-400 transition-colors duration-300 hover:text-blue-500"
          >
            {{ $t('marketing.footer.support') }}
          </a>
          <a
            :href="opnformConfig.links.source_code"
            target="_blank"
            class="text-neutral-600 dark:text-neutral-400 transition-colors duration-300 hover:text-blue-500"
          >
            {{ $t('marketing.footer.source_code') }}
          </a>
          <template v-if="!useFeatureFlag('self_hosted')">
            <router-link
              :to="{ name: 'integrations' }"
              class="text-neutral-600 dark:text-neutral-400 transition-colors duration-300 hover:text-blue-500"
            >
              {{ $t('marketing.footer.integrations') }}
            </router-link>
            <router-link
              :to="{ name: 'report-abuse' }"
              class="text-neutral-600 dark:text-neutral-400 transition-colors duration-300 hover:text-blue-500"
            >
              {{ $t('marketing.footer.report_abuse') }}
            </router-link>
            <router-link
              :to="{ name: 'privacy-policy' }"
              class="text-neutral-600 dark:text-neutral-400 transition-colors duration-300 hover:text-blue-500"
            >
              {{ $t('marketing.footer.privacy_policy') }}
            </router-link>

            <router-link
              :to="{ name: 'terms-conditions' }"
              class="text-neutral-600 dark:text-neutral-400 transition-colors duration-300 hover:text-blue-500"
            >
              {{ $t('marketing.footer.terms_conditions') }}
            </router-link>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import opnformConfig from "~/opnform.config.js"

const { data: user } = useAuth().user()
const currYear = ref(new Date().getFullYear())

// Use the reactive version for proper template reactivity
const version = computed(() => useFeatureFlag('version'))
</script>
