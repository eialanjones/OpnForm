<template>
  <div>
    <div class="flex mt-6 mb-10">
      <div class="w-full md:w-2/3 md:mx-auto md:max-w-md px-4">
        <h1 class="my-6">
          {{ $t('auth.password_email.title') }}
        </h1>
        <form
          @submit.prevent="send"
        >
          <UAlert
            v-if="status"
            color="success"
            variant="subtle"
            :description="status"
            icon="i-heroicons-check-circle"
            class="mb-4"
          />

          <!-- Email -->
          <text-input
            name="email"
            :form="form"
            :label="$t('common.labels.email')"
            :required="true"
          />

          <!-- Submit Button -->
          <UButton
            class="w-full"
            :loading="form.busy"
            type="submit"
            :label="$t('auth.password_email.submit')"
          />
        </form>
      </div>
    </div>
    <open-form-footer />
  </div>
</template>

<script>
export default {
  setup() {
    const { t } = useI18n()

    definePageMeta({
      middleware: "guest",
    })
    useOpnSeoMeta({
      title: t("auth.password_email.page_title"),
    })
  },

  data: () => ({
    status: "",
    form: useForm({
      email: "",
    }),
  }),

  methods: {
    async send() {
      const { data } = await this.form.post("/password/email")

      this.status = data.status

      this.form.reset()
    },
  },
}
</script>
