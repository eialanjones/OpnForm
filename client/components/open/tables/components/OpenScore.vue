<template>
  <UBadge
    v-if="tier"
    :label="label"
    variant="subtle"
    size="sm"
    :style="badgeStyle"
  />
  <span
    v-else-if="hasScore"
    class="text-sm text-neutral-600 dark:text-neutral-300"
  >
    {{ formattedScore }}
  </span>
  <span
    v-else
    class="text-sm text-neutral-400"
  >
    {{ $t('submissions.cells.score_empty') }}
  </span>
</template>

<script setup>
import { computed } from 'vue'
import { formatScore, resolveScoreTier } from '~/lib/forms/scoring'

const props = defineProps({
  value: {
    type: [Number, String],
    default: null,
  },
  // The column config carries the form's tiers: cell renderers only ever
  // receive `value` and `property`.
  property: {
    type: Object,
    default: () => ({}),
  },
})

// Submissions stored before scoring was configured have no score at all.
const hasScore = computed(
  () =>
    props.value !== null &&
    props.value !== undefined &&
    props.value !== '' &&
    Number.isFinite(Number(props.value)),
)

const formattedScore = computed(() =>
  hasScore.value ? formatScore(props.value) : '',
)

const tier = computed(() =>
  hasScore.value
    ? resolveScoreTier(props.value, props.property?.score_tiers)
    : null,
)

const label = computed(() =>
  tier.value?.label
    ? `${formattedScore.value} · ${tier.value.label}`
    : formattedScore.value,
)

const badgeStyle = computed(() => {
  const color = tier.value?.color
  if (!color) return {}

  // Tier colors are arbitrary hex, so they cannot ride on a Nuxt UI color token.
  return { backgroundColor: `${color}1a`, color }
})
</script>
