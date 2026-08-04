<template>
  <main class="flex justify-center items-center px-16 w-full max-md:px-5 max-md:max-w-full">
    <section class="flex flex-col mb-4 w-full max-w-[1080px] max-md:mt-10 max-md:max-w-full">
      <div class="text-center mx-auto">
        <img
          src="/img/icons/404.png"
          :alt="$t('marketing.not_found_form.image_alt')"
          class="w-full max-w-[250px] mb-5 mx-auto"
        >
        <h1 class="text-dark text-2xl md:text-4xl leading-none font-medium text-center pt-4 md:pt-0 mx-auto md:max-w-full">
          {{ $t('marketing.not_found_form.title') }}
        </h1>
        <p class="text-neutral-500 mt-3 max-w-xl mx-auto">
          {{ $t('marketing.not_found_form.description') }}
        </p>

        <h3 class="my-8 text-dark text-xl font-semibold text-center md:pt-0 mx-auto md:max-w-full">
          {{ $t('marketing.not_found_form.what_can_you_do') }}
        </h3>
        <div class="mt-8 grid grid-cols-2 md:grid-cols-5 gap-4">
          <NuxtLink
            v-for="(action, index) in actions"
            :key="index"
            :to="action.to"
            :href="action.href"
            class="bg-blue-100/50 rounded-lg px-4 py-6 text-center text-neutral-700 hover:bg-blue-100 flex flex-col items-center hover:no-underline"
            @click="action.onClick ? action.onClick() : null"
          >
            <Icon
              :name="action.icon"
              class="w-8 h-8 mb-2 text-blue-500"
            />
            <span class="font-medium">
              {{ action.text }}
            </span>
          </NuxtLink>
        </div>
        
        <div class="mt-10 text-neutral-700">
          <h3 class="mt-8 text-dark text-xl font-semibold text-center md:pt-0 mx-auto md:max-w-full">
            {{ $t('marketing.not_found_form.more_ways_title') }}
          </h3>
          <p class="mt-4 text-neutral-500">
            {{ $t('marketing.not_found_form.more_ways_description') }}
          </p>
          <ul class="mt-4 space-y-2 text-sm">
            <li>
              {{ $t('marketing.not_found_form.why_choose_label') }}
              <NuxtLink
                class="text-blue-500 hover:text-blue-600"
                :to="{ name: 'ai-form-builder' }"
              >
                {{ $t('marketing.not_found_form.why_choose_link') }}
              </NuxtLink>
            </li>
            <li>
              {{ $t('marketing.not_found_form.templates_label') }}
              <NuxtLink
                class="text-blue-500 hover:text-blue-600"
                :to="{ name: 'templates' }"
              >
                {{ $t('marketing.not_found_form.templates_link') }}
              </NuxtLink>
            </li>
            <li>
              {{ $t('marketing.not_found_form.help_center_label') }}
              <NuxtLink
                class="text-blue-500 hover:text-blue-600"
                to="https://help.opnform.com/en/"
              >
                {{ $t('marketing.not_found_form.help_center_link') }}
              </NuxtLink>
            </li>
          </ul>
        </div>
      </div>

      <div class="mt-20 border-t">
        <OpenFormFooter />
      </div>
    </section>
  </main>
</template>

<script setup>
const crisp = useCrisp()
const { t } = useI18n()

const actions = [
  {
    to: { name: 'forms-create-guest' },
    icon: 'i-heroicons-rocket-launch',
    text: t('marketing.not_found_form.actions.create_form')
  },
  {
    to: { name: 'login' },
    icon: 'i-heroicons-list-bullet',
    text: t('marketing.not_found_form.actions.find_your_forms')
  },
  {
    href: '#',
    icon: 'i-heroicons-book-open',
    text: t('marketing.not_found_form.actions.documentation'),
    onClick: () => crisp.openHelpdesk()
  },
  {
    to: { name: 'index' },
    icon: 'i-heroicons-document-magnifying-glass',
    text: t('marketing.not_found_form.actions.explore')
  },
  {
    href: '#',
    icon: 'i-heroicons-chat-bubble-left-right',
    text: t('marketing.not_found_form.actions.need_assistance'),
    onClick: () => crisp.openChat()
  }
]
</script>
