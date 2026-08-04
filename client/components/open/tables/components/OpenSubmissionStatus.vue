<template>
  <UBadge
    :label="displayLabel"
    :color="badgeColor"
    variant="subtle"
    class="mx-auto"
    size="sm"
  />
</template>

<script setup>
const props = defineProps({
  value: {
    type: String,
    required: true,
  },
})

const { t } = useI18n()

const displayLabel = computed(() => {
  return props.value === 'partial' ? t('submissions.status.in_progress') : t('submissions.status.submitted')
})

const badgeColor = computed(() => {
  return props.value === 'partial' ? 'warning' : 'success'
})
</script> 