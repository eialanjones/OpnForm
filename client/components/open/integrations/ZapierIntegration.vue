<template>
  <IntegrationWrapper
    v-model="props.integrationData"
    :integration="props.integration"
    :form="form"
  >
    <div class="my-5">
      {{ $t('integrations.zapier.coming_soon') }}
    </div>
  </IntegrationWrapper>
</template>

<script setup>
import IntegrationWrapper from "./components/IntegrationWrapper.vue"

const props = defineProps({
  integration: { type: Object, required: true },
  form: { type: Object, required: true },
  integrationData: { type: Object, required: true },
  formIntegrationId: { type: Number, required: false, default: null },
})
</script>
