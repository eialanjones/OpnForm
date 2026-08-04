<template>
  <AdminCard
    v-if="props.user.stripe_id"
    :title="$t('admin.subscriptions.title')"
    icon="heroicons:credit-card-16-solid"
  >
    <UTable
      :loading="loading"
      :loading-state="{ icon: 'i-heroicons-arrow-path-20-solid', label: $t('common.states.loading') }"
      :progress="{ color: 'primary', animation: 'carousel' }"
      :empty-state="{ icon: 'i-heroicons-circle-stack-20-solid', label: $t('admin.table.no_items') }"
      :columns="columns"
      :data="rows"
      class="-mx-6"
    >
      <template #stripe_id-cell="{ row }">
        <a
          :href="'https://dashboard.stripe.com/subscriptions/' + row.original.stripe_id"
          target="_blank"
          class="text-xs select-all bg-purple-50 border-purple-200 text-purple-500 rounded-md px-2 py-1 border"
        >
          <Icon
            name="bx:bxl-stripe"
            class="h-4 w-4 inline-block"
          />
          {{ row.original.stripe_id }}
        </a>
      </template>
      <template #status-cell="{ row }">
        <span
          class="text-xs select-all rounded-md px-2 py-1 border"
          :class="row.original.status == 'active' ? 'bg-green-50 border-green-200 text-green-500' : 'bg-yellow-50 border-yellow-200 text-yellow-500'"
        >
          {{ row.original.status }}
        </span>
      </template>
      <template #actions-cell="{ row }">
        <UButton
          v-if="['active', 'trialing'].includes(row.original.status) && !row.original.canceled_at"
          color="error"
          variant="outline"
          size="sm"
          icon="heroicons:trash-16-solid"
          @click="openCancelSubscriptionModal(row.original)"
        />
      </template>
    </UTable>
    <div
      v-if="subscriptions?.length > pageCount"
      class="flex justify-end px-3 py-3.5 border-t border-neutral-200 dark:border-neutral-700"
    >
      <UPagination
        v-model:page="page"
        :items-per-page="pageCount"
        :total="subscriptions.length"
      />
    </div>

    <UModal
      v-model:open="showCancelSubscriptionModal"
      :ui="{ content: 'sm:max-w-lg' }"
      :title="$t('admin.subscriptions.cancel_modal_title')"
    >
      <template #body>
        <form @submit.prevent="askCancel">
            <p class="text-xs text-neutral-500">
              {{ $t('admin.subscriptions.cancel_notice') }}
            </p>
            <div class="mt-4">
              <TextInput
                name="cancellation_reason"
                :form="form"
                :label="$t('admin.subscriptions.cancellation_reason_label')"
                native-type="reason"
                :required="true"
                :help="$t('admin.subscriptions.cancellation_reason_help')"
              />

              <UButton
                class="mt-4"
                :loading="form.busy"
                type="submit"
                block
                icon="heroicons:exclamation-triangle-16-solid"
                :label="$t('admin.subscriptions.cancel_now')"
              />
            </div>
          </form>
        </template>
    </UModal>

  </AdminCard>
</template>

<script setup>
import { adminApi } from '~/api'

const props = defineProps({
  user: { type: Object, required: true }
})

const { t } = useI18n()
const loading = ref(true)
const subscriptions = ref([])
const page = ref(1)
const pageCount = 5

const rows = computed(() => {
  return subscriptions.value.slice((page.value - 1) * pageCount, (page.value) * pageCount)
})
onMounted(() => {
    getSubscriptions()
})

const getSubscriptions = () => {
    if (!props.user.stripe_id) return
    loading.value = true
    adminApi.billing.getSubscriptions(props.user.id).then(data => {
        loading.value = false
        subscriptions.value = data.subscriptions
    }).catch(error => {
        useAlert().error(error.data.message)

        loading.value = false
    })
}


const columns = computed(() => [{
    accessorKey: 'id',
    header: t('admin.table.id')
}, {
    accessorKey: 'stripe_id',
    header: t('admin.subscriptions.columns.stripe_id')
}, {
    accessorKey: 'name',
    header: t('common.labels.name'),
    sortable: true
}, {
    accessorKey: 'creation_date',
    header: t('admin.table.creation_date'),
    sortable: true
}, {
    accessorKey: 'plan',
    header: t('admin.table.plan'),
    sortable: true,
    direction: 'desc'
}, {
    accessorKey: 'status',
    header: t('common.labels.status')
},{
    accessorKey: 'canceled_at',
    header: t('admin.subscriptions.columns.canceled_at')
}, {
    accessorKey: 'actions',
    header: '',
}])

const showCancelSubscriptionModal = ref(false)
const alert = useAlert()
const form = useForm({
  user_id: props.user.id,
  subscription_id: null,
  cancellation_reason: null
})

watch(showCancelSubscriptionModal, (value) => {
  if (!value) {
    form.subscription_id = null
    form.cancellation_reason = null
  }
})

const openCancelSubscriptionModal = (subscription) => {
  form.subscription_id = subscription.id
  showCancelSubscriptionModal.value = true
}

const askCancel = () => {
  alert.confirm(t('admin.subscriptions.cancel_confirm'), cancelSubscription)
}

const cancelSubscription = () => {
  if (!props.user.stripe_id) return
  form
    .patch('/moderator/cancellation-subscription')
    .then(async (data) => {
      alert.success(data.message)
      showCancelSubscriptionModal.value = false
      getSubscriptions()
    })
    .catch((error) => {
      alert.error(error.data.message)
    })
}

</script>
