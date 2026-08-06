<template>
  <div class="flex flex-col gap-2">
    <div
      v-for="document in documents"
      :key="document.id"
      class="flex items-center gap-2 p-2 border rounded-lg border-neutral-200 dark:border-neutral-700"
    >
      <UIcon
        :name="iconFor(document.original_name)"
        class="w-5 h-5 shrink-0 text-neutral-400"
      />
      <div class="min-w-0 flex-1">
        <p class="text-sm truncate">
          {{ document.original_name }}
        </p>
        <p class="text-xs text-neutral-400 tabular-nums">
          {{ formatSize(document.size_bytes) }}
        </p>
      </div>

      <UBadge
        :color="statusColor(document.status)"
        variant="subtle"
        size="sm"
      >
        {{ statusLabel(document) }}
      </UBadge>

      <UButton
        color="neutral"
        variant="ghost"
        size="xs"
        icon="i-heroicons-trash"
        :aria-label="$t('form_fields.ai_pdf.remove_file')"
        :loading="removingId === document.id"
        @click.prevent="remove(document)"
      />
    </div>

    <p
      v-for="document in failedDocuments"
      :key="`error-${document.id}`"
      class="text-xs text-red-500"
    >
      {{ document.original_name }} — {{ extractionError(document) }}
    </p>

    <div v-if="canUploadMore">
      <input
        ref="fileInput"
        type="file"
        class="hidden"
        :accept="acceptedExtensions"
        @change="onFilePicked"
      >
      <button
        type="button"
        class="w-full border border-dashed rounded-lg py-3 px-2 text-xs text-neutral-500 hover:border-neutral-400 disabled:opacity-50"
        :disabled="uploading"
        @click.prevent="openPicker"
      >
        <span v-if="uploading">{{ $t('form_fields.ai_pdf.uploading') }}</span>
        <span v-else>
          {{ $t('form_fields.ai_pdf.upload_cta') }}<br>
          <span class="text-neutral-400">pdf · xlsx · xls · csv · txt · md</span>
        </span>
      </button>
    </div>

    <p
      v-if="uploadError"
      class="text-xs text-red-500"
    >
      {{ uploadError }}
    </p>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { formsApi } from '~/api'

const props = defineProps({
  documents: { type: Array, default: () => [] },
  role: { type: String, required: true },
  blockId: { type: String, required: true },
  workspaceId: { type: [String, Number], default: null },
  formId: { type: [String, Number], default: null },
  maxFiles: { type: Number, default: 1 }
})

const emit = defineEmits(['changed'])

const { t } = useI18n()

// Kept in sync with config/ai_pdf.php on the API side.
const MAX_FILE_BYTES = 104857600
const acceptedExtensions = '.pdf,.xlsx,.xls,.csv,.txt,.md'

const fileInput = ref(null)
const uploading = ref(false)
const uploadError = ref('')
const removingId = ref(null)

const canUploadMore = computed(() => props.documents.length < props.maxFiles)

const failedDocuments = computed(() => props.documents.filter(document => document.status === 'failed'))

const openPicker = () => {
  uploadError.value = ''
  fileInput.value?.click()
}

const onFilePicked = async (event) => {
  const file = event.target?.files?.[0]
  event.target.value = ''

  if (!file) return

  if (file.size > MAX_FILE_BYTES) {
    uploadError.value = t('form_fields.ai_pdf.errors.too_large')
    return
  }

  if (!props.workspaceId) {
    uploadError.value = t('form_fields.ai_pdf.errors.no_workspace')
    return
  }

  const payload = new FormData()
  payload.append('file', file)
  payload.append('workspace_id', props.workspaceId)
  payload.append('block_id', props.blockId)
  payload.append('role', props.role)
  if (props.formId) payload.append('form_id', props.formId)

  uploading.value = true
  uploadError.value = ''

  try {
    await formsApi.aiPdf.uploadDocument(payload)
    emit('changed')
  } catch (error) {
    uploadError.value = error?.data?.errors?.file?.[0]
      || error?.data?.message
      || t('form_fields.ai_pdf.errors.upload_failed')
  } finally {
    uploading.value = false
  }
}

const remove = async (document) => {
  removingId.value = document.id
  try {
    await formsApi.aiPdf.deleteDocument(document.id)
    emit('changed')
  } catch {
    uploadError.value = t('form_fields.ai_pdf.errors.remove_failed')
  } finally {
    removingId.value = null
  }
}

const statusColor = (status) => ({
  completed: 'success',
  failed: 'error',
  processing: 'warning',
  pending: 'warning'
}[status] || 'neutral')

const statusLabel = (document) => t(`form_fields.ai_pdf.status.${document.status}`)

const extractionError = (document) => {
  const known = ['empty', 'unsupported', 'file_missing']
  return known.includes(document.error)
    ? t(`form_fields.ai_pdf.errors.extraction.${document.error}`)
    : t('form_fields.ai_pdf.errors.extraction.generic')
}

const iconFor = (fileName) => {
  const extension = String(fileName).split('.').pop()?.toLowerCase()
  if (extension === 'pdf') return 'i-heroicons-document-text'
  if (['xlsx', 'xls', 'csv'].includes(extension)) return 'i-heroicons-table-cells'
  return 'i-heroicons-document'
}

const formatSize = (bytes) => {
  if (!bytes) return '—'
  const megabytes = bytes / (1024 * 1024)
  return megabytes >= 1 ? `${megabytes.toFixed(1)} MB` : `${Math.max(1, Math.round(bytes / 1024))} KB`
}
</script>
