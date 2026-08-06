<template>
  <div
    v-if="isScorable && (scoringEnabled || hasWeight)"
    class="px-4"
  >
    <EditorSectionHeader
      icon="i-heroicons-calculator"
      :title="$t('form_fields.scoring.title')"
    />

    <UAlert
      v-if="showDisabledNotice"
      color="neutral"
      variant="subtle"
      :description="$t('form_fields.scoring.disabled_notice')"
    />

    <template v-else>
      <div class="flex items-center justify-between">
        <span class="text-sm font-semibold text-neutral-700 dark:text-neutral-300">
          {{ $t('form_fields.scoring.weight_label') }}
        </span>
        <span class="text-sm font-medium tabular-nums text-neutral-700 dark:text-neutral-300">
          {{ formatPoints(weight) }}
        </span>
      </div>

      <!-- A range input never carries a half-typed value, so the weight stays
           a valid multiple of the step at every keystroke. -->
      <input
        type="range"
        class="mt-2 w-full accent-form"
        min="0"
        :max="sliderMax"
        :step="SCORE_STEP"
        :value="weight"
        :aria-label="$t('form_fields.scoring.weight_label')"
        @input="setWeight($event.target.value)"
      >

      <p
        class="mt-1 text-xs"
        :class="isOverBudget ? 'text-red-500' : 'text-neutral-500'"
      >
        {{
          isOverBudget
            ? $t('form_fields.scoring.budget_exceeded', { max: SCORE_MAX })
            : $t('form_fields.scoring.budget_hint', { remaining: formatPoints(remaining), max: SCORE_MAX })
        }}
      </p>

      <FlatSelectInput
        v-model="mode"
        name="scoring_mode"
        class="mt-4"
        :label="$t('form_fields.scoring.mode_label')"
        :options="modeOptions"
      />

      <p class="mt-1 text-xs text-neutral-500">
        {{ modeHelp }}
      </p>

      <!-- Points per option -->
      <div
        v-if="mode === SCORE_MODE_OPTIONS && scorableOptions.length"
        class="mt-4"
      >
        <span class="mb-2 block text-sm font-semibold text-neutral-700 dark:text-neutral-300">
          {{ $t('form_fields.scoring.options_title') }}
        </span>

        <div
          v-for="option in scorableOptions"
          :key="option.key"
          class="mb-2 flex items-center gap-3"
        >
          <span class="min-w-0 flex-1 truncate text-sm text-neutral-600 dark:text-neutral-300">
            {{ option.label }}
          </span>
          <input
            type="number"
            :class="numberInputClass"
            min="0"
            :max="weight"
            :step="SCORE_STEP"
            :value="optionPoints(option.key)"
            :aria-label="option.label"
            @change="setOptionPoints(option.key, $event.target.value)"
          >
        </div>

        <UAlert
          v-if="optionExceedsWeight"
          color="warning"
          variant="subtle"
          :description="$t('form_fields.scoring.option_exceeds_weight')"
        />
      </div>

      <!-- Expected maximum, for number blocks that carry no bounds -->
      <div
        v-if="needsProportionalMax"
        class="mt-4"
      >
        <span class="mb-1 block text-sm font-semibold text-neutral-700 dark:text-neutral-300">
          {{ $t('form_fields.scoring.proportional_max_label') }}
        </span>
        <input
          type="number"
          :class="numberInputClass"
          min="0"
          step="any"
          :value="field.scoring?.proportional_max ?? ''"
          :aria-label="$t('form_fields.scoring.proportional_max_label')"
          @change="setProportionalMax($event.target.value)"
        >
        <p class="mt-1 text-xs text-neutral-500">
          {{ $t('form_fields.scoring.proportional_max_help') }}
        </p>
      </div>

      <!-- Logic mode depends on a rule living in the Logic tab -->
      <UAlert
        v-if="mode === SCORE_MODE_LOGIC && !logicRuleReady"
        color="warning"
        variant="subtle"
        class="mt-4"
        :description="$t('form_fields.scoring.mode_help.logic')"
        :actions="logicTabActions"
      />
    </template>
  </div>
</template>

<script setup>
import { computed, inject, watch } from 'vue'
import blocksTypes from '~/data/blocks_types.json'
import EditorSectionHeader from '~/components/open/forms/components/form-components/EditorSectionHeader.vue'
import FlatSelectInput from '~/components/forms/core/FlatSelectInput.vue'
import {
  ensureScoringObject,
  useFormScoreBudget,
} from '~/composables/forms/useFormScoreBudget'
import {
  AWARD_SCORE_ACTION,
  OPTION_MODE_TYPES,
  PROPORTIONAL_MODE_TYPES,
  SCORE_MAX,
  SCORE_MODE_ANSWERED,
  SCORE_MODE_LOGIC,
  SCORE_MODE_OPTIONS,
  SCORE_MODE_PROPORTIONAL,
  SCORE_STEP,
  clampWeight,
  getWeight,
  isScoringEnabled,
} from '~/lib/forms/scoring'

const props = defineProps({
  field: { type: Object, required: true },
  form: { type: Object, required: true },
})

const { t } = useI18n()
const fieldEditActiveTab = inject('fieldEditActiveTab', null)
const { remaining, isOverBudget, maxWeightFor } = useFormScoreBudget(() => props.form)

const numberInputClass =
  'w-24 rounded-lg border border-neutral-300 dark:border-neutral-600 bg-white dark:bg-notion-dark-light ' +
  'px-2 py-1 text-sm text-neutral-700 dark:text-neutral-300 focus:outline-hidden ' +
  'focus:ring-2 focus:ring-form/100 focus:border-transparent'

const isScorable = computed(
  () => blocksTypes[props.field?.type]?.is_input === true,
)
const scoringEnabled = computed(() => isScoringEnabled(props.form))
const weight = computed(() => getWeight(props.field))
const hasWeight = computed(() => weight.value > 0)
const showDisabledNotice = computed(
  () => !scoringEnabled.value && hasWeight.value,
)

// The block may take the leftover budget plus whatever it already holds.
const sliderMax = computed(() =>
  Math.max(maxWeightFor(props.field?.id), weight.value),
)

const formatPoints = (value) => (Number(value) || 0).toFixed(1)

// Seeded lazily: writing a scoring object into every field on form load would
// mark every form dirty as soon as it opens.
watch(
  () => props.field?.id,
  () => {
    if (isScorable.value) ensureScoringObject(props.field)
  },
  { immediate: true },
)

const setWeight = (value) => {
  ensureScoringObject(props.field)
  props.field.scoring.weight = clampWeight(value, sliderMax.value)
}

const mode = computed({
  get: () => props.field?.scoring?.mode || SCORE_MODE_ANSWERED,
  set: (value) => {
    ensureScoringObject(props.field)
    props.field.scoring.mode = value
  },
})

const modeOptions = computed(() => {
  const options = [
    { name: t('form_fields.scoring.modes.answered'), value: SCORE_MODE_ANSWERED },
  ]

  if (OPTION_MODE_TYPES.includes(props.field?.type)) {
    options.push({
      name: t('form_fields.scoring.modes.options'),
      value: SCORE_MODE_OPTIONS,
    })
  }

  if (PROPORTIONAL_MODE_TYPES.includes(props.field?.type)) {
    options.push({
      name: t('form_fields.scoring.modes.proportional'),
      value: SCORE_MODE_PROPORTIONAL,
    })
  }

  options.push({
    name: t('form_fields.scoring.modes.logic'),
    value: SCORE_MODE_LOGIC,
  })

  return options
})

// Converting a block (select to multi_select, rating to scale, ...) can leave
// a mode its new type does not support.
watch(modeOptions, (options) => {
  if (!options.some((option) => option.value === mode.value)) {
    mode.value = SCORE_MODE_ANSWERED
  }
})

const proportionalBounds = computed(() => {
  const field = props.field ?? {}

  const min =
    field.type === 'scale'
      ? Number(field.scale_min_value ?? 1)
      : field.type === 'slider'
        ? Number(field.slider_min_value ?? 0)
        : 0

  const override = field.scoring?.proportional_max
  if (override !== null && override !== undefined && override !== '') {
    return { min, max: Number(override) || 0 }
  }

  const max =
    field.type === 'rating'
      ? Number(field.rating_max_value ?? 5)
      : field.type === 'scale'
        ? Number(field.scale_max_value ?? 5)
        : field.type === 'slider'
          ? Number(field.slider_max_value ?? 50)
          : 0

  return { min, max }
})

const modeHelp = computed(() => {
  if (mode.value === SCORE_MODE_PROPORTIONAL) {
    return t('form_fields.scoring.mode_help.proportional', proportionalBounds.value)
  }

  return t(`form_fields.scoring.mode_help.${mode.value}`)
})

const scorableOptions = computed(() => {
  const field = props.field

  if (['select', 'multi_select'].includes(field?.type)) {
    // Keyed by option name: that is what a submission actually stores.
    return (field[field.type]?.options ?? []).map((option) => ({
      key: String(option.name),
      label: String(option.name),
    }))
  }

  if (field?.type === 'checkbox') {
    return [
      { key: 'true', label: t('form_fields.scoring.checkbox_checked') },
      { key: 'false', label: t('form_fields.scoring.checkbox_unchecked') },
    ]
  }

  return []
})

const optionPoints = (key) =>
  Number(props.field?.scoring?.option_points?.[key]) || 0

const setOptionPoints = (key, value) => {
  ensureScoringObject(props.field)

  const points = props.field.scoring.option_points
  if (!points || typeof points !== 'object' || Array.isArray(points)) {
    props.field.scoring.option_points = {}
  }

  props.field.scoring.option_points[key] = clampWeight(
    String(value ?? '').replace(',', '.'),
    weight.value,
  )
}

const optionExceedsWeight = computed(() =>
  scorableOptions.value.some((option) => optionPoints(option.key) > weight.value),
)

// Rewriting the options list orphans the points that referenced the old names.
watch(
  () => scorableOptions.value.map((option) => option.key).join(' '),
  () => {
    const points = props.field?.scoring?.option_points
    if (!points || typeof points !== 'object') return

    const keys = new Set(scorableOptions.value.map((option) => option.key))
    Object.keys(points).forEach((key) => {
      if (!keys.has(key)) delete points[key]
    })
  },
)

const needsProportionalMax = computed(
  () => props.field?.type === 'number' && mode.value === SCORE_MODE_PROPORTIONAL,
)

const setProportionalMax = (value) => {
  ensureScoringObject(props.field)

  const parsed = Number(String(value ?? '').replace(',', '.'))
  props.field.scoring.proportional_max =
    Number.isFinite(parsed) && parsed > 0 ? parsed : null
}

const logicRuleReady = computed(() => {
  const logic = props.field?.logic
  return (
    Boolean(logic?.conditions) &&
    (logic?.actions ?? []).includes(AWARD_SCORE_ACTION)
  )
})

const logicTabActions = computed(() =>
  fieldEditActiveTab
    ? [
        {
          label: t('form_fields.edit.tabs.logic'),
          color: 'neutral',
          variant: 'outline',
          size: 'xs',
          onClick: () => {
            fieldEditActiveTab.value = 'logic'
          },
        },
      ]
    : [],
)
</script>
