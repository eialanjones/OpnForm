<template>
  <div v-if="form">
    <UDropdownMenu
      class="z-50"
      arrow
      :items="items"
      :content="content"
      :modal="false"
      :portal="portal"
    >
      <slot :loading="deleteFormMutation.isPending.value || duplicateFormMutation.isPending.value">
      <UButton
        color="neutral"
        variant="outline"
        icon="i-heroicons-ellipsis-horizontal"
        size="md"
        :loading="deleteFormMutation.isPending.value || duplicateFormMutation.isPending.value"
      />
      </slot>
    </UDropdownMenu>

    <!-- Delete Form Modal -->
    <UModal
      v-model:open="showDeleteFormModal"
      :ui="{ content: 'sm:max-w-sm' }"
      :title="$t('form_pages.extra_menu.delete_modal_title')"
    >
      <template #body>
        <p>
          {{ $t('form_pages.extra_menu.delete_modal_body') }}
        </p>
      </template>

      <template #footer>
        <div class="flex justify-between gap-3 w-full">
          <UButton
            color="neutral"
            variant="outline"
            @click="showDeleteFormModal = false"
            :label="$t('common.actions.cancel')"
          />
          <UButton
            color="error"
            :loading="deleteFormMutation.isPending.value"
            @click="deleteForm"
            :label="$t('form_pages.extra_menu.delete_confirm')"
          />
        </div>
      </template>
    </UModal>
    <form-template-modal
      v-if="!isMainPage && user"
      :form="form"
      :show="showFormTemplateModal"
      @close="showFormTemplateModal = false"
    />
    <form-workspace-modal
      v-if="user"
      :form="form"
      :show="showFormWorkspaceModal"
      @close="showFormWorkspaceModal = false"
    />
  </div>
</template>

<script setup>
import { ref, defineProps, computed } from "vue"
import FormTemplateModal from "../../../open/forms/components/templates/FormTemplateModal.vue"
import FormWorkspaceModal from "../../../open/forms/components/FormWorkspaceModal.vue"

const { copy } = useClipboard()
const { t } = useI18n()
const router = useRouter()

const props = defineProps({
  form: { type: Object, required: true },
  isMainPage: { type: Boolean, required: false, default: false },
  content: { 
    type: Object, 
    required: false, 
    default: () => ({side: 'bottom', align: 'end'}) 
  },
  portal: { type: [Boolean, String], required: false, default: false }
})

const { data: user } = useAuth().user()
const { current: workspace } = useCurrentWorkspace()

const { remove, duplicate } = useForms()

const showDeleteFormModal = ref(false)
const showFormTemplateModal = ref(false)
const showFormWorkspaceModal = ref(false)

const deleteFormMutation = remove()
const duplicateFormMutation = duplicate()

const items = computed(() => {
  return [
    [
      ...props.isMainPage ? [{
        label: t('form_pages.extra_menu.open_form'),
        icon: 'i-heroicons-arrow-top-right-on-square',
        onClick: () => {
          if (props.isMainPage && props.form.visibility === 'draft') {
            showDraftFormWarningNotification()
          } else {
            window.open(props.form.share_url, '_blank')
          }
        }
      }] : [],
      {
        label: t('form_pages.extra_menu.copy_link_to_share'),
        icon: 'i-heroicons-clipboard-document-check-20-solid',
        onClick: copyLink
      }
    ],
    ...workspace.value?.is_readonly ? [] : [
      [
        ...props.isMainPage ? [{
        label: t('common.actions.edit'),
        icon: 'i-heroicons-pencil-square-20-solid',
        to: { name: 'forms-slug-edit', params: { slug: props.form.slug } }
      }] : [],
      {
        label: t('form_pages.extra_menu.duplicate_form'),
        icon: 'i-heroicons-document-duplicate-20-solid',
        onClick: duplicateForm
        
      }], 
    [
      ...props.isMainPage ? [] : [{
        label: t('form_pages.extra_menu.create_template'),
        icon: 'i-heroicons-document-plus-20-solid',
        onClick: () => {
          showFormTemplateModal.value = true
        }
      }],
      {
        label: t('form_pages.extra_menu.change_workspace'),
        icon: 'i-heroicons-building-office-2-20-solid',
        onClick: () => {
          showFormWorkspaceModal.value = true
        }
      },
    ],[
      {
        label: t('form_pages.extra_menu.delete_form'),
        icon: 'i-heroicons-trash-20-solid',
        onClick: () => {
          showDeleteFormModal.value = true
        },
        class: 'text-red-800 hover:bg-red-50 hover:text-red-600 group',
        iconClass: 'text-red-900 group-hover:text-red-800'
      }
      ]
    ]
  ].filter((group) => group.length > 0)
})

const copyLink = () => {
  copy(props.form.share_url)
  useAlert().success(t('common.states.copied'))
}

const duplicateForm = () => {
  duplicateFormMutation.mutateAsync(props.form.id).then((data) => {
    router.push({
      name: "forms-slug-show",
      params: { slug: data.new_form.slug },
    })
    useAlert().success(data.message)
  }).catch((error) => {
    useAlert().error(error.data?.message || t('form_pages.extra_menu.duplicate_failed'))
  })
}

const deleteForm = () => {
  deleteFormMutation.mutateAsync(props.form.id).then((data) => {
    useAlert().success(data.message)
    showDeleteFormModal.value = false
    router.push({ name: "home" })
  }).catch((error) => {
    useAlert().error(error.data?.message || t('form_pages.extra_menu.delete_failed'))
  })
}

const showDraftFormWarningNotification = () => {
  useAlert().warning(t('form_pages.shared.draft_warning'))
}
</script>
