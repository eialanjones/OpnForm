<template>
  <div
    v-if="user.active_license"
    class="border p-5 shadow-md rounded-md"
  >
    <div class="w-auto flex flex-col items-center">
      <img
        src="/img/appsumo/as-taco-white-bg.png"
        class="max-w-[60px]"
        alt="AppSumo"
      >

      <img
        src="/img/appsumo/as-Select-dark.png"
        class="max-w-[150px]"
        alt="AppSumo"
      >
    </div>
    <i18n-t
      keypath="admin.appsumo.billing.license_active"
      scope="global"
      tag="p"
      class="mt-6"
    >
      <template #tier>
        <span class="font-semibold">{{ licenseTier }}</span>
      </template>
    </i18n-t>
    <ul class="list-disc pl-5 mt-4">
      <li>
        {{ $t('admin.appsumo.billing.forms_count') }}
        <span class="font-semibold">{{ tierFeatures.form_quantity }}</span>
      </li>
      <li>
        {{ $t('admin.appsumo.billing.custom_domains') }}
        <span class="font-semibold">{{ tierFeatures.domain_names }}</span>
      </li>
      <li>
        {{ $t('admin.appsumo.billing.file_upload_size') }}
        <span class="font-semibold">{{ tierFeatures.file_upload_size }}</span>
      </li>
      <li>
        {{ $t('admin.appsumo.billing.users_limit') }}
        <span class="font-semibold">{{ tierFeatures.users }}</span>
      </li>
    </ul>
    <div class="w-max">
      <UButton
        variant="outline"
        color="neutral"
        class="mt-4 block"
        href="https://appsumo.com/account/products/"
        target="_blank"
        :label="$t('admin.appsumo.billing.manage_button')"
      />
    </div>
  </div>
</template>

<script>
import { computed } from "vue"

export default {
  name: "AppSumoBilling",

  setup() {
          return {
        user: computed(() => useAuth().user().data.value),
      }
  },

  data() {
    return {}
  },

  computed: {
    licenseTier() {
      return this.user?.active_license?.meta?.tier
    },
    tierFeatures() {
      if (!this.licenseTier) return {}
      return {
        1: {
          form_quantity: this.$t("admin.appsumo.billing.unlimited"),
          file_upload_size: "25mb",
          domain_names: "5",
          users: 1
        },
        2: {
          form_quantity: this.$t("admin.appsumo.billing.unlimited"),
          file_upload_size: "50mb",
          domain_names: "25",
          users: 5
        },
        3: {
          form_quantity: this.$t("admin.appsumo.billing.unlimited"),
          file_upload_size: "75mb",
          domain_names: this.$t("admin.appsumo.billing.unlimited"),
          users: 20
        },
      }[this.licenseTier]
    },
  },

  watch: {},

  mounted() {},

  created() {},

  unmounted() {},

  methods: {},
}
</script>
