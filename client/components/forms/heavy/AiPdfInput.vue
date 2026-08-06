<template>
  <InputWrapper v-bind="inputWrapperProps">
    <template #label>
      <slot name="label" />
    </template>

    <!-- Editor placeholder: nothing is generated until someone answers -->
    <div
      v-if="isAdminPreview"
      class="border border-dashed rounded-lg p-6 text-center flex flex-col gap-1 text-neutral-500 dark:text-neutral-400"
    >
      <span class="font-semibold text-neutral-700 dark:text-neutral-200">
        {{ $t('forms.ai_pdf.editor_placeholder_title') }}
      </span>
      <span class="text-sm">{{ $t('forms.ai_pdf.editor_placeholder_body') }}</span>
    </div>

    <div
      v-else
      class="rounded-lg border border-neutral-200 dark:border-neutral-700 p-4 flex flex-col gap-3"
    >
      <!-- Generating -->
      <template v-if="state === 'generating'">
        <div class="h-1.5 rounded-full bg-neutral-100 dark:bg-neutral-800 overflow-hidden">
          <div
            class="h-full rounded-full ai-pdf-progress"
            :style="{ backgroundColor: color }"
          />
        </div>
        <div class="flex items-baseline gap-2">
          <span class="text-sm text-neutral-600 dark:text-neutral-300 flex-1">
            {{ progressMessage }}
          </span>
          <span class="text-xs text-neutral-400 tabular-nums">{{ elapsedSeconds }}s</span>
        </div>
      </template>

      <!-- Ready -->
      <template v-else-if="state === 'ready'">
        <div class="flex items-center gap-3">
          <UIcon
            name="i-heroicons-document-check"
            class="w-8 h-8 shrink-0"
            :style="{ color }"
          />
          <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold truncate">
              {{ fileName }}
            </p>
            <p
              v-if="fileSizeLabel"
              class="text-xs text-neutral-400 tabular-nums"
            >
              {{ fileSizeLabel }}
            </p>
          </div>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <a
            v-if="downloadUrl"
            :href="downloadUrl"
            rel="noopener"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white"
            :style="{ backgroundColor: color }"
          >
            <UIcon
              name="i-heroicons-arrow-down-tray"
              class="w-4 h-4"
            />
            {{ $t('forms.ai_pdf.download') }}
          </a>
          <!-- Without a live link there is nothing else to click, so the way
               back to the document stays available even when regenerating is
               otherwise switched off. -->
          <button
            v-if="!downloadUrl"
            type="button"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white disabled:opacity-50"
            :style="{ backgroundColor: color }"
            :disabled="disabled"
            @click="startGeneration"
          >
            <UIcon
              name="i-heroicons-arrow-path"
              class="w-4 h-4"
            />
            {{ $t('forms.ai_pdf.rebuild_link') }}
          </button>
          <button
            v-else-if="canRegenerate"
            type="button"
            class="text-sm text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300 underline underline-offset-2"
            :disabled="disabled"
            @click="startGeneration"
          >
            {{ $t('forms.ai_pdf.regenerate') }}
          </button>
        </div>
        <p
          v-if="!downloadUrl"
          class="text-xs text-neutral-400"
        >
          {{ $t('forms.ai_pdf.link_expired_hint') }}
        </p>
      </template>

      <!-- Failed -->
      <template v-else-if="state === 'error'">
        <UAlert
          color="error"
          variant="subtle"
          :description="errorMessage"
        />
        <div class="flex flex-wrap items-center gap-3">
          <button
            type="button"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white"
            :style="{ backgroundColor: color }"
            :disabled="disabled"
            @click="startGeneration"
          >
            {{ $t('forms.ai_pdf.try_again') }}
          </button>
        </div>
      </template>

      <!-- Waiting for the respondent to ask -->
      <template v-else>
        <p
          v-if="!help"
          class="text-sm text-neutral-600 dark:text-neutral-300"
        >
          {{ $t('forms.ai_pdf.idle_hint') }}
        </p>
        <div>
          <button
            type="button"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white disabled:opacity-50"
            :style="{ backgroundColor: color }"
            :disabled="disabled"
            @click="startGeneration"
          >
            <UIcon
              name="i-heroicons-sparkles"
              class="w-4 h-4"
            />
            {{ $t('forms.ai_pdf.generate') }}
          </button>
        </div>
      </template>
    </div>

    <template #error>
      <slot name="error" />
    </template>
  </InputWrapper>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { inputProps, useFormInput } from '../useFormInput.js'
import { formsApi } from '~/api'

const props = defineProps({
  ...inputProps,
  formSlug: { type: String, default: null },
  autoGenerate: { type: Boolean, default: true },
  allowRegenerate: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue', 'focus', 'blur'])

const { compVal, inputWrapperProps } = useFormInput(props, { emit })
const { t } = useI18n()

const POLL_INTERVAL_MS = 2000
const MAX_WAIT_MS = 5 * 60 * 1000

const state = ref('idle')
const downloadUrl = ref(null)
const fileName = ref(null)
const fileSize = ref(null)
const errorMessage = ref('')
const elapsedSeconds = ref(0)

let pollTimer = null
let tickTimer = null
let startedAt = null

/**
 * The handle that lets us re-read a generation — and mint a fresh download
 * link — outlives the component: moving between pages unmounts this block, and
 * the answer itself survives a reload through the saved draft. Keeping it in
 * localStorage means coming back re-reads the status instead of paying for a
 * second document.
 */
const handleKey = computed(() => `ai-pdf-generation:${props.formSlug || 'form'}:${props.name}`)

const readHandle = () => {
  if (!import.meta.client) return null
  try {
    const raw = window.localStorage.getItem(handleKey.value)
    return raw ? JSON.parse(raw) : null
  } catch {
    return null
  }
}

const rememberHandle = (handle) => {
  if (!import.meta.client) return
  try {
    window.localStorage.setItem(handleKey.value, JSON.stringify(handle))
  } catch {
    // Private browsing and full quotas both land here; the block still works,
    // it just cannot restore the link after a reload.
  }
}

const canRegenerate = computed(() => props.allowRegenerate && !props.disabled)

const fileSizeLabel = computed(() => {
  if (!fileSize.value) return null
  const megabytes = fileSize.value / (1024 * 1024)
  return megabytes >= 1
    ? `${megabytes.toFixed(1)} MB`
    : `${Math.max(1, Math.round(fileSize.value / 1024))} KB`
})

const progressMessages = computed(() => [
  t('forms.ai_pdf.progress.reading_answers'),
  t('forms.ai_pdf.progress.consulting_sources'),
  t('forms.ai_pdf.progress.writing'),
  t('forms.ai_pdf.progress.building_pdf')
])

const progressMessage = computed(() => {
  const index = Math.min(
    Math.floor(elapsedSeconds.value / 12),
    progressMessages.value.length - 1
  )
  return progressMessages.value[index]
})

const clearTimers = () => {
  if (pollTimer) { clearTimeout(pollTimer); pollTimer = null }
  if (tickTimer) { clearInterval(tickTimer); tickTimer = null }
}

const failWith = (messageKey) => {
  clearTimers()
  state.value = 'error'
  errorMessage.value = t(messageKey)
}

const applyReady = (generation) => {
  clearTimers()
  compVal.value = generation.file_reference
  fileName.value = generation.file_name
  fileSize.value = generation.size_bytes
  downloadUrl.value = generation.download_url
  state.value = 'ready'
}

const pollStatus = async (handle) => {
  if (Date.now() - startedAt > MAX_WAIT_MS) {
    failWith('forms.ai_pdf.errors.timed_out')
    return
  }

  try {
    const response = await formsApi.aiPdf.status(props.formSlug, handle.id, handle.token)
    const generation = response?.generation

    if (generation?.status === 'completed') {
      applyReady(generation)
      return
    }

    if (generation?.status === 'failed') {
      failWith('forms.ai_pdf.errors.generation_failed')
      return
    }

    pollTimer = setTimeout(() => pollStatus(handle), POLL_INTERVAL_MS)
  } catch {
    failWith('forms.ai_pdf.errors.generation_failed')
  }
}

const beginWaiting = (handle) => {
  state.value = 'generating'
  startedAt = Date.now()
  elapsedSeconds.value = 0
  tickTimer = setInterval(() => {
    elapsedSeconds.value = Math.round((Date.now() - startedAt) / 1000)
  }, 1000)
  pollTimer = setTimeout(() => pollStatus(handle), POLL_INTERVAL_MS)
}

const startGeneration = async () => {
  if (props.disabled || props.isAdminPreview || !props.formSlug) return
  if (state.value === 'generating') return

  clearTimers()
  errorMessage.value = ''
  state.value = 'generating'
  startedAt = Date.now()
  elapsedSeconds.value = 0

  try {
    const response = await formsApi.aiPdf.generate(props.formSlug, {
      block_id: props.name,
      answers: typeof props.form?.data === 'function' ? props.form.data() : {}
    })

    const handle = { id: response?.generation?.id, token: response?.token }
    if (!handle.id || !handle.token) {
      failWith('forms.ai_pdf.errors.generation_failed')
      return
    }

    rememberHandle(handle)
    beginWaiting(handle)
  } catch (error) {
    if (error?.response?.status === 429) {
      failWith('forms.ai_pdf.errors.too_many_requests')
    } else {
      failWith('forms.ai_pdf.errors.generation_failed')
    }
  }
}

/**
 * Coming back to this step: re-read the known generation so the download link
 * is fresh, since signed URLs expire.
 */
const restorePreviousGeneration = async () => {
  const handle = readHandle()
  if (!handle?.id || !handle?.token || !props.formSlug) return false

  try {
    const response = await formsApi.aiPdf.status(props.formSlug, handle.id, handle.token)
    const generation = response?.generation

    if (generation?.status === 'completed') {
      applyReady(generation)
      return true
    }

    if (generation?.status === 'failed') {
      state.value = 'error'
      errorMessage.value = t('forms.ai_pdf.errors.generation_failed')
      return true
    }

    beginWaiting(handle)
    return true
  } catch {
    return false
  }
}

onMounted(async () => {
  if (props.isAdminPreview) return

  if (await restorePreviousGeneration()) return

  // A value with no handle means the answer was restored from a draft; the
  // file is still attached, there is simply no fresh link to offer.
  if (compVal.value) {
    state.value = 'ready'
    fileName.value = fileName.value || t('forms.ai_pdf.saved_document')
    return
  }

  if (props.autoGenerate) {
    startGeneration()
  }
})

onBeforeUnmount(clearTimers)
</script>

<style scoped>
.ai-pdf-progress {
  width: 8%;
  animation: ai-pdf-crawl 60s cubic-bezier(0.25, 0.6, 0.2, 1) forwards;
}

@keyframes ai-pdf-crawl {
  0% { width: 8%; }
  30% { width: 46%; }
  65% { width: 72%; }
  100% { width: 94%; }
}

@media (prefers-reduced-motion: reduce) {
  .ai-pdf-progress {
    animation: none;
    width: 46%;
  }
}
</style>
