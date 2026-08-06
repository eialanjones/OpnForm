<template>
  <div>
    <h4 class="text-sm font-semibold text-neutral-700 dark:text-neutral-300">
      {{ $t('form_blocks.scoring.tiers_heading') }}
    </h4>
    <p class="mt-1 mb-3 text-sm text-neutral-500">
      {{ $t('form_blocks.scoring.tiers_help') }}
    </p>

    <div
      v-for="(tier, index) in tiers"
      :key="index"
      class="mb-2 flex items-end gap-3"
    >
      <input
        type="color"
        class="mb-1 h-8 w-8 shrink-0 cursor-pointer rounded border border-neutral-300 dark:border-neutral-600"
        :value="tier.color || SCORE_TIER_COLORS[index % SCORE_TIER_COLORS.length]"
        :aria-label="$t('form_blocks.scoring.tier_color')"
        @input="updateTier(index, { color: $event.target.value })"
      >

      <TextInput
        :model-value="tier.label"
        :name="`score_tier_label_${index}`"
        class="flex-1"
        :label="index === 0 ? $t('form_blocks.scoring.tier_label') : null"
        @update:model-value="updateTier(index, { label: $event })"
      />

      <div class="w-28 shrink-0">
        <label
          v-if="index === 0"
          class="mb-1 block text-sm font-semibold text-neutral-700 dark:text-neutral-300"
          :for="`score_tier_from_${index}`"
        >
          {{ $t('form_blocks.scoring.tier_from') }}
        </label>
        <!-- Committed on change, not on input: a number field reports an empty
             value mid-way through typing "7.5". -->
        <input
          :id="`score_tier_from_${index}`"
          type="number"
          :class="numberInputClass"
          min="0"
          :max="SCORE_MAX"
          :step="SCORE_STEP"
          :value="tier.from"
          :disabled="index === 0"
          :aria-label="$t('form_blocks.scoring.tier_from')"
          @change="updateTier(index, { from: $event.target.value })"
        >
      </div>

      <UButton
        color="neutral"
        variant="ghost"
        icon="i-heroicons-trash"
        class="mb-1 shrink-0"
        :aria-label="$t('form_blocks.scoring.tier_remove')"
        :disabled="tiers.length <= 1"
        @click="removeTier(index)"
      />
    </div>

    <UAlert
      v-if="validationMessage"
      color="warning"
      variant="subtle"
      class="mt-2"
      :description="validationMessage"
    />

    <UButton
      color="neutral"
      variant="outline"
      size="sm"
      icon="i-heroicons-plus"
      class="mt-3"
      :label="$t('form_blocks.scoring.tier_add')"
      @click="addTier"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import TextInput from '~/components/forms/core/TextInput.vue'
import {
  SCORE_MAX,
  SCORE_STEP,
  SCORE_TIER_COLORS,
  clampWeight,
} from '~/lib/forms/scoring'

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue'])

const { t } = useI18n()

const numberInputClass =
  'w-full rounded-lg border border-neutral-300 dark:border-neutral-600 bg-white dark:bg-notion-dark-light ' +
  'px-2 py-1.5 text-sm text-neutral-700 dark:text-neutral-300 focus:outline-hidden ' +
  'focus:ring-2 focus:ring-form/100 focus:border-transparent disabled:opacity-60'

const tiers = computed(() => props.modelValue ?? [])

// Thresholds are the ordering, so sorting on every change keeps the list and
// the intervals from ever disagreeing.
const commit = (next) => {
  emit(
    'update:modelValue',
    [...next].sort((a, b) => Number(a.from) - Number(b.from)),
  )
}

const updateTier = (index, patch) => {
  const next = tiers.value.map((tier, position) => {
    if (position !== index) return { ...tier }

    const merged = { ...tier, ...patch }
    if (patch.from !== undefined) {
      merged.from = clampWeight(String(patch.from ?? '').replace(',', '.'))
    }

    return merged
  })

  commit(next)
}

const addTier = () => {
  const last = tiers.value[tiers.value.length - 1]
  const from = clampWeight((Number(last?.from) || 0) + 1)

  commit([
    ...tiers.value.map((tier) => ({ ...tier })),
    {
      label: '',
      from,
      color: SCORE_TIER_COLORS[tiers.value.length % SCORE_TIER_COLORS.length],
    },
  ])
}

const removeTier = (index) => {
  if (tiers.value.length <= 1) return

  const next = tiers.value
    .filter((_tier, position) => position !== index)
    .map((tier) => ({ ...tier }))

  // The lowest scores must always land somewhere.
  if (next.length && Number(next[0].from) !== 0) {
    next[0] = { ...next[0], from: 0 }
  }

  commit(next)
}

const validationMessage = computed(() => {
  if (!tiers.value.length) return null

  if (Number(tiers.value[0].from) !== 0) {
    return t('form_blocks.scoring.tier_first_must_be_zero')
  }

  const thresholds = tiers.value.map((tier) => Number(tier.from))
  if (new Set(thresholds).size !== thresholds.length) {
    return t('form_blocks.scoring.tier_duplicate_threshold')
  }

  return null
})
</script>
