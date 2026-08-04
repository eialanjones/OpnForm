<template>
  <VForm size="sm">
    <div class="space-y-4">
      <div class="flex flex-col flex-wrap items-start justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h3 class="text-lg font-medium text-neutral-900">
            {{ $t('form_blocks.seo.heading') }} <ProTag
            class="ml-2"
            :upgrade-modal-title="$t('form_blocks.seo.pro_title')"
            :upgrade-modal-description="$t('form_blocks.seo.pro_description')"
          />
          </h3>
          <p class="mt-1 text-sm text-neutral-500">
            {{ $t('form_blocks.seo.description') }}
          </p>
        </div>
      <UButton
        :label="$t('form_blocks.actions.help')"
        icon="i-heroicons-question-mark-circle"
        variant="outline"
        color="neutral"
        @click="crisp.openHelpdeskArticle('how-do-i-add-custom-seo-settings-to-my-forms-url-preview-1v9y9a')"
      />
      </div>

      <template v-if="form.seo_meta">
        <div class="flex flex-col lg:flex-row gap-8 mt-4 lg:items-start">
          <!-- Left Column - Form Inputs -->
          <div class="flex-1 space-y-4 max-w-xs">
            <SelectInput
              v-if="useFeatureFlag('custom_domains')"
              v-model="form.custom_domain"
              :clearable="true"
              :disabled="customDomainOptions.length <= 0"
              :options="customDomainOptions"
              name="type"
              :label="$t('form_blocks.seo.form_domain_label')"
              :placeholder="$t('form_blocks.seo.form_domain_placeholder')"
            />
            <text-input
              v-model="form.seo_meta.page_title"
              name="page_title"
              :label="$t('form_blocks.seo.page_title_label')"
              :help="$t('form_blocks.seo.page_title_help')"
            />
            <text-area-input
              v-model="form.seo_meta.page_description"
              name="page_description"
              :label="$t('form_blocks.seo.page_description_label')"
              :help="$t('form_blocks.seo.page_description_help')"
            />
            <image-input
              v-model="form.seo_meta.page_thumbnail"
              name="page_thumbnail"
              :label="$t('form_blocks.seo.thumbnail_label')"
              :help="$t('form_blocks.seo.thumbnail_help')"
            />
            <image-input
              v-model="form.seo_meta.page_favicon"
              name="page_favicon"
              :label="$t('form_blocks.seo.favicon_label')"
              :help="$t('form_blocks.seo.favicon_help')"
            />
          </div>
          
          <!-- Right Column - Preview (After fields on mobile) -->
          <SeoPreview :form="form" />
        </div>
      </template>

      <div class="w-full border-t pt-4 mt-4">
        <h4 class="font-semibold">
          {{ $t('form_blocks.seo.link_privacy_heading') }}
        </h4>
        <p class="text-neutral-500 text-sm mb-4">
          {{ $t('form_blocks.seo.link_privacy_description') }}
        </p>
        <ToggleSwitchInput
          name="can_be_indexed"
          :form="form"
          :label="$t('form_blocks.seo.indexable_label')"
        />
      </div>

      <div v-if="useFeatureFlag('self_hosted')" class="w-full border-t pt-4 mt-4">
        <h4 class="font-semibold">
          {{ $t('form_blocks.seo.custom_url_heading') }}
        </h4>
        <p class="text-neutral-500 text-sm mb-4">
          {{ $t('form_blocks.seo.custom_url_description') }}
        </p>
        <text-input
          :form="form"
          name="slug"
          class="mt-4 max-w-xs"
          :label="$t('form_blocks.seo.custom_url_label')"
          :help="$t('form_blocks.seo.custom_url_help')"
        />
      </div>
    </div>
  </VForm>
</template>

<script setup>
const crisp = useCrisp()
import ProTag from "~/components/app/ProTag.vue"
import SeoPreview from "~/components/open/forms/components/SeoPreview.vue"

const workingFormStore = useWorkingFormStore()
const { content: form } = storeToRefs(workingFormStore)

const { current: workspace } = useCurrentWorkspace()

const customDomainOptions = computed(() => {
  return workspace?.value?.custom_domains
    ? workspace?.value?.custom_domains.map((domain) => {
        return {
          name: domain,
          value: domain,
        }
      })
    : []
})

onMounted(() => {
  if (!form.value.seo_meta || Array.isArray(form.value.seo_meta))
    form.value.seo_meta = {}

  form.value.seo_meta = {
    ...form.value.seo_meta,
    page_title: form.value.seo_meta.page_title === undefined ? null : form.value.seo_meta.page_title,
    page_description: form.value.seo_meta.page_description === undefined ? null : form.value.seo_meta.page_description,
    page_thumbnail: form.value.seo_meta.page_thumbnail === undefined ? null : form.value.seo_meta.page_thumbnail,
    page_favicon: form.value.seo_meta.page_favicon === undefined ? null : form.value.seo_meta.page_favicon,
  }

  if (form.value.custom_domain && workspace.value?.custom_domains && !workspace.value.custom_domains.find((item) => { return item === form.value.custom_domain })) {
    form.value.custom_domain = null
  }
})
</script>
