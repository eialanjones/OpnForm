<template>
  <AdminCard
    v-if="props.user.stripe_id"
    :title="$t('admin.extend_trial.title')"
    icon="heroicons:calendar-16-solid"
  >
    <form
      class="space-y-6 flex flex-col justify-between"
      @submit.prevent="extendTrial"
    >
      <p class="text-xs text-neutral-500">
        {{ $t('admin.extend_trial.description') }}
      </p>
      <div>
        <TextInput
          name="number_of_day"
          :form="form"
          :label="$t('admin.extend_trial.days_label')"
          native-type="day"
          :required="true"
          :help="$t('admin.extend_trial.days_help')"
          placeholder="7"
        />
        <UButton
          :loading="form.busy"
          type="submit"
          block
          :label="$t('admin.extend_trial.apply_button')"
        />
      </div>
    </form>
  </AdminCard>
</template>

<script setup>
const props = defineProps({
  user: { type: Object, required: true }
})

const form = useForm({
  user_id: props.user.id,
  number_of_day: ''
})

const extendTrial = () => {
  if (!props.user.stripe_id) return
  form
    .patch('/moderator/extend-trial')
    .then(async (data) => {
      useAlert().success(data.message)
    })
    .catch((error) => {
      useAlert().error(error.data.message)
    })
}

</script>
