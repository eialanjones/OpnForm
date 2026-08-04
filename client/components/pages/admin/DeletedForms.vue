<template>
  <AdminCard
    :title="$t('admin.deleted_forms.title')"
    icon="heroicons:trash-16-solid"
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
      <template #actions-cell="{ row }">
        <UButton
          :loading="restoringForm"
          size="sm"
          color="neutral"
          variant="outline"
          @click.prevent="restoreForm(row.original.slug)"
        >
          {{ $t('admin.deleted_forms.restore') }}
        </UButton>
      </template>
    </UTable>
    <div 
      v-if="forms?.length > pageCount"
      class="flex justify-end px-3 py-3.5 border-t border-neutral-200 dark:border-neutral-700"
    >
      <UPagination
        v-model:page="page"
        :items-per-page="pageCount"
        :total="forms.length"
      />
    </div>
  </AdminCard>
</template>

<script setup>
import { adminApi } from '~/api'

const { t } = useI18n()

const props = defineProps({
    user: { type: Object, required: true }
})

const loading = ref(true)
const restoringForm = ref(false)
const forms = ref([])
const page = ref(1)
const pageCount = 5

const rows = computed(() => {
    return forms.value.slice((page.value - 1) * pageCount, (page.value) * pageCount)
})
onMounted(() => {
    getDeletedForms()
})

const getDeletedForms = () => {
    loading.value = true
    adminApi.forms.getDeleted(props.user.id).then(data => {
        loading.value = false
        forms.value = data.forms
    }).catch(error => {
        useAlert().error(error.message)
        loading.value = false
    })
}

const restoreForm = (slug) => {
    return useAlert().confirm(
        t("admin.deleted_forms.restore_confirm"),
        () => {
            restoringForm.value = true
            adminApi.forms.restore(slug).then(data => {
                restoringForm.value = false
                useAlert().success(data.message)
                getDeletedForms()
            }).catch(error => {
                restoringForm.value = false
                useAlert().error(error.data.message)
            })
        })
}


const columns = computed(() => [{
    accessorKey: 'id',
    header: t('admin.table.id')
}, {
    accessorKey: 'slug',
    header: t('admin.deleted_forms.columns.slug'),
    sortable: true
}, {
    accessorKey: 'title',
    header: t('common.labels.title'),
    sortable: true
}, {
    accessorKey: 'created_by',
    header: t('admin.deleted_forms.columns.created_by'),
    sortable: true
}, {
    accessorKey: 'deleted_at',
    header: t('admin.deleted_forms.columns.deleted_at'),
    sortable: true,
}, {
    id: 'actions',
    header: t('admin.deleted_forms.restore'),
    sortable: false,
}])

</script>
