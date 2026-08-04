<template>
  <UModal v-model:open="isOpen" @close="handleCancel">
    <template #header>
      <h3 class="text-lg font-semibold">{{ modalTitle }}</h3>
    </template>

    <template #body>
      <div class="flex justify-center">
        <VForm :form="form" size="sm" @submit.prevent="handleSave" class="w-full max-w-md space-y-6">
          <div class="space-y-4 rounded-xl border border-neutral-200 bg-neutral-50 p-4 text-sm text-neutral-600">
            <p class="text-xs uppercase tracking-wide text-neutral-500">{{ $t('workspace.oidc_modal.section_general') }}</p>
            <TextInput
              name="name"
              :form="form"
              :label="$t('workspace.oidc_modal.name_label')"
              :required="true"
              :placeholder="$t('workspace.oidc_modal.name_placeholder')"
            />

            <TextInput
              name="slug"
              :form="form"
              :label="$t('workspace.oidc_modal.slug_label')"
              :required="true"
              :placeholder="$t('workspace.oidc_modal.slug_placeholder')"
              :help="$t('workspace.oidc_modal.slug_help')"
            />

            <TextInput
              name="domain"
              :form="form"
              :label="$t('workspace.oidc_modal.domain_label')"
              :required="true"
              :placeholder="userEmailDomain || 'example.com'"
            >
              <template #help>
                <div class="space-y-1">
                  <p v-if="userEmailDomain" class="text-xs text-neutral-500">
                    {{ $t('workspace.oidc_modal.domain_help_with_domain', { domain: userEmailDomain }) }}
                  </p>
                  <p v-else class="text-xs text-neutral-500">
                    {{ $t('workspace.oidc_modal.domain_help_generic') }}
                  </p>
                  <p class="text-xs text-neutral-500">
                    <i18n-t
                      keypath="workspace.oidc_modal.domain_help_providers"
                      scope="global"
                      tag="span"
                    >
                      <template #support>
                        <button
                          type="button"
                          class="text-blue-600 hover:text-blue-700 underline"
                          @click="openSupportChat"
                        >
                          {{ $t('workspace.oidc_modal.contact_support') }}
                        </button>
                      </template>
                    </i18n-t>
                  </p>
                </div>
              </template>
            </TextInput>
          </div>

          <div class="space-y-4 rounded-xl border border-neutral-200 bg-neutral-50 p-4 text-sm text-neutral-600">
            <p class="text-xs uppercase tracking-wide text-neutral-500">{{ $t('workspace.oidc_modal.section_security') }}</p>

            <!-- Redirect URI Display -->
            <div v-if="redirectUri" class="rounded-lg border border-blue-200 bg-blue-50 p-3">
              <p class="text-xs font-medium text-blue-900 mb-1">{{ $t('workspace.oidc_modal.redirect_uri_title') }}</p>
              <p class="text-xs text-blue-700 mb-2">
                {{ $t('workspace.oidc_modal.redirect_uri_description') }}
              </p>
              <CopyContent
                :content="redirectUri"
                :label="$t('common.actions.copy')"
              />
            </div>

            <TextInput
              name="issuer"
              :form="form"
              :label="$t('workspace.oidc_modal.issuer_label')"
              :required="true"
              placeholder="https://idp.example.com"
              :help="$t('workspace.oidc_modal.issuer_help')"
            />

            <TextInput
              name="client_id"
              :form="form"
              :label="$t('workspace.oidc_modal.client_id_label')"
              :required="true"
              :placeholder="$t('workspace.oidc_modal.client_id_placeholder')"
            />

            <TextInput
              name="client_secret"
              :form="form"
              :label="$t('workspace.oidc_modal.client_secret_label')"
              :required="true"
              type="password"
              :placeholder="$t('workspace.oidc_modal.client_secret_placeholder')"
            />

            <ToggleSwitchInput
              name="options.require_state"
              :form="form"
              :label="$t('workspace.oidc_modal.require_state_label')"
              :help="$t('workspace.oidc_modal.require_state_help')"
            />

            <ToggleSwitchInput
              name="enabled"
              class="mt-2"
              :form="form"
              :label="$t('common.states.enabled')"
            />
          </div>

          <Collapse v-model="showFieldMappings" class="rounded-xl border border-neutral-200 bg-neutral-50 p-4 text-sm text-neutral-600">
            <template #title>
              <p class="text-xs uppercase tracking-wide text-neutral-500">{{ $t('workspace.oidc_modal.section_field_mappings') }}</p>
            </template>
            <div class="space-y-4 pt-2">
              <p class="text-xs text-neutral-500">
                {{ $t('workspace.oidc_modal.field_mappings_description') }}
              </p>

              <div class="space-y-2">
                <TextInput
                  name="options.field_mappings.email"
                  :form="form"
                  :label="$t('workspace.oidc_modal.email_field_label')"
                  placeholder="email"
                  :help="$t('workspace.oidc_modal.email_field_help')"
                  size="sm"
                />
                <TextInput
                  name="options.field_mappings.name"
                  :form="form"
                  :label="$t('workspace.oidc_modal.name_field_label')"
                  placeholder="name"
                  :help="$t('workspace.oidc_modal.name_field_help')"
                  size="sm"
                />
              </div>
            </div>
          </Collapse>

          <Collapse v-model="showRoleMapping" class="rounded-xl border border-neutral-200 bg-neutral-50 p-4 text-sm text-neutral-600">
            <template #title>
              <p class="text-xs uppercase tracking-wide text-neutral-500">{{ $t('workspace.oidc_modal.section_role_mapping') }}</p>
            </template>
            <div class="space-y-4 pt-2">
              <p class="text-xs text-neutral-500">
                {{ $t('workspace.oidc_modal.role_mapping_description') }}
              </p>

              <div v-if="roleMappings.length === 0" class="text-center py-4 text-sm text-neutral-400">
                {{ $t('workspace.oidc_modal.role_mapping_empty') }}
              </div>
              
              <div v-else class="space-y-2">
                <div
                  v-for="(mapping, index) in roleMappings"
                  :key="index"
                  class="flex items-start gap-2 p-2 rounded-md border border-neutral-200 bg-white"
                >
                  <div class="flex-1 grid grid-cols-2 gap-2">
                    <TextInput
                      :name="`options.group_role_mappings.${index}.idp_group`"
                      :form="form"
                      :label="$t('workspace.oidc_modal.idp_group_label')"
                      placeholder="opnform_admins"
                      :required="true"
                      size="sm"
                    />
                    <SelectInput
                      :name="`options.group_role_mappings.${index}.role`"
                      :form="form"
                      :label="$t('workspace.fields.role')"
                      :required="true"
                      size="sm"
                      :options="roleOptions"
                    />
                  </div>
                  <UButton
                    size="xs"
                    variant="ghost"
                    color="red"
                    icon="i-heroicons-trash"
                    square
                    class="mt-6"
                    @click="removeRoleMapping(index)"
                  />
                </div>
              </div>
              
              <div v-if="showRoleMapping" class="flex justify-end">
                <UButton
                  size="xs"
                  variant="ghost"
                  icon="i-heroicons-plus"
                  @click="addRoleMapping"
                >
                  {{ $t('workspace.oidc_modal.add_mapping') }}
                </UButton>
              </div>
            </div>
          </Collapse>
        </VForm>
      </div>
    </template>

    <template #footer>
      <div class="flex justify-between gap-2 w-full">
        <UButton variant="ghost" color="neutral" @click="handleCancel">{{ $t('common.actions.cancel') }}</UButton>
        <UButton type="submit" :loading="isBusy" :disabled="isBusy" @click="handleSave">
          {{ actionLabel }}
        </UButton>
      </div>
    </template>
  </UModal>
</template>

<script setup>
import Collapse from '@/components/app/Collapse.vue'
import CopyContent from '@/components/open/forms/components/CopyContent.vue'
import { appUrl } from '~/lib/utils.js'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  form: { type: Object, required: true },
  connection: { type: Object, default: null },
  isBusy: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'save', 'cancel'])

const { t } = useI18n()

const isEditing = computed(() => !!props.connection)
const modalTitle = computed(() => (isEditing.value ? t('workspace.oidc_modal.edit_title') : t('workspace.oidc_modal.add_title')))
const actionLabel = computed(() => (isEditing.value ? t('common.actions.update') : t('common.actions.create')))

const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

// Get user email domain
const { data: user } = useAuth().user()
const userEmailDomain = computed(() => {
  if (!user.value?.email) return null
  const email = user.value.email
  const parts = email.split('@')
  return parts.length === 2 ? parts[1] : null
})

// Pre-fill domain field with user's email domain when creating new connection
watch([isOpen, userEmailDomain], ([isOpenVal, domain]) => {
  if (isOpenVal && !isEditing.value && domain && !props.form.domain) {
    props.form.domain = domain
  }
})

// Open Crisp chat for support
const openSupportChat = () => {
  useCrisp().openChat()
}

const roleOptions = computed(() => [
  { value: 'member', name: t('workspace.roles.member') },
  { value: 'editor', name: t('workspace.roles.editor') },
  { value: 'admin', name: t('workspace.roles.admin') },
  { value: 'owner', name: t('workspace.roles.owner') },
])

const roleMappings = computed({
  get: () => {
    return props.form.options?.group_role_mappings || []
  },
  set: (value) => {
    if (!props.form.options) {
      props.form.options = {}
    }
    props.form.options.group_role_mappings = value
  }
})

const hasFieldMappings = computed(() => {
  const mappings = props.form.options?.field_mappings || {}
  return !!(mappings.email || mappings.name)
})

const showFieldMappings = ref(hasFieldMappings.value)

const showRoleMapping = ref(roleMappings.value.length > 0)

const redirectUri = computed(() => {
  const slug = props.form.slug
  if (!slug) return null
  
  // Use appUrl helper to construct the redirect URI
  return appUrl(`/auth/${slug}/callback`)
})

watch(hasFieldMappings, (newVal) => {
  if (!showFieldMappings.value && newVal) {
    showFieldMappings.value = true
  }
})

watch(roleMappings, (newVal) => {
  if (!showRoleMapping.value && newVal.length > 0) {
    showRoleMapping.value = true
  }
}, { deep: true })

const addRoleMapping = () => {
  const current = roleMappings.value || []
  roleMappings.value = [...current, { idp_group: '', role: 'member' }]
  if (!showRoleMapping.value) {
    showRoleMapping.value = true
  }
}

const removeRoleMapping = (index) => {
  const current = roleMappings.value || []
  roleMappings.value = current.filter((_, i) => i !== index)
}

const handleSave = () => emit('save')
const handleCancel = () => {
  emit('cancel')
  emit('update:modelValue', false)
}
</script>

