<template>
  <div>
    <div class="flex mt-6">
      <div class="w-full md:w-2/3 md:mx-auto md:max-w-md">
        <img
          :alt="$t('marketing.not_found_page.image_alt')"
          src="/img/icons/plant.png"
          class="w-56 mb-5"
        >

        <h1 class="mb-6 font-semibold text-3xl text-neutral-900">
          {{ $t('marketing.not_found_page.title') }}
        </h1>

        <div class="links">
          <UButton
            :to="{ name: 'index' }"
            class="hover:underline"
          >
            {{ $t('marketing.not_found_page.go_home') }}
          </UButton>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const { t } = useI18n()

definePageMeta({
  middleware: ['root-redirect']
})

useOpnSeoMeta({
  title: t('marketing.not_found_page.meta_title'),
})

const event = useRequestEvent()
setResponseStatus(event, 404, 'Page Not Found')
</script>