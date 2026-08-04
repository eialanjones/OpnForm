<template>
  <div>
    <TrackClick
      name="regenerate_form_link_click"
      :properties="{form_id: form.id, form_slug: form.slug}"
    >
      <UButton
        variant="outline"
        color="neutral"
        icon="i-heroicons-arrow-path"
        @click="showGenerateFormLinkModal = true"
        :label="$t('form_pages.regenerate_link.trigger')"
      />
    </TrackClick>

    <!--  Regenerate form link modal  -->
    <UModal
      v-model:open="isModalOpen"
      :ui="{ content: 'sm:max-w-2xl' }"
    >
      <template #header>
        <div class="flex items-center w-full gap-4 px-2">
          <h2 class="font-semibold">
            {{ $t('form_pages.regenerate_link.modal_title') }}
          </h2>
        </div>
        <UButton
          color="neutral"
          variant="outline"
          icon="i-heroicons-question-mark-circle"
          size="sm"
          @click="crisp.openHelpdeskArticle('how-to-change-my-form-url-171rjw7')"
        >
          {{ $t('form_pages.shared.help') }}
        </UButton>
    </template>

      <template #body>
        <p>
          {{ $t('form_pages.regenerate_link.intro') }}
          <span class="font-semibold">{{ $t('form_pages.regenerate_link.warning') }}</span>. {{ $t('form_pages.regenerate_link.reminder') }}
        </p>
        <div class="border-t py-4 mt-4">
          <h3 class="text-xl text-neutral-700 font-semibold">
            {{ $t('form_pages.regenerate_link.readable_heading') }}
          </h3>
          <p>
            {{ $t('form_pages.regenerate_link.readable_description') }}
          </p>
          <p class="text-neutral-600 border p-4 bg-neutral-50 rounded-md mt-4">
            https://opnform.com/forms/contact-form-e68des
          </p>
          <div class="text-center mt-4">
            <TrackClick
              name="regenerate_form_link_readable_click"
              :properties="{form_id: form.id, form_slug: form.slug, type: 'slug'}"
            >
              <UButton
                :loading="regenerateLinkMutationInstance.isPending.value"
                variant="outline"
                color="primary"
                @click="regenerateLink('slug')"
              >
                {{ $t('form_pages.regenerate_link.readable_button') }}
              </UButton>
            </TrackClick>
          </div>
        </div>
        <div class="border-t pt-4 mt-4">
          <h3 class="text-xl text-neutral-700 font-semibold">
            {{ $t('form_pages.regenerate_link.random_heading') }}
          </h3>
          <p>
            {{ $t('form_pages.regenerate_link.random_description') }}
          </p>
          <p class="text-neutral-600 p-4 border bg-neutral-50 rounded-md mt-4">
            https://opnform.com/forms/b4417f9c-34ae-4421-8006-832ee47786e7
          </p>
          <div class="text-center mt-4">
            <TrackClick
              name="regenerate_form_link_random_click"
              :properties="{form_id: form.id, form_slug: form.slug, type: 'uuid'}"
            >
              <UButton
                :loading="regenerateLinkMutationInstance.isPending.value"
                variant="outline"
                color="primary"
                @click="regenerateLink('uuid')"
              >
                {{ $t('form_pages.regenerate_link.random_button') }}
              </UButton>
            </TrackClick>
          </div>
        </div>
      </template>
    </UModal>
  </div>
</template>

<script setup>
import TrackClick from "~/components/global/TrackClick.vue"

const props = defineProps({
  form: { type: Object, required: true },
})

const crisp = useCrisp()
const { t } = useI18n()
const router = useRouter()
const showGenerateFormLinkModal = ref(false)

// Modal state
const isModalOpen = computed({
  get() {
    return showGenerateFormLinkModal.value
  },
  set(value) {
    showGenerateFormLinkModal.value = value
  }
})

const { regenerateLink: regenerateLinkMutation } = useForms()
const regenerateLinkMutationInstance = regenerateLinkMutation()

const regenerateLink = (option) => {
  regenerateLinkMutationInstance.mutateAsync({
    id: props.form.id,
    option
  }).then((data) => {
    router.push({
      name: "forms-slug-show-share",
      params: { slug: data.form.slug },
    })
    useAlert().success(data.message)
    showGenerateFormLinkModal.value = false
  }).catch((error) => {
    useAlert().error(error?.data?.message || t('common.errors.something_went_wrong'))
  })
}
</script>
