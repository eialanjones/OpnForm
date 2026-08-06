<template>
  <div
    v-if="field.type === 'ai_pdf'"
    class="px-4"
  >
    <EditorSectionHeader
      icon="i-heroicons-document-arrow-down"
      :title="$t('form_fields.ai_pdf.title')"
    />

    <!-- Which answers the model may read -->
    <div class="mt-4">
      <span class="text-sm font-semibold text-neutral-700 dark:text-neutral-300 block mb-1">
        {{ $t('form_fields.ai_pdf.sources_label') }}
      </span>
      <p class="text-xs text-neutral-500 mb-2">
        {{ $t('form_fields.ai_pdf.sources_help') }}
      </p>

      <p
        v-if="!hasPrecedingFields"
        class="text-xs text-neutral-400 border border-dashed rounded-lg p-3"
      >
        {{ $t('form_fields.ai_pdf.sources_empty') }}
      </p>

      <template v-else>
        <div
          v-for="group in sourceGroups"
          :key="group.key"
          class="mb-2"
        >
          <p class="text-[10px] uppercase tracking-wider text-neutral-400 mb-1">
            {{ group.label }}
          </p>
          <label
            v-for="sourceField in group.fields"
            :key="sourceField.id"
            class="flex items-center gap-2 py-1 cursor-pointer text-sm text-neutral-600 dark:text-neutral-300"
          >
            <UCheckbox
              :model-value="isSelected(sourceField.id)"
              @update:model-value="toggleSource(sourceField.id)"
            />
            <span class="truncate">{{ sourceField.name }}</span>
          </label>
        </div>

        <div class="flex items-center gap-3 text-xs">
          <span class="text-neutral-400">
            {{ $t('form_fields.ai_pdf.sources_count', { selected: selectedIds.length, total: precedingFields.length }) }}
          </span>
          <button
            type="button"
            class="text-neutral-500 underline underline-offset-2"
            @click.prevent="selectAll"
          >
            {{ $t('form_fields.ai_pdf.select_all') }}
          </button>
          <button
            type="button"
            class="text-neutral-500 underline underline-offset-2"
            @click.prevent="selectNone"
          >
            {{ $t('form_fields.ai_pdf.select_none') }}
          </button>
        </div>
      </template>
    </div>

    <!-- The instructions -->
    <text-area-input
      class="mt-4"
      name="ai_pdf_prompt"
      :form="field"
      :required="true"
      :label="$t('form_fields.ai_pdf.prompt_label')"
      :help="$t('form_fields.ai_pdf.prompt_help')"
      :placeholder="$t('form_fields.ai_pdf.prompt_placeholder')"
    />

    <!-- Knowledge sources -->
    <div class="mt-6">
      <span class="text-sm font-semibold text-neutral-700 dark:text-neutral-300 block mb-1">
        {{ $t('form_fields.ai_pdf.knowledge_label') }}
      </span>
      <p class="text-xs text-neutral-500 mb-2">
        {{ $t('form_fields.ai_pdf.knowledge_help') }}
      </p>
      <AiPdfSourceList
        :documents="knowledgeDocuments"
        role="knowledge"
        :block-id="field.id"
        :workspace-id="workspaceId"
        :form-id="form?.id"
        :max-files="5"
        @changed="loadDocuments"
      />
    </div>

    <!-- Structure template -->
    <div class="mt-6">
      <span class="text-sm font-semibold text-neutral-700 dark:text-neutral-300 block mb-1">
        {{ $t('form_fields.ai_pdf.template_label') }}
      </span>
      <p class="text-xs text-neutral-500 mb-2">
        {{ $t('form_fields.ai_pdf.template_help') }}
      </p>
      <AiPdfSourceList
        :documents="templateDocuments"
        role="template"
        :block-id="field.id"
        :workspace-id="workspaceId"
        :form-id="form?.id"
        :max-files="1"
        @changed="loadDocuments"
      />
    </div>

    <!-- Output -->
    <MentionInput
      class="mt-6"
      name="ai_pdf_file_name"
      :form="field"
      :mentions="form?.properties || []"
      :label="$t('form_fields.ai_pdf.file_name_label')"
      :help="$t('form_fields.ai_pdf.file_name_help')"
    />

    <toggle-switch-input
      class="mt-4"
      name="ai_pdf_auto_generate"
      :form="field"
      :label="$t('form_fields.ai_pdf.auto_generate_label')"
      :help="$t('form_fields.ai_pdf.auto_generate_help')"
    />

    <toggle-switch-input
      class="mt-4"
      name="ai_pdf_allow_regenerate"
      :form="field"
      :label="$t('form_fields.ai_pdf.allow_regenerate_label')"
      :help="$t('form_fields.ai_pdf.allow_regenerate_help')"
    />
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import AiPdfSourceList from './AiPdfSourceList.vue'
import EditorSectionHeader from '~/components/open/forms/components/form-components/EditorSectionHeader.vue'
import MentionInput from '~/components/forms/heavy/MentionInput.vue'
import { formsApi } from '~/api'

const props = defineProps({
  field: { type: Object, required: true },
  form: { type: Object, required: false, default: null }
})

const { t } = useI18n()
const { current: workspace } = useCurrentWorkspace()

const documents = ref([])
let refreshTimer = null

const workspaceId = computed(() => props.form?.workspace_id || workspace.value?.id || null)

/**
 * Only answers collected before this block exist when it runs, so those are
 * the only ones worth offering.
 */
const precedingFields = computed(() => {
  const properties = props.form?.properties || []
  const index = properties.findIndex(property => property.id === props.field.id)
  if (index < 0) return []

  return properties
    .slice(0, index)
    .filter(property => property.type
      && !property.type.startsWith('nf-')
      && property.type !== 'ai_pdf'
      && property.type !== 'payment')
})

const hasPrecedingFields = computed(() => precedingFields.value.length > 0)

/** Groups the offered answers by the page they sit on. */
const sourceGroups = computed(() => {
  const properties = props.form?.properties || []
  const blockIndex = properties.findIndex(property => property.id === props.field.id)
  const groups = []
  let page = 1
  let current = { key: 'page-1', label: t('form_fields.ai_pdf.page_label', { number: 1 }), fields: [] }

  properties.slice(0, blockIndex < 0 ? 0 : blockIndex).forEach((property) => {
    if (property.type === 'nf-page-break') {
      if (current.fields.length) groups.push(current)
      page += 1
      current = { key: `page-${page}`, label: t('form_fields.ai_pdf.page_label', { number: page }), fields: [] }
      return
    }

    if (precedingFields.value.some(candidate => candidate.id === property.id)) {
      current.fields.push(property)
    }
  })

  if (current.fields.length) groups.push(current)

  return groups
})

/**
 * A field never configured means "all of them"; an explicit list — even an
 * empty one — is honoured as written, on both sides of the wire.
 */
const selectedIds = computed(() => {
  const configured = props.field.ai_pdf_source_fields
  if (!Array.isArray(configured)) {
    return precedingFields.value.map(sourceField => sourceField.id)
  }
  return configured.filter(id => precedingFields.value.some(sourceField => sourceField.id === id))
})

const isSelected = (fieldId) => selectedIds.value.includes(fieldId)

const toggleSource = (fieldId) => {
  const next = isSelected(fieldId)
    ? selectedIds.value.filter(id => id !== fieldId)
    : [...selectedIds.value, fieldId]

  props.field.ai_pdf_source_fields = next
}

const selectAll = () => {
  props.field.ai_pdf_source_fields = precedingFields.value.map(sourceField => sourceField.id)
}

const selectNone = () => {
  props.field.ai_pdf_source_fields = []
}

const knowledgeDocuments = computed(() => documents.value.filter(document => document.role === 'knowledge'))
const templateDocuments = computed(() => documents.value.filter(document => document.role === 'template'))

const hasPendingExtraction = computed(() => documents.value.some(
  document => document.status === 'pending' || document.status === 'processing'
))

const loadDocuments = async () => {
  if (!workspaceId.value || !props.field?.id) return

  try {
    const response = await formsApi.aiPdf.documents({
      workspace_id: workspaceId.value,
      block_id: props.field.id
    })
    documents.value = response?.documents || []
  } catch {
    documents.value = []
  }

  scheduleRefresh()
}

/** Extraction runs on a worker, so poll until every file settles. */
const scheduleRefresh = () => {
  if (refreshTimer) { clearTimeout(refreshTimer); refreshTimer = null }
  if (!hasPendingExtraction.value) return

  refreshTimer = setTimeout(loadDocuments, 3000)
}

watch(() => props.field?.id, loadDocuments)

onMounted(loadDocuments)
onBeforeUnmount(() => {
  if (refreshTimer) clearTimeout(refreshTimer)
})
</script>
