<template>
  <VForm size="sm">
    <div class="space-y-4">
      <div>
        <h3 class="text-lg font-medium text-neutral-900 dark:text-neutral-100">
          {{ $t('form_blocks.sections.scoring') }}
        </h3>
        <p class="mt-1 text-sm text-neutral-500">
          {{ $t('form_blocks.scoring.description') }}
        </p>
      </div>

      <ToggleSwitchInput
        name="settings.scoring_enabled"
        :form="form"
        :label="$t('form_blocks.scoring.enable_label')"
        :help="$t('form_blocks.scoring.enable_help')"
      />

      <template v-if="scoringEnabled">
        <div class="border-t pt-4">
          <ScoreBudgetOverview :form="form" />
        </div>

        <div class="border-t pt-4">
          <ScoreTiersEditor v-model="scoreTiers" />
        </div>

        <div
          v-if="form?.id"
          class="border-t pt-4"
        >
          <h4 class="text-sm font-semibold text-neutral-700 dark:text-neutral-300">
            {{ $t('form_blocks.scoring.recalculate_heading') }}
          </h4>
          <p class="mt-1 mb-3 text-sm text-neutral-500">
            {{ $t('form_blocks.scoring.recalculate_help') }}
          </p>
          <UButton
            color="neutral"
            variant="outline"
            size="sm"
            icon="i-heroicons-arrow-path"
            :loading="isRecalculating"
            :label="$t('form_blocks.scoring.recalculate_button')"
            @click="onRecalculate"
          />
        </div>
      </template>
    </div>
  </VForm>
</template>

<script setup>
import { computed, onBeforeUnmount, ref } from 'vue'
import ScoreBudgetOverview from './scoring/ScoreBudgetOverview.vue'
import ScoreTiersEditor from './scoring/ScoreTiersEditor.vue'
import { formsApi } from '~/api'
import { ensureSettingsObject } from '~/composables/forms/initForm'
import { getScoreTiers, isScoringEnabled } from '~/lib/forms/scoring'

const POLL_INTERVAL_MS = 2000
const POLL_MAX_ATTEMPTS = 150 // five minutes

const { t } = useI18n()
const workingFormStore = useWorkingFormStore()
const { content: form } = storeToRefs(workingFormStore)
const alert = useAlert()
const { recalculateScores, invalidateScores } = useFormSubmissions()

const scoringEnabled = computed(() => isScoringEnabled(form.value))

// Falls back to the shared defaults instead of seeding them into the form:
// writing on mount would mark every form dirty just for opening this page.
const scoreTiers = computed({
  get: () => {
    const configured = form.value?.settings?.score_tiers
    return Array.isArray(configured) && configured.length
      ? configured
      : getScoreTiers(form.value)
  },
  set: (value) => {
    ensureSettingsObject(form.value)
    form.value.settings.score_tiers = value
  },
})

const isRecalculating = ref(false)
let pollTimer = null

const stopPolling = () => {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
  isRecalculating.value = false
}

onBeforeUnmount(stopPolling)

// The job runs on the queue, so the scores only exist once it reports back.
const pollRecalculation = (formId, jobId) => {
  let attempts = 0

  pollTimer = setInterval(async () => {
    attempts++

    if (attempts > POLL_MAX_ATTEMPTS) {
      stopPolling()
      return
    }

    try {
      const status = await formsApi.submissions.recalculateScoresStatus(formId, jobId)

      if (status?.status === 'completed') {
        stopPolling()
        invalidateScores(formId)
        alert.success(
          t('runtime.scoring.recalculate_success', status.processed_submissions ?? 0),
        )
      } else if (status?.status === 'failed') {
        stopPolling()
        alert.error(t('runtime.scoring.recalculate_error'))
      }
    } catch {
      stopPolling()
      alert.error(t('runtime.scoring.recalculate_error'))
    }
  }, POLL_INTERVAL_MS)
}

const recalculateMutation = recalculateScores({
  onSuccess: (response, { formId }) => {
    alert.success(t('runtime.scoring.recalculate_started'))
    if (response?.job_id) {
      pollRecalculation(formId, response.job_id)
    } else {
      stopPolling()
    }
  },
  onError: () => {
    stopPolling()
    alert.error(t('runtime.scoring.recalculate_error'))
  },
})

const onRecalculate = () => {
  if (isRecalculating.value) return

  alert.confirm(t('form_blocks.scoring.recalculate_confirm'), () => {
    isRecalculating.value = true
    recalculateMutation.mutate({ formId: form.value.id })
  })
}
</script>
