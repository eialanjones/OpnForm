<template>
  <AdminCard
    v-if="props.user.stripe_id"
    :title="$t('admin.discount.title')"
    icon="heroicons:tag-20-solid"
  >
    <form
      class="space-y-6 flex flex-col justify-between"
      @submit.prevent="applyDiscount"
    >
      <p class="text-xs text-neutral-500">
        {{ $t('admin.discount.description') }}
      </p>
      <UButton
        :loading="form.busy"
        type="submit"
        block
        :label="$t('admin.discount.apply_button')"
      />
    </form>
  </AdminCard>
</template>

<script setup>
const props = defineProps({
  user: { type: Object, required: true }
})

const form = useForm({
  user_id: props.user.id
})

const applyDiscount = () => {
  if (!props.user.stripe_id) return
  form
    .patch('/moderator/apply-discount')
    .then(async (data) => {
      useAlert().success(data.message)
    })
    .catch((error) => {
      useAlert().error(error.data.message)
    })
}

</script>
