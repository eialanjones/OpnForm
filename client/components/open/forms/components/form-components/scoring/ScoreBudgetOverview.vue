<template>
  <div>
    <h4 class="text-sm font-semibold text-neutral-700 dark:text-neutral-300">
      {{ $t('form_blocks.scoring.budget_heading') }}
    </h4>

    <p class="mt-1 text-sm text-neutral-500">
      {{
        $t('form_blocks.scoring.budget_summary', {
          used: formatPoints(totalDistributed),
          max: SCORE_MAX,
          remaining: formatPoints(remaining),
        })
      }}
    </p>

    <div class="mt-2 flex h-3 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
      <div
        v-for="segment in segments"
        :key="segment.id"
        class="h-full"
        :style="{ width: segment.width, backgroundColor: segment.color }"
        :title="`${segment.name} · ${formatPoints(segment.weight)}`"
      />
    </div>

    <UAlert
      v-if="isOverBudget"
      color="error"
      variant="subtle"
      class="mt-3"
      :description="$t('form_blocks.scoring.budget_exceeded', { max: SCORE_MAX })"
    />

    <h4 class="mt-6 text-sm font-semibold text-neutral-700 dark:text-neutral-300">
      {{ $t('form_blocks.scoring.blocks_heading') }}
    </h4>

    <p
      v-if="!scorableFields.length"
      class="mt-2 rounded-lg border border-dashed p-3 text-sm text-neutral-400"
    >
      {{ $t('form_blocks.scoring.blocks_empty') }}
    </p>

    <ul
      v-else
      class="mt-2 divide-y divide-neutral-200 dark:divide-neutral-700"
    >
      <li
        v-for="field in scorableFields"
        :key="field.id"
      >
        <button
          type="button"
          class="flex w-full items-center gap-3 py-2 text-left hover:opacity-80"
          @click="focusBlock(field)"
        >
          <BlockTypeIcon :type="field.type" />
          <span
            class="min-w-0 flex-1 truncate text-sm"
            :class="weightOf(field) > 0 ? 'text-neutral-700 dark:text-neutral-300' : 'text-neutral-400'"
          >
            {{ field.name }}
          </span>
          <span class="shrink-0 text-xs text-neutral-400">
            {{ modeLabel(field) }}
          </span>
          <UBadge
            :color="weightOf(field) > 0 ? 'primary' : 'neutral'"
            variant="subtle"
            size="sm"
            :label="weightOf(field) > 0 ? formatPoints(weightOf(field)) : $t('form_blocks.scoring.block_no_weight')"
          />
        </button>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { computed, inject } from 'vue'
import BlockTypeIcon from '~/components/open/forms/components/BlockTypeIcon.vue'
import { useFormScoreBudget } from '~/composables/forms/useFormScoreBudget'
import {
  SCORE_MAX,
  SCORE_TIER_COLORS,
  getScoreMode,
  getWeight,
} from '~/lib/forms/scoring'

const props = defineProps({
  form: { type: Object, required: true },
})

const { t } = useI18n()
const workingFormStore = useWorkingFormStore()
const closeSettingsModal = inject('closeSettingsModal', null)

const { scorableFields, totalDistributed, remaining, isOverBudget } =
  useFormScoreBudget(() => props.form)

const formatPoints = (value) => (Number(value) || 0).toFixed(1)
const weightOf = (field) => getWeight(field)
const modeLabel = (field) =>
  weightOf(field) > 0 ? t(`form_fields.scoring.modes.${getScoreMode(field)}`) : ''

const segments = computed(() =>
  scorableFields.value
    .filter((field) => getWeight(field) > 0)
    .map((field, index) => ({
      id: field.id,
      name: field.name,
      weight: getWeight(field),
      width: `${Math.min((getWeight(field) / SCORE_MAX) * 100, 100)}%`,
      color: SCORE_TIER_COLORS[index % SCORE_TIER_COLORS.length],
    })),
)

const focusBlock = (field) => {
  if (closeSettingsModal) closeSettingsModal()
  workingFormStore.openSettingsForField(field, true)
}
</script>
