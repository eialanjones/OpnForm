<template>
  <div class="w-full">
    <h3 class="font-medium text-lg mb-4">{{ $t('form_editor.score_breakdown.heading') }}</h3>

    <div
      v-if="!form.is_pro"
      class="border border-neutral-300 rounded-lg shadow-xs p-4"
    >
      <p class="text-center">
        {{ $t('form_editor.score_breakdown.pro_required') }}
        <pro-tag
          :upgrade-modal-title="$t('form_editor.score_breakdown.upgrade_modal_title')"
          class="mx-1"
        />
      </p>
    </div>

    <VTransition
      v-else
      name="fade"
    >
      <div
        v-if="isLoading"
        class="border border-neutral-300 rounded-lg shadow-xs p-4 space-y-4"
      >
        <USkeleton class="h-6 w-24" />
        <USkeleton class="h-4 w-full rounded-full" />
        <div class="flex flex-wrap gap-2 justify-center">
          <USkeleton
            v-for="index in 3"
            :key="index"
            class="h-4 w-16 rounded"
          />
        </div>
      </div>

      <div
        v-else
        class="border border-neutral-300 rounded-lg shadow-xs p-4"
      >
        <div
          v-if="!scoredCount"
          class="text-sm text-gray-500 text-center py-2"
        >
          {{ $t('form_editor.score_breakdown.no_data') }}
        </div>

        <div
          v-else
          class="space-y-4"
        >
          <p class="text-sm text-neutral-500">
            {{ $t('form_editor.score_breakdown.average', { score: formattedAverage }) }}
            ·
            {{ $t('form_editor.score_breakdown.count', scoredCount) }}
          </p>

          <div class="w-full h-4 flex rounded-full overflow-hidden">
            <div
              v-for="segment in segments"
              :key="segment.label"
              class="h-full"
              :style="{ width: segment.percentage + '%', backgroundColor: segment.color }"
              :title="`${segment.label}: ${segment.count} (${segment.percentage}%)`"
            />
          </div>

          <div class="flex flex-wrap gap-3 items-center justify-center">
            <div
              v-for="segment in segments"
              :key="segment.label"
              class="flex items-center gap-1"
            >
              <div
                class="w-3 h-3 rounded-sm"
                :style="{ backgroundColor: segment.color }"
              />
              <span class="text-xs">{{ segment.label }} ({{ segment.count }})</span>
            </div>
          </div>
        </div>
      </div>
    </VTransition>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import ProTag from '~/components/app/ProTag.vue'
import {
  SCORE_TIER_COLORS,
  formatScore,
  getScoreTiers,
} from '~/lib/forms/scoring'

const props = defineProps({
  form: { type: Object, required: true },
  scoreStats: { type: Object, default: () => ({}) },
  isLoading: { type: Boolean, default: false },
})

const scoredCount = computed(() => Number(props.scoreStats?.scored_count) || 0)

const formattedAverage = computed(() =>
  props.scoreStats?.average === null || props.scoreStats?.average === undefined
    ? '-'
    : formatScore(props.scoreStats.average),
)

// Merged against the live tier list so a tier renamed after the submissions
// were scored still displays with its current name and colour.
const segments = computed(() => {
  const tiers = getScoreTiers(props.form)
  const distribution = Array.isArray(props.scoreStats?.distribution)
    ? props.scoreStats.distribution
    : []

  const total = scoredCount.value || 1

  return tiers.map((tier, index) => {
    const match = distribution.find(
      (entry) => Number(entry.from) === Number(tier.from),
    )
    const count = Number(match?.count) || 0

    return {
      label: tier.label,
      color: tier.color || SCORE_TIER_COLORS[index % SCORE_TIER_COLORS.length],
      count,
      percentage: Math.round((count / total) * 100),
    }
  })
})
</script>
