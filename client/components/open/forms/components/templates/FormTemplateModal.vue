<template>
  <UModal
    v-model:open="isOpen"
    :ui="{ content: 'sm:max-w-4xl' }"
  >
    <template #header>
      <div class="flex items-center w-full gap-4 px-2">
        <h2 class="font-semibold">
          {{ template ? $t('form_editor.template_modal.edit_title') : $t('form_editor.template_modal.create_title') }}
        </h2>
      </div>
      <UButton
        color="neutral"
        variant="outline"
        icon="i-heroicons-question-mark-circle"
        size="sm"
        @click="crisp.openHelpdeskArticle('how-to-create-an-opnform-template-1fn84i4')"
      >
        {{ $t('form_editor.template_modal.help') }}
      </UButton>
    </template>

    <template #body>
      <p v-if="!template" class="mb-4">
        {{ $t('form_editor.template_modal.create_from_form') }}
        <span class="font-semibold">{{ form.title }}</span>.
      </p>

      <v-form
        v-if="templateForm"
        :form="templateForm"
        @submit.prevent="onSubmit"
        @keydown="templateForm.onKeydown($event)"
      >
        <div class="space-y-4">
          <toggle-switch-input
            v-if="user && (user.admin || user.template_editor)"
            name="publicly_listed"
            :form="templateForm"
            :label="$t('form_editor.template_modal.publicly_listed')"
          />
          <text-input
            name="name"
            :form="templateForm"
            :label="$t('common.labels.title')"
            :required="true"
          />
          <text-input
            name="slug"
            :form="templateForm"
            :label="$t('form_editor.template_modal.slug')"
            :required="true"
          />
          <text-area-input
            name="short_description"
            :form="templateForm"
            :label="$t('form_editor.template_modal.short_description')"
            :required="true"
          />
          <rich-text-area-input
            name="description"
            :allow-fullscreen="true"
            :form="templateForm"
            :label="$t('common.labels.description')"
            :required="true"
          />
          <text-input
            name="image_url"
            :form="templateForm"
            :label="$t('form_editor.template_modal.image')"
            :required="true"
          />
          <select-input
            name="types"
            :form="templateForm"
            :label="$t('form_editor.template_modal.types')"
            :options="typesOptions"
            :multiple="true"
            :searchable="true"
          />
          <select-input
            name="industries"
            :form="templateForm"
            :label="$t('form_editor.template_modal.industries')"
            :options="industriesOptions"
            :multiple="true"
            :searchable="true"
          />
          <select-input
            name="related_templates"
            :form="templateForm"
            :label="$t('form_editor.template_modal.related_templates')"
            :options="templatesOptions"
            :multiple="true"
            :searchable="true"
          />
          <questions-editor
            name="questions"
            :questions="templateForm.questions"
            :label="$t('form_editor.template_modal.faq')"
          />
        </div>
      </v-form>
    </template>

    <template #footer>
      <div class="flex justify-end gap-x-2 w-full">
        <UButton
          color="neutral"
          variant="outline"
          @click="close"
          :label="$t('common.actions.close')"
        />
        <UButton
          v-if="template"
          color="error"
          variant="outline"
          @click="
            useAlert().confirm(
              $t('form_editor.template_modal.delete_confirm'),
              deleteFormTemplate,
            )
          "
          :label="$t('form_editor.template_modal.delete_template')"
        />
        <div class="grow"/>
        <UButton
          class="px-8"
          :loading="createMutation.isPending.value || updateMutation.isPending.value"
          @click="onSubmit"
          :label="template ? $t('common.actions.update') : $t('common.actions.create')"
        />
      </div>
    </template>
  </UModal>
</template>

<script setup>
import { ref, defineProps, defineEmits, computed, watch, onMounted } from "vue"
import QuestionsEditor from "./QuestionsEditor.vue"
import { useTemplateMeta } from "~/composables/data/useTemplateMeta"

const props = defineProps({
  show: { type: Boolean, required: true },
  form: { type: Object, required: true },
  template: { type: Object, required: false, default: () => {} },
})

const crisp = useCrisp()
const router = useRouter()
const { t } = useI18n()
const { data: user } = useAuth().user()

const { list, create, update, remove } = useTemplates()

const { industries: industriesMap, types: typesMap } = useTemplateMeta()

const industries = computed(() => [...(industriesMap.value?.values() ?? [])])
const types = computed(() => [...(typesMap.value?.values() ?? [])])

const templateForm = ref(null)
const emit = defineEmits(["close"])

// Modal state
const isOpen = computed({
  get() {
    return props.show
  },
  set(value) {
    if (!value) {
      close()
    }
  }
})

// Initialize templates query - this needs to be called at setup level
const templatesQuery = list({ enabled: false })

// Enable the query when modal is opened
watch(isOpen, (open) => {
  if (open) {
    templatesQuery.refetch()
  }
})

onMounted(() => {
  templateForm.value = useForm(
    props.template ?? {
      publicly_listed: false,
      name: "",
      slug: "",
      short_description: "",
      description: "",
      image_url: "",
      types: [],
      industries: [],
      related_templates: [],
      questions: [],
    },
  )
})

const typesOptions = computed(() => {
  return Object.values(types.value).map((type) => {
    return {
      name: type.name,
      value: type.slug,
    }
  })
})
const industriesOptions = computed(() => {
  return Object.values(industries.value).map((industry) => {
    return {
      name: industry.name,
      value: industry.slug,
    }
  })
})
const templatesOptions = computed(() => {
  if (!templatesQuery.data.value) return []
  return Object.values(templatesQuery.data.value).map((template) => {
    return {
      name: template.name,
      value: template.slug,
    }
  })
})

const close = () => {
  emit("close")
}

const createMutation = create()
const updateMutation = update()
const deleteMutation = remove()

const onSubmit = () => {
  if (props.template) {
    updateFormTemplate()
  } else {
    createFormTemplate()
  }
}
const createFormTemplate = () => {
  templateForm.value.form = props.form
  createMutation.mutateAsync(templateForm.value).then(() => {
    useAlert().success(t("form_editor.template_modal.created"))
    emit("close")
  }).catch((error) => {
    useAlert().error(error.message)
  })
}
const updateFormTemplate = () => {
  templateForm.value.form = props.form
  updateMutation.mutateAsync({ id: props.template.id, data: templateForm.value }).then(() => {
    useAlert().success(t("form_editor.template_modal.updated"))
    emit("close")
  }).catch((error) => {
    useAlert().error(error.message)
  })
}
const deleteFormTemplate = () => {
  if (!props.template) return
  deleteMutation.mutateAsync(props.template.id).then(() => {
    useAlert().success(t("form_editor.template_modal.deleted"))
    router.push({ name: "templates" })
    emit("close")
  }).catch((error) => {
    useAlert().error(error.message)
  })
}
</script>
