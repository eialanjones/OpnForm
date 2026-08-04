<template>
  <CopyContent
    :content="share_url"
    :label="$t('common.actions.copy')"
    class="w-auto"
  />
</template>
  
<script setup>
import { computed, defineProps } from 'vue'
import CopyContent from './CopyContent.vue'

const props = defineProps({
  form: {
    type: Object,
    required: true,
  },
  link: {
    type: Boolean,
    default: false,
  },
  extraQueryParam: {
    type: String,
    default: '',
  },
})

const share_url = computed(() => {
  if (props.extraQueryParam) {
      return `${props.form.share_url}?${props.extraQueryParam}`
  }
  return props.form.share_url
})
</script>
  