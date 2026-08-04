<template>
  <UTooltip
    :text="tooltipText"
    :disabled="!unavailable || !tooltipText"
  >
    <TrackClick
      name="new_integration_click"
      :properties="{ name: integration.id }"
    >
      <div
        role="button"
        :class="{
          'hover:bg-neutral-100 dark:hover:bg-neutral-800 group cursor-pointer': !unavailable,
          'cursor-not-allowed opacity-50': unavailable,
        }"
        class="border rounded-lg p-4 flex flex-col items-center justify-center text-center transition-colors w-full h-full relative"
        @click="onClick"
      >
      <div class="flex-shrink-0">
        <Icon
          :name="integration.icon"
          class="w-8 h-8 text-neutral-500 transition-colors group-hover:text-neutral-700"
        />
      </div>
      <div class="flex-grow flex flex-col justify-center">
        <div class="font-semibold text-sm text-neutral-800 dark:text-neutral-200">
          {{ integration.name }}
          <span
            v-if="integration.coming_soon"
            class="text-xs text-neutral-500"
          >{{ $t('integrations.list_option.soon') }}</span>
        </div>
      </div>
      <pro-tag
        v-if="integration?.is_pro === true"
        class="absolute top-2 right-2"
      />
      <Icon
        v-if="integration.is_external"
        class="absolute bottom-2 right-2 h-4 w-4 text-neutral-400"
        name="heroicons:arrow-top-right-on-square-20-solid"
      />
      </div>
    </TrackClick>
  </UTooltip>
</template>

<script setup>
import { computed } from 'vue'
import ProTag from "~/components/app/ProTag.vue"
import TrackClick from "~/components/global/TrackClick.vue"
const emit = defineEmits(["select"])
const { t } = useI18n()
const { openSubscriptionModal } = useAppModals()

const props = defineProps({
  integration: {
    type: Object,
    required: true,
  },
})

const { current: currentWorkspace } = useCurrentWorkspace()

const unavailable = computed(() => {
  return (
    props.integration.coming_soon || 
    (props.integration.requires_subscription && !currentWorkspace.value.is_pro)
  )
})

const tooltipText = computed(() => {
  if (props.integration.coming_soon) return t('integrations.list_option.coming_soon_tooltip')
  if (props.integration.requires_subscription && !currentWorkspace.value.is_pro )
    return t('integrations.list_option.subscription_tooltip')
  return null
})

const onClick = () => {
  if (props.integration.coming_soon) return
  if (props.integration.requires_subscription && !currentWorkspace.value.is_pro ) {
    openSubscriptionModal({
      modal_title: t('integrations.list_option.upgrade_modal_title'),
      modal_description: t('integrations.list_option.upgrade_modal_description', { name: props.integration.name })
    })
    return
  }
  emit("select", props.integration.id)
}
</script>