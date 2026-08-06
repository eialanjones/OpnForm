<template>
  <div
    v-if="logic"
    :key="resetKey"
  >
    <div class="flex gap-1 border-b pb-2">
      <UButton
        color="neutral"
        variant="ghost"
        size="xs"
        icon="i-heroicons-arrow-down-on-square"
        class="text-neutral-500"
        @click="showCopyFormModal = true"
      >
        {{ $t('form_logic.block_logic.copy_from') }}
      </UButton>
      <UButton
        color="neutral"
        variant="ghost"
        size="xs"
        icon="i-heroicons-arrow-up-on-square"
        class="text-neutral-500"
        @click="showCopyToModal = true"
      >
        {{ $t('form_logic.block_logic.copy_to') }}
      </UButton>
      <UButton
        color="neutral"
        variant="ghost"
        size="xs"
        icon="i-mdi-clear-outline"
        class="text-neutral-500"
        @click="clearAll"
      >
        {{ $t('common.actions.clear') }}
      </UButton>
      <UButton
        color="neutral"
        variant="ghost"
        class="text-neutral-500"
        size="xs"
        icon="i-heroicons-question-mark-circle"
        @click="openHelpArticle"
      />
    </div>

    <!-- Conditions Card -->
    <div class="mt-4">
      <p class="text-xs font-medium text-gray-600 mb-2">{{ $t('form_logic.block_logic.conditions_heading') }}</p>
      <div class="p-3 border border-gray-200 rounded-lg bg-gray-50/50 hover:bg-gray-50 transition-colors">
      <UPopover
        :content="{ 
          align: 'start', 
          side: 'left', 
          sideOffset: 8 
        }"
        :ui="{ 
          content: 'w-[650px] overflow-hidden' 
        }"
        arrow
      >
        <UButton
          :color="hasConditions ? 'primary' : 'neutral'"
          :variant="hasConditions ? 'subtle' : 'outline'"
          :icon="hasConditions ? 'i-heroicons-cog-8-tooth-16-solid' : 'i-heroicons-plus'"
          size="sm"
          class="w-full justify-start font-medium hover:bg-white transition-colors"
        >
          {{ hasConditions ? $t('form_logic.block_logic.rules_count', conditionsCount) : $t('form_logic.block_logic.add_rule') }}
        </UButton>

        <template #content>
            <ScrollableContainer
              ref="scrollableContainer"
              direction="both"
              max-width-class="max-w-[650px]"
              max-height-class="max-h-96"
              :fade-class="'from-white via-white/80 to-transparent'"
              left-fade-width="w-4"
              right-fade-width="w-4"
              top-fade-height="h-4"
              bottom-fade-height="h-4"
              :scroll-tolerance="5"
            >
              <condition-editor
                class="w-full p-4"
                ref="filter-editor"
                v-model="logic.conditions"
                :form="form"
              />
            </ScrollableContainer>
        </template>
      </UPopover>
      </div>
    </div>

    <!-- Divider Line -->
    <div class="flex items-center my-5">
      <div class="flex-1 border-b"></div>
      <span class="px-4 py-1 text-xs font-medium text-gray-600 bg-white border rounded-full">{{ $t('form_logic.block_logic.then') }}</span>
      <div class="flex-1 border-b"></div>
    </div>

    <div>
      <p class="text-xs font-medium text-gray-600 mb-2">{{ $t('form_logic.block_logic.actions_heading') }}</p>
      <div class="p-3 border border-gray-200 rounded-lg bg-gray-50/50 hover:bg-gray-50 transition-colors">
        <flat-select-input
          :key="resetKey"
          v-model="logic.actions"
          name="actions"
          :multiple="true"
          :placeholder="$t('form_logic.block_logic.actions_placeholder')"
          :options="actionOptions"
          @update:model-value="onActionInput"
          clearable
        />
      </div>
    </div>

    <p class="text-neutral-400 text-xs mt-2">
      {{ $t('form_logic.block_logic.hidden_fields_note') }}
    </p>

    <UModal
      v-model:open="showCopyFormModal"
      :title="$t('form_logic.block_logic.copy_from_modal.title')"
      :description="$t('form_logic.block_logic.copy_from_modal.description', { name: field.name })"
    >
      <template #body>
        <USelectMenu
          v-model="copyFrom"
          :items="copyFromOptions"
          value-key="value"
          :placeholder="$t('form_logic.block_logic.copy_from_modal.placeholder')"
          searchable
        />
      </template>

      <template #footer>
        <UButton
          color="neutral"
          variant="outline"
          :label="$t('common.actions.close')"
          @click="showCopyFormModal = false"
        />
        <UButton
          color="primary"
          @click="copyLogic"
          :label="$t('form_logic.block_logic.confirm_copy')"
        />
      </template>
    </UModal>

    <UModal
      v-model:open="showCopyToModal"
      :title="$t('form_logic.block_logic.copy_to_modal.title')"
      :description="$t('form_logic.block_logic.copy_to_modal.description', { name: field.name })"
    >
      <template #body>
        <USelectMenu
          v-model="copyTo"
          :items="copyToOptions"
          value-key="value"
          :placeholder="$t('form_logic.block_logic.copy_to_modal.placeholder')"
          :multiple="true"
          searchable
        />
      </template>

      <template #footer>
        <UButton
          color="neutral"
          variant="outline"
          :label="$t('common.actions.close')"
          @click="showCopyToModal = false"
        />
        <UButton
          color="primary"
          @click="copyLogicToFields"
          :label="$t('form_logic.block_logic.confirm_copy')"
        />
      </template>
    </UModal>
  </div>
</template>

<script>
import ConditionEditor from "./ConditionEditor.client.vue"
import ScrollableContainer from "~/components/dashboard/ScrollableContainer.vue"
import clonedeep from "clone-deep"
import { default as _has } from "lodash/has"

const LAYOUT_BLOCK_TYPES = [
  "nf-text",
  "nf-code",
  "nf-page-break",
  "nf-divider",
  "nf-image",
  "nf-video",
]

const SCORING_ACTIONS = ["award-score", "zero-score"]

export default {
  name: "FormBlockLogicEditor",
  components: { ConditionEditor, ScrollableContainer },
  props: {
    field: {
      type: Object,
      required: false,
    },
    form: {
      type: Object,
      required: false,
    },
  },

  setup() {
    const crisp = useCrisp()
    return {
      crisp
    }
  },

  data() {
    return {
      resetKey: 0,
      logic: this.field.logic || {
        conditions: null,
        actions: [],
      },
      showCopyFormModal: false,
      copyFrom: null,
      showCopyToModal: false,
      copyTo: [],
    }
  },

  computed: {
    conditionsCount() {
      if (this.logic.conditions === null || this.logic.conditions === undefined) return 0
      // Count the number of rules/conditions recursively
      return this.countConditions(this.logic.conditions)
    },
    hasConditions() {
      return this.conditionsCount > 0
    },
    copyFromOptions() {
      return this.form.properties
        .filter((field) => {
          return (
            field.id !== this.field.id &&
            _has(field, "logic") &&
            field.logic !== null &&
            Object.keys(field.logic || {}).length > 0
          )
        })
        .map((field) => {
          return { label: field.name, value: field.id }
        })
    },
    copyToOptions() {
      return this.form.properties
        .filter((field) => {
          return field.id !== this.field.id
        })
        .map((field) => {
          return { label: field.name, value: field.id }
        })
    },
    isLayoutBlock() {
      return LAYOUT_BLOCK_TYPES.includes(this.field.type)
    },
    scoringActionOptions() {
      // Layout blocks never produce an answer, so they can never be scored.
      if (this.isLayoutBlock) {
        return []
      }
      if ((this.form?.settings?.scoring_enabled ?? true) === false) {
        return []
      }
      return [
        {
          name: this.$t("form_logic.block_logic.actions.award_score"),
          value: "award-score",
        },
        {
          name: this.$t("form_logic.block_logic.actions.zero_score"),
          value: "zero-score",
        },
      ]
    },
    actionOptions() {
      return [...this.stateActionOptions, ...this.scoringActionOptions]
    },
    stateActionOptions() {
      if (this.isLayoutBlock) {
        if (this.field.hidden) {
          return [{ name: this.$t("form_logic.block_logic.actions.show_block"), value: "show-block" }]
        } else {
          return [{ name: this.$t("form_logic.block_logic.actions.hide_block"), value: "hide-block" }]
        }
      }

      if (this.field.hidden) {
        return [
          { name: this.$t("form_logic.block_logic.actions.show_block"), value: "show-block" },
          { name: this.$t("form_logic.block_logic.actions.require_answer"), value: "require-answer" },
        ]
      } else if (this.field.disabled) {
        return [
          { name: this.$t("form_logic.block_logic.actions.enable_block"), value: "enable-block" },
          this.field.required
            ? { name: this.$t("form_logic.block_logic.actions.make_it_optional"), value: "make-it-optional" }
            : {
                name: this.$t("form_logic.block_logic.actions.require_answer"),
                value: "require-answer",
              },
        ]
      } else {
        return [
          { name: this.$t("form_logic.block_logic.actions.hide_block"), value: "hide-block" },
          { name: this.$t("form_logic.block_logic.actions.disable_block"), value: "disable-block" },
          this.field.required
            ? { name: this.$t("form_logic.block_logic.actions.make_it_optional"), value: "make-it-optional" }
            : {
                name: this.$t("form_logic.block_logic.actions.require_answer"),
                value: "require-answer",
              },
        ]
      }
    },
  },

  watch: {
    logic: {
      handler() {
        this.field.logic = this.logic
      },
      deep: true,
    },
    "field.id": {
      handler() {
        // On field change, reset logic
        this.logic = this.field.logic || {
          conditions: null,
          actions: [],
        }
      },
    },
    "field.required": "cleanConditions",
    "field.disabled": "cleanConditions",
    "field.hidden": "cleanConditions",
  },

  mounted() {
    if (!_has(this.field, "logic")) {
      this.field.logic = this.logic
    }
  },

  methods: {
    countConditions(conditions) {
      if (!conditions) return 0
      
      // If it's a group with children
      if (conditions.children && Array.isArray(conditions.children)) {
        return conditions.children.reduce((count, child) => {
          // If child has an identifier, it's a rule
          if (child.identifier) {
            return count + 1
          }
          // If child has children, it's a nested group - count recursively
          if (child.children) {
            return count + this.countConditions(child)
          }
          return count
        }, 0)
      }
      
      // If it's a single rule with identifier
      if (conditions.identifier) {
        return 1
      }
      
      return 0
    },
    clearAll() {
      this.logic.conditions = null
      this.logic.actions = []
      this.refreshActions()
    },
    onActionInput() {
      const scoringChanged = this.enforceExclusivity(SCORING_ACTIONS)
      const stateChanged = this.enforceExclusivity([
        "hide-block",
        "require-answer",
      ])

      if (scoringChanged || stateChanged) {
        this.refreshActions()
      }
    },
    // Drops every action of a mutually exclusive pair except the one the user
    // picked last. The select appends on selection, so the last index wins.
    enforceExclusivity(pair) {
      const actions = this.logic.actions
      if (!pair.every((action) => actions.includes(action))) {
        return false
      }

      const keep = pair.reduce((latest, action) =>
        actions.lastIndexOf(action) > actions.lastIndexOf(latest)
          ? action
          : latest,
      )
      this.logic.actions = actions.filter(
        (action) => action === keep || !pair.includes(action),
      )

      return true
    },
    cleanConditions() {
      const availableActions = this.actionOptions.map(function (op) {
        return op.value
      })
      this.logic.actions = availableActions.filter((value) =>
        this.logic.actions.includes(value),
      )
      this.refreshActions()
    },
    refreshActions() {
      this.resetKey++
    },
    openHelpArticle() {
      this.crisp.openHelpdeskArticle('how-do-i-add-logic-to-my-form-1lmguq5')
    },
    copyLogic() {
      if (this.copyFrom) {
        const property = this.form.properties.find((property) => {
          return property.id === this.copyFrom
        })
        if (property && property.logic) {
          this.logic = clonedeep(property.logic)
          this.cleanConditions()
        }
      }
      this.showCopyFormModal = false
    },
    copyLogicToFields() {
      if (this.copyTo.length) {
        this.copyTo.forEach((fieldId) => {
          const targetField = this.form.properties.find(
            (property) => property.id === fieldId
          )
          if (targetField) {
            targetField.logic = clonedeep(this.logic)
          }
        })
      }
      this.showCopyToModal = false
      this.copyTo = []
    },
  },
}
</script>
