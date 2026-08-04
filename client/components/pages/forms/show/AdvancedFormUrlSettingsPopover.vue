<template>
  <UPopover arrow :content="{ align: 'end' }">
    <TrackClick
      name="advanced_form_url_settings_click"
      :properties="{form_id: form.id}"
    >
      <UButton
        variant="ghost"
        color="neutral"
        icon="i-heroicons-cog-6-tooth"
      >
      </UButton>
    </TrackClick>

    <template #content>
      <div class="p-4 w-80">
        <h3 class="font-semibold text-medium">
          {{ $t('form_pages.advanced_url_settings.heading') }}
        </h3>
        <p class="text-sm text-neutral-600">{{ $t('form_pages.advanced_url_settings.description') }}</p>

        <div class="space-y-4">
          <toggle-switch-input
            :model-value="modelValue.auto_submit"
            name="auto_submit"
            :label="$t('form_pages.advanced_url_settings.auto_submit_label')"
            :help="$t('form_pages.advanced_url_settings.auto_submit_help')"
            @update:model-value="onChangeAutoSubmit"
          />
        </div>
      </div>
    </template>
  </UPopover>
</template>

<script>
import TrackClick from '~/components/global/TrackClick.vue'

export default {
  name: "AdvancedFormUrlSettingsPopover",
  components: { TrackClick },
  props: {
    form: {
      type: Object,
      required: true,
    },
    modelValue: {
      type: Object,
      required: true,
    },
  },

  emits: ['update:modelValue'],

  methods: {
    onChangeAutoSubmit(val) {
      const updatedValue = { ...this.modelValue, auto_submit: val }
      this.$emit('update:modelValue', updatedValue)
    },
  },
}
</script> 