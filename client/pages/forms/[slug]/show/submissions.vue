<template>
  <FormSubmissions :form="form" />
</template>

<script setup>
import FormSubmissions from "~/components/open/forms/components/FormSubmissions.vue"

const props = defineProps({
  form: { type: Object, required: true },
})

definePageMeta({
  middleware: "auth",
})
const { t } = useI18n()

useOpnSeoMeta({
  title: props.form
    ? t('form_pages.submissions.page_title_with_form', { title: props.form.title })
    : t('form_pages.submissions.page_title'),
})


</script>
