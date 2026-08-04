<template>
  <UModal
    v-model:open="isOpen"
    @close="closeModal"
  >
    <template #header>
      <div class="flex items-center w-full gap-4 px-2">
        <h2 class="font-semibold">
          {{ $t('user_settings.access_tokens.modal.title') }}
        </h2>
      </div>
      <UButton
        color="neutral"
        variant="outline"
        icon="i-heroicons-question-mark-circle"
        size="sm"
        @click="crisp.openHelpdesk()"
      >
        {{ $t('user_settings.access_tokens.modal.help_button') }}
      </UButton>
    </template>
 
    <template #body>
      <template v-if="token">
        <UAlert
          icon="i-heroicons-key-20-solid"
          color="success"
          variant="subtle"
          :title="$t('user_settings.access_tokens.modal.token_ready_title')"
          :description="$t('user_settings.access_tokens.modal.token_ready_description')"
        />
        <CopyContent
          class="mt-4"
          :content="token"
          :label="$t('user_settings.access_tokens.modal.copy_token')"
        />
      </template>

      <VForm v-else size="sm">
        <form
          @submit.prevent="createToken"
        >
          <div v-if="!token">
            <TextInput
              :form="tokenForm"
              name="name"
              :required="true"
              :label="$t('common.labels.name')"
            />

            <FlatSelectInput
              :form="tokenForm"
              name="abilities"
              :label="$t('user_settings.access_tokens.abilities')"
              :options="abilitiesOptions"
              multiple
            />
          </div>
        </form>
      </VForm>
    </template>

    <template #footer>
      <UButton
        color="neutral"
        variant="outline"
        @click="closeModal"
      >
        {{ $t('common.actions.close') }}
      </UButton>
      <UButton
        v-if="!token"
        type="submit"
        block
        size="lg"
        :loading="tokenForm.busy"
        @click="createToken"
      >
        {{ $t('user_settings.access_tokens.modal.create_button') }}
      </UButton>
    </template>
  </UModal>
</template>

<script setup>
import CopyContent from "~/components/open/forms/components/CopyContent.vue"

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close'])

const { abilities, create } = useTokens()
const alert = useAlert()
const { t } = useI18n()

const abilitiesOptions = computed(() => abilities.map(ability => ({
  name: ability.title,
  value: ability.name
})))

const token = ref('')
const tokenForm = useForm({
  name: "",
  abilities: abilitiesOptions.value.map(ability => ability.value),
})

// Create token mutation
const createTokenMutation = create()

// Modal state
const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('close', value)
})

// Methods
const closeModal = () => {
  tokenForm.reset()
  token.value = ''
  isOpen.value = false
}

function createToken() {
  tokenForm.mutate(createTokenMutation).then((response) => {
    // Assuming the response contains the token
    token.value = response.token || response.data?.token || response
  }).catch(() => {
    alert.error(t('user_settings.access_tokens.modal.create_error'))
  })
}
</script>
