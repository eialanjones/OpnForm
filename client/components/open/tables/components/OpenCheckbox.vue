<template>
  <UBadge
    v-if="value === true"
    color="primary"
    variant="soft"
    class="mx-auto"
    size="sm"
  >
    {{ $t('common.labels.yes') }}
  </UBadge>
  <UBadge
    v-else-if="value === false"
    color="neutral"
    variant="soft"
    class="mx-auto"
    size="sm"
  >
    {{ $t('common.labels.no') }}
  </UBadge>
</template>

<script setup>
defineProps({
  value: {
    type: Boolean,
    required: true,
  },
})
</script>
