<template>
  <AdminCard
    :title="isBlocked ? $t('admin.block_user.unblock_title') : $t('admin.block_user.block_title')"
    :icon="isBlocked ? 'heroicons:lock-open-20-solid' : 'heroicons:no-symbol-20-solid'"
  >
    <div class="space-y-6 flex flex-col justify-between">
      <UAlert
        :icon="alertContent.icon"
        :color="alertContent.color"
        :variant="alertContent.variant"
      >
        <template #description>
          <div v-html="alertContent.content" />
        </template>
      </UAlert>

      <VForm @submit.prevent="submit">
        <TextAreaInput
          :label="$t('admin.block_user.reason_label')"
          name="reason"
          :form="form"
          :required="true"
          :help="$t('admin.block_user.reason_help')"
        />
        <div class="flex space-x-2 mt-4">
          <UButton
            block
            :loading="form.busy"
            type="submit"
            class="grow"
            :label="isBlocked ? $t('admin.block_user.unblock_title') : $t('admin.block_user.block_title')"
          />
          <UButton
            v-if="blockingHistory && blockingHistory.length"
            variant="outline"
            icon="i-heroicons-clock"
            @click="isModalOpen = true"
            :label="$t('admin.block_user.view_history')"
          />
        </div>
      </VForm>
    </div>
    <UModal 
      v-model:open="isModalOpen"
      :ui="{ content: 'sm:max-w-4xl' }"
      :title="$t('admin.block_user.history_title')"
    >
      <template #body>
        <UTable 
          :columns="historyColumns"
          :data="blockingHistory"
        />
      </template>
    </UModal>
  </AdminCard>
</template>

<script setup>


import { escapeHtml } from '~/lib/utils'

const props = defineProps({
  user: { type: Object, required: true }
})
const emit = defineEmits(['user-updated'])

const { t } = useI18n()
const alert = useAlert()
const isModalOpen = ref(false)

const form = useForm({
  user_id: props.user.id,
  reason: ''
})

const historyColumns = computed(() => [
  {
    accessorKey: 'blocked_at',
    header: t('admin.block_user.columns.blocked_at'),
    cell: ({ row }) => {
      return row.original.blocked_at ? new Date(row.original.blocked_at).toLocaleString() : ''
    }
  },
  {
    accessorKey: 'blocked_by',
    header: t('admin.block_user.columns.blocked_by'),
    cell: ({ row }) => {
      return row.original.blocked_by ? row.original.blocked_by : 'AI'
    }
  },
  {
    accessorKey: 'reason',
    header: t('admin.block_user.columns.reason')
  },
  {
    accessorKey: 'unblocked_at',
    header: t('admin.block_user.columns.unblocked_at'),
    cell: ({ row }) => {
      return row.original.unblocked_at ? new Date(row.original.unblocked_at).toLocaleString() : ''
    }
  },
  {
    accessorKey: 'unblocked_by',
    header: t('admin.block_user.columns.unblocked_by')
  },
  {
    accessorKey: 'unblock_reason',
    header: t('admin.block_user.columns.unblock_reason')
  }
])

const isBlocked = computed(() => props.user.is_blocked)
const blockingHistory = computed(() => props.user.meta?.blocking_history || [])
const lastBlock = computed(() => {
  if (!blockingHistory.value.length) {
    return null
  }
  return blockingHistory.value[blockingHistory.value.length - 1]
})

const alertContent = computed(() => {
  if (isBlocked.value) {
    const blockedBy = escapeHtml(lastBlock.value?.blocked_by || t('admin.block_user.auto_blocked_by_ai'))
    const blockedOn = escapeHtml(new Date(props.user.blocked_at).toLocaleString())
    const reason = escapeHtml(lastBlock.value?.reason || '')
    return {
      icon: 'i-heroicons-exclamation-triangle',
      color: 'error',
      variant: 'subtle',
      content: `
        ${t('admin.block_user.unblock_warning')}
        <div class="mt-2">
          <b>${t('admin.block_user.blocked_on')}</b> ${blockedOn}
          <br>
          <b>${t('admin.block_user.blocked_by')}</b> ${blockedBy}
          <br>
          <b>${t('admin.block_user.reason')}</b> ${reason}
        </div>
      `
    }
  } else {
    return {
      icon: 'i-heroicons-exclamation-triangle',
      color: 'warning',
      variant: 'subtle',
      content: t('admin.block_user.block_warning')
    }
  }
})


async function submit() {
  try {
    let response
    if (isBlocked.value) {
      response = await form.post('/moderator/unblock-user')
    } else {
      response = await form.post('/moderator/block-user')
    }
    alert.success(response.message)
    emit('user-updated', response.user)
    form.reset()
  } catch (error) {
    alert.error(error.data?.message || t('admin.errors.generic'))
  }
}
</script> 