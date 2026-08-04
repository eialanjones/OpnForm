<template>
  <div>
    <MentionInput
      v-model="compVal.message"
      :mentions="form.properties"
      name="message"
      class="mt-4"
      :label="$t('integrations.notifications.message_label')"
      :help="$t('integrations.notifications.message_help')"
    />
    <toggle-switch-input
      v-model="compVal.include_submission_data"
      name="include_submission_data"
      class="mt-4"
      :label="$t('integrations.notifications.include_submission_data_label')"
      :help="$t('integrations.notifications.include_submission_data_help')"
    />
    <toggle-switch-input
      v-if="compVal.include_submission_data"
      v-model="compVal.include_hidden_fields_submission_data"
      name="include_hidden_fields_submission_data"
      class="mt-4"
      :label="$t('integrations.notifications.include_hidden_fields_label')"
      :help="$t('integrations.notifications.include_hidden_fields_help')"
    />
    <toggle-switch-input
      v-model="compVal.link_open_form"
      name="link_open_form"
      class="mt-4"
      :label="$t('integrations.notifications.open_form_link_label')"
      :help="$t('integrations.notifications.open_form_link_help')"
    />
    <toggle-switch-input
      v-model="compVal.link_edit_form"
      name="link_edit_form"
      class="mt-4"
      :label="$t('integrations.notifications.edit_form_link_label')"
      :help="$t('integrations.notifications.edit_form_link_help')"
    />
    <toggle-switch-input
      v-model="compVal.views_submissions_count"
      name="views_submissions_count"
      class="mt-4"
      :label="$t('integrations.notifications.analytics_label')"
      :help="$t('integrations.notifications.analytics_help')"
    />
    <toggle-switch-input
      v-if="form.editable_submissions"
      v-model="compVal.link_edit_submission"
      name="link_edit_submission"
      class="mt-4"
      :label="$t('integrations.notifications.edit_submission_link_label')"
    />
  </div>
</template>

<script>
export default {
  name: "NotificationsMessageActions",
  components: {},
  props: {
    modelValue: { type: Object, required: false },
    form: { type: Object, required: true },
  },
  emits:  ['modelValue',  'input'],
  data() {
    return {
      content: this.modelValue ?? {},
    }
  },

  computed: {
    compVal: {
      set(val) {
        this.content = val
        this.$emit("input", this.compVal)
      },
      get() {
        return this.content
      },
    },
  },

  watch: {
    modelValue(val) {
      this.content = val
    },
  },

  created() {
    if (this.compVal === undefined || this.compVal === null) {
      this.compVal = {}
    }
    [
      "message",
      "include_submission_data",
      'include_hidden_fields_submission_data',
      "link_open_form",
      "link_edit_form",
      "views_submissions_count",
      "link_edit_submission",
    ].forEach((keyname) => {
      if (this.compVal[keyname] === undefined) {
        if (keyname === 'message') {
          this.compVal[keyname] = this.$t('integrations.notifications.default_message')
        } else if (['include_hidden_fields_submission_data'].includes(keyname)) {
          this.compVal[keyname] = false
        } else {
          this.compVal[keyname] = true
        }
      }
    })
  },

  methods: {},
}
</script>
