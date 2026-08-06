<template>
  <div class="p-4">
    <div
      class="w-full max-w-4xl mx-auto grid grid-cols-2 gap-2"
      :class="metrics.length > 4 ? 'md:grid-cols-3 lg:grid-cols-5' : 'md:grid-cols-4'"
    >
      <div
        v-for="(stat, index) in metrics"
        :key="index"
        class="border border-neutral-300 rounded-lg shadow-xs p-4"
      >
        <div class="mb-2 text-xs text-neutral-500">
          {{ stat.label }}
        </div>
         
        <VTransition name="fade">
        <USkeleton
          v-if="isLoading"
          class="h-7 w-16"
        />
        <span
          v-else-if="form.is_pro"
          class="font-medium text-xl"
        >
          {{ stat.value }}
        </span>
        <span
          v-else
          class="blur-[3px] pointer-events-none"
        >
          {{ stat.placeholder }}
        </span>
      </VTransition>
      </div>
    </div>

    <FormStats 
      class="w-full max-w-4xl mx-auto" 
      :form="form" 
    />
    
    <FormScoreBreakdown
      v-if="hasScoring"
      class="w-full max-w-4xl mx-auto mt-8"
      :form="form"
      :score-stats="statsData?.score_stats ?? {}"
      :is-loading="isLoading"
    />

    <FormTrafficBreakdown
      class="w-full max-w-4xl mx-auto mt-8"
      :form="form"
      :meta-data="statsData?.meta_stats ?? {}"
      :is-loading="isLoading"
    />
  </div>
</template>

<script setup>
import FormStats from "~/components/open/forms/components/FormStats.vue"
import FormTrafficBreakdown from "~/components/open/forms/components/FormTrafficBreakdown.vue"
import FormScoreBreakdown from "~/components/open/forms/components/FormScoreBreakdown.vue"
import { formatScore, formHasScoreWeights, isScoringEnabled } from "~/lib/forms/scoring"

const props = defineProps({
  form: { type: Object, required: true },
})

definePageMeta({
  middleware: "auth",
})
const { t } = useI18n()

useOpnSeoMeta({
  title: props.form
    ? t('form_pages.stats.page_title_with_form', { title: props.form.title })
    : t('form_pages.stats.page_title'),
})

// Use query composables instead of manual API calls
const { statsDetails } = useFormStats()

// Get stats data using query composable
const { data: statsData, isFetching: isQueryLoading } = statsDetails(
  props.form.workspace_id, 
  props.form.id,
  {
    enabled: computed(() => import.meta.client && !!props.form && props.form.is_pro)
  }
)

const isLoading = computed(() => {
  if (import.meta.server) {
    return !!props.form && props.form.is_pro
  }
  return isQueryLoading.value
})

// Computed values derived from query data
const totalViews = computed(() => statsData.value?.views ?? 0)
const totalSubmissions = computed(() => statsData.value?.submissions ?? 0)
const completionRate = computed(() => Math.min(100, statsData.value?.completion_rate ?? 0))
const averageDuration = computed(() => statsData.value?.average_duration ?? '-')

// Only forms that actually weight a block get the score metric and breakdown.
const hasScoring = computed(
  () => isScoringEnabled(props.form) && formHasScoreWeights(props.form),
)

const averageScore = computed(() => {
  const average = statsData.value?.score_stats?.average
  return average === null || average === undefined ? '-' : formatScore(average)
})

const metrics = computed(() => [
  { label: t('form_pages.stats.views'), value: totalViews.value, placeholder: '123' },
  { label: t('form_pages.stats.submissions'), value: totalSubmissions.value, placeholder: '123' },
  { label: t('form_pages.stats.completion'), value: completionRate.value + '%', placeholder: '100%' },
  { label: t('form_pages.stats.avg_duration'), value: averageDuration.value, placeholder: t('form_pages.stats.duration_placeholder') },
  ...(hasScoring.value
    ? [{ label: t('form_pages.stats.average_score'), value: averageScore.value, placeholder: t('form_pages.stats.score_placeholder') }]
    : []),
])
</script>
