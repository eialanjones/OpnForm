<template>
  <div
    v-if="field"
    class="pb-20"
  >
    <!-- General -->
    <div class="px-4">
      <text-input
        name="name"
        class="mt-2"
        :form="field"
        :required="true"
        wrapper-class="mb-2"
        :label="$t('form_fields.options.field_name')"
      />
      <HiddenRequiredDisabled
        class="mt-4"
        :field="field"
      />
    </div>

    <!-- Focused Mode: Media settings (high priority under general) -->
    <div v-if="isFocused" class="mt-2">
      <BlockMediaOptions :model="field" :form="form" />
    </div>

    <!-- Checkbox -->
    <div
      v-if="field.type === 'checkbox'"
      class="px-4"
    >
      <EditorSectionHeader
        icon="i-heroicons-check-circle"
        :title="$t('form_fields.options.checkbox.title')"
      />
      <toggle-switch-input
        v-if="!isFocused"
        :form="field"
        name="use_toggle_switch"
        :label="$t('form_fields.options.checkbox.use_toggle_switch')"
        :help="$t('form_fields.options.checkbox.use_toggle_switch_help')"
      />
      <template v-else>
        <flat-select-input
          v-model="field.focused_checkbox_style"
          name="focused_checkbox_style"
          class="mt-3"
          :form="field"
          :options="focusedCheckboxStyleOptions"
          :label="$t('form_fields.options.checkbox.style_label')"
          :help="$t('form_fields.options.checkbox.style_help')"
          @update:model-value="onFieldFocusedCheckboxStyleChange"
        />
      </template>
    </div>

    <!-- File Uploads -->
    <div
      v-if="field.type === 'files'"
      class="px-4"
    >
      <EditorSectionHeader
        icon="i-heroicons-paper-clip"
        :title="$t('form_fields.options.files.title')"
      />
      <toggle-switch-input
        :form="field"
        name="multiple"
        :label="$t('form_fields.options.files.allow_multiple')"
      />
      <toggle-switch-input
        :form="field"
        name="camera_upload"
        :label="$t('form_fields.options.files.allow_camera')"
      />
      <text-input
        name="allowed_file_types"
        class="mt-3"
        :form="field"
        :label="$t('form_fields.options.files.allowed_types_label')"
        placeholder="jpg,jpeg,png,gif"
        :help="$t('form_fields.options.files.allowed_types_help')"
      />

      <text-input
        name="max_file_size"
        class="mt-3"
        :form="field"
        native-type="number"
        :min="1"
        :max="mbLimit"
        :label="$t('form_fields.options.files.max_size_label')"
        :placeholder="`1MB - ${mbLimit}MB`"
        :help="$t('form_fields.options.files.max_size_help')"
      />
    </div>

    <!-- Barcode Reader -->
    <div
      v-if="field.type === 'barcode'"
      class="px-4"
    >
      <EditorSectionHeader
        icon="i-material-symbols-barcode-scanner-rounded"
        :title="$t('form_fields.options.barcode.title')"
      />
      <select-input
        name="decoders"
        class="mt-4"
        :form="field"
        :options="barcodeDecodersOptions"
        :label="$t('form_fields.options.barcode.decoders_label')"
        :searchable="true"
        :multiple="true"
        :help="$t('form_fields.options.barcode.decoders_help')"
      />
    </div>

    <div
      v-if="field.type === 'rating'"
      class="px-4"
    >
      <EditorSectionHeader
        icon="i-heroicons-star"
        :title="$t('form_fields.options.rating.title')"
      />
      <text-input
        name="rating_max_value"
        native-type="number"
        :min="1"
        class="mt-3"
        :form="field"
        required
        :label="$t('form_fields.options.rating.max_value')"
      />
    </div>

    <div
      v-if="field.type === 'scale'"
      class="px-4"
    >
      <EditorSectionHeader
        icon="i-heroicons-scale-20-solid"
        :title="$t('form_fields.options.scale.title')"
      />
      <text-input
        name="scale_min_value"
        native-type="number"
        class="mt-4"
        :form="field"
        required
        :label="$t('form_fields.options.scale.min_value')"
      />
      <text-input
        name="scale_max_value"
        native-type="number"
        :min="1"
        class="mt-4"
        :form="field"
        required
        :label="$t('form_fields.options.scale.max_value')"
      />
      <text-input
        name="scale_step_value"
        native-type="number"
        :min="1"
        class="mt-4"
        :form="field"
        required
        :label="$t('form_fields.options.scale.step_value')"
      />
    </div>

    <div
      v-if="field.type === 'slider'"
      class="px-4"
    >
      <EditorSectionHeader
        icon="i-heroicons-adjustments-horizontal"
        :title="$t('form_fields.options.slider.title')"
      />
      <text-input
        name="slider_min_value"
        native-type="number"
        class="mt-4"
        :form="field"
        required
        :label="$t('form_fields.options.slider.min_value')"
      />
      <text-input
        name="slider_max_value"
        native-type="number"
        :min="1"
        class="mt-4"
        :form="field"
        required
        :label="$t('form_fields.options.slider.max_value')"
      />
      <text-input
        name="slider_step_value"
        native-type="number"
        :min="1"
        class="mt-4"
        :form="field"
        required
        :label="$t('form_fields.options.slider.step_value')"
      />
    </div>

    <MatrixFieldOptions
      :model-value="field"
      @update:model-value="field = $event"
    />

    <PaymentFieldOptions
      v-if="field.type === 'payment'"
      :field="field"
      :form="form"
    />

    <AiPdfFieldOptions
      v-if="field.type === 'ai_pdf'"
      :field="field"
      :form="form"
    />

    <!--   Text Options   -->
    <div
      v-if="field.type === 'text' && displayBasedOnAdvanced"
      class="px-4"
    >
      <EditorSectionHeader
        icon="i-heroicons-bars-3-bottom-left"
        :title="$t('form_fields.options.text.title')"
      />
      <toggle-switch-input
        :form="field"
        name="multi_lines"
        :label="$t('form_fields.options.text.multi_lines')"
        @update:model-value="onFieldMultiLinesChange"
      />
      <toggle-switch-input
        :form="field"
        name="secret_input"
        :help="$t('form_fields.options.text.secret_input_help')"
        @update:model-value="onFieldSecretInputChange"
      >
        <template #label>
          <span class="text-sm">
            {{ $t('form_fields.options.text.secret_input') }}
          </span>
          <pro-tag
            :upgrade-modal-title="$t('form_fields.options.text.secret_input_upgrade')"
            class="-mt-1"
          />
        </template>
      </toggle-switch-input>
    </div>

    <!--   Date Options   -->
    <div
      v-if="field.type === 'date'"
      class="px-4"
    >
      <EditorSectionHeader
        icon="i-heroicons-calendar-20-solid"
        :title="$t('form_fields.options.date.title')"
      />
      <toggle-switch-input
        :form="field"
        class="mt-3"
        name="date_range"
        :label="$t('form_fields.options.date.include_end_date')"
        @update:model-value="onFieldDateRangeChange"
      />
      <toggle-switch-input
        :form="field"
        name="prefill_today"
        :label="$t('form_fields.options.date.prefill_today')"
        @update:model-value="onFieldPrefillTodayChange"
      />
      <toggle-switch-input
        :form="field"
        name="disable_past_dates"
        :label="$t('form_fields.options.date.disable_past_dates')"
        @update:model-value="onFieldDisablePastDatesChange"
      />
      <toggle-switch-input
        :form="field"
        name="disable_future_dates"
        :label="$t('form_fields.options.date.disable_future_dates')"
        @update:model-value="onFieldDisableFutureDatesChange"
      />
      <toggle-switch-input
        :form="field"
        name="with_time"
        :label="$t('form_fields.options.date.include_time')"
      />
      <select-input
        v-if="field.with_time"
        name="timezone"
        class="mt-4"
        :form="field"
        :options="timezonesOptions"
        :label="$t('form_fields.options.date.timezone_label')"
        :searchable="true"
        :help="$t('form_fields.options.date.timezone_help')"
      />
      <flat-select-input
        v-if="field.with_time"
        name="time_format"
        class="mt-4"
        :form="field"
        :options="timeFormatOptions"
        :label="$t('form_fields.options.date.time_format_label')"
      />
      <flat-select-input
        name="date_format"
        class="mt-4"
        :form="field"
        :options="dateFormatOptions"
        :label="$t('form_fields.options.date.date_format_label')"
      />
    </div>

    <!-- select/multiselect Options   -->
    <div
      v-if="['select', 'multi_select'].includes(field.type)"
      class="px-4"
    >
      <EditorSectionHeader
        icon="i-heroicons-chevron-up-down-20-solid"
        :title="$t('form_fields.options.select.title')"
      />
      <text-area-input
        v-model="optionsText"
        :name="field.id + '_options_text'"
        class="mt-3"
        :label="$t('form_fields.options.select.options_label')"
        :help="$t('form_fields.options.select.options_help')"
        @update:model-value="onFieldOptionsChange"
      />
      <toggle-switch-input
        v-if="isFocused"
        :model-value="field.use_focused_selector === false"
        :label="$t('form_fields.options.select.use_dropdown_instead')"
        :help="$t('form_fields.options.select.use_dropdown_instead_help')"
        @update:model-value="onFieldUseDropdownInFocusedChange"
      />
      <toggle-switch-input
        v-if="!isFocusedSelectorActive"
        :form="field"
        name="allow_creation"
        :label="$t('form_fields.options.select.allow_creation')"
        @update:model-value="onFieldAllowCreationChange"
      />
      <toggle-switch-input
        v-if="!isFocusedSelectorActive"
        :form="field"
        name="without_dropdown"
        :label="$t('form_fields.options.select.without_dropdown')"
        @update:model-value="onFieldWithoutDropdownChange"
      />
      <toggle-switch-input
        :form="field"
        name="shuffle_options"
        :label="$t('form_fields.options.select.shuffle_options')"
      />
      
      <!-- Min/Max Selection Constraints for multi_select only -->
      <template v-if="field.type === 'multi_select'">
        <div class="flex gap-1">
        <text-input
          name="min_selection"
          native-type="number"
          :min="0"
          class="flex-1"
          :form="field"
          :label="$t('form_fields.options.select.min_selection_label')"
          placeholder="1"
          @update:model-value="onFieldMinSelectionChange"
        />
        <text-input
          name="max_selection"
          native-type="number"
          :min="1"
          class="flex-1"
          :form="field"
          :label="$t('form_fields.options.select.max_selection_label')"
          placeholder="2"
          @update:model-value="onFieldMaxSelectionChange"
        />
        <UButton
          icon="i-heroicons-backspace"
          color="neutral"
          variant="outline"
          class="self-end mb-1"
          :title="$t('form_fields.options.select.clear_min_max')"
          @click="clearMinMaxSelection"
        />
      </div>
      <InputHelp :help="$t('form_fields.options.select.min_max_help')" />
      </template>
    </div>

    <!-- Customization - Placeholder, Prefill, Relabel, Field Help    -->
    <div
      v-if="displayBasedOnAdvanced"
      class="px-4"
    >
      <EditorSectionHeader
        icon="i-heroicons-adjustments-horizontal"
        :title="$t('form_fields.options.customization.title')"
      />

      <toggle-switch-input
        :form="field"
        name="hide_field_name"
        :label="$t('form_fields.options.customization.hide_field_name')"
      />

      <toggle-switch-input
        v-if="field.type === 'phone_number'"
        :form="field"
        name="use_simple_text_input"
        :label="$t('form_fields.options.customization.use_simple_text_input')"
      />

      <template v-if="field.type === 'phone_number' && !field.use_simple_text_input">
        <select-input
          class="mt-3"
          v-model="field.unavailable_countries"
          popover-width="full"
          input-class="ltr-only:rounded-r-none rtl:rounded-l-none!"
          :options="allCountries"
          :multiple="true"
          :searchable="true"
          :search-keys="['name']"
          :option-key="'code'"
          :emit-key="'code'"
          :label="$t('form_fields.options.customization.disabled_countries_label')"
          :placeholder="$t('form_fields.options.customization.disabled_countries_placeholder')"
          :help="$t('form_fields.options.customization.disabled_countries_help')"
        >
          <template #selected="{ option }">
            <div class="flex items-center space-x-2 justify-center overflow-hidden">
              {{ $t('form_fields.options.customization.countries_selected', { count: option.length }) }}
            </div>
          </template>
          <template #option="{ option, selected }">
            <div class="flex items-center gap-2 max-w-full">
              <country-flag
                size="normal"
                class="-mt-[9px]! rounded"
                :country="option.code"
              />
              <span class="truncate">{{ option.name }}</span>
              <span class="text-gray-500">{{ option.dial_code }}</span>
            </div>
            <span
              v-if="selected"
              class="absolute inset-y-0 right-0 flex items-center pr-2 dark:text-white"
            >
              <svg
                class="h-5 w-5"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                  clip-rule="evenodd"
                />
              </svg>
            </span>
          </template>
        </select-input>
        <small class="flex -mt-2">
          <a
            href="#"
            class="grow"
            @click.prevent="selectAllCountries"
          >{{ $t('form_fields.options.customization.select_all_countries') }}</a>
          <a
            href="#"
            @click.prevent="field.unavailable_countries = null"
          >{{ $t('form_fields.options.customization.unselect_all_countries') }}</a>
        </small>
      </template>

      <!-- Pre-fill depends on type -->
      <toggle-switch-input
        v-if="field.type == 'checkbox'"
        :form="field"
        name="prefill"
        :label="$t('form_fields.options.customization.prefill_label')"
        @update:model-value="field.prefill = $event"
      />
      <select-input
        v-else-if="['select', 'multi_select'].includes(field.type)"
        name="prefill"
        class="mt-3"
        :form="field"
        :options="prefillSelectsOptions"
        :label="$t('form_fields.options.customization.prefill_label')"
        :searchable="shouldEnableSelectSearch"
        :multiple="field.type === 'multi_select'"
      />
      <template v-else-if="field.type === 'matrix'">
        <MatrixInput
          :form="field"
          :rows="field.rows"
          :columns="field.columns"
          name="prefill"
          :label="$t('form_fields.options.customization.prefill_label')"
        />
      </template>
      <date-input
        v-else-if="field.type === 'date' && field.prefill_today !== true"
        name="prefill"
        class="mt-3"
        :form="field"
        :time-format="field.time_format"
        :with-time="field.with_time === true"
        :date-range="field.date_range === true"
        :label="$t('form_fields.options.customization.prefill_label')"
      />
      <text-input
        v-else-if="field.type==='date' && field.prefill_today===true"
        name="prefill"
        class="mt-4"
        disabled
        :form="field"
        :label="$t('form_fields.options.customization.prefill_label')"
        :placeholder="$t('form_fields.options.customization.prefill_today_placeholder')"
      />
      <phone-input
        v-else-if="field.type === 'phone_number' && !field.use_simple_text_input"
        name="prefill"
        class="mt-3"
        :form="field"
        :can-only-country="true"
        :unavailable-countries="field.unavailable_countries ?? []"
        :label="$t('form_fields.options.customization.prefill_label')"
      />
      <text-area-input
        v-else-if="field.type === 'text' && field.multi_lines"
        name="prefill"
        class="mt-3"
        :form="field"
        :label="$t('form_fields.options.customization.prefill_label')"
      />
      <file-input
        v-else-if="field.type === 'files'"
        name="prefill"
        class="mt-4"
        :form="field"
        :label="$t('form_fields.options.customization.prefill_file_label')"
        :multiple="field.multiple === true"
        :move-to-form-assets="true"
      />
      <rich-text-area-input
        v-else-if="field.type === 'rich_text'"
        :allow-fullscreen="true"
        name="prefill"
        class="mt-3"
        :form="field"
        :label="$t('form_fields.options.customization.prefill_label')"
      />
      <text-input
        v-else-if="!['files', 'signature', 'rich_text', 'payment', 'ai_pdf'].includes(field.type)"
        name="prefill"
        class="mt-3"
        :form="field"
        :label="$t('form_fields.options.customization.prefill_label')"
      />
      <div
        v-if="['select', 'multi_select'].includes(field.type)"
        class="-mt-3 mb-3 text-neutral-400 dark:text-neutral-500"
      >
        <small>
          {{ $t('form_fields.options.customization.prefill_problem') }} <a
            href="#"
            @click.prevent="field.prefill = null"
          >{{ $t('form_fields.options.customization.prefill_clear_link') }}</a>
        </small>
      </div>

      <!-- Placeholder -->
      <text-area-input
        v-if="hasPlaceholder && ((field.type === 'text' && field.multi_lines) || field.type === 'rich_text')"
        name="placeholder"
        class="mt-3"
        :form="field"
        :label="$t('form_fields.options.customization.placeholder_label')"
      />
      <text-input
        v-else-if="hasPlaceholder"
        name="placeholder"
        class="mt-3"
        :form="field"
        :label="$t('form_fields.options.customization.placeholder_label')"
      />

      <OptionSelectorInput
        v-model="field.width"
        name="width"
        class="mt-4"
        :form="field"
        :label="$t('form_fields.width.label')"
        seamless
        v-if="!isFocused"
        :options="[
          { name: 'full', label: $t('form_fields.width.full') },
          { name: '1/2', label: '1/2' },
          { name: '1/3', label: '1/3' },
          { name: '2/3', label: '2/3' },
          { name: '1/4', label: '1/4' },
          { name: '3/4', label: '3/4' },
        ]"
        :multiple="false"
        :columns="6"
      />

      <!--   Help  -->
      <RichTextAreaInput
        name="help"
        class="mt-3"
        :allow-fullscreen="true"
        :form="field"
        :label="$t('form_fields.options.customization.help_label')"
        :editor-options="{
          formats: [
            'bold',
            'color',
            'font',
            'italic',
            'link',
            'underline',
            'list',
            'strike'
          ],
          modules: {
            toolbar: [
              ['bold', 'italic', 'underline', 'strike'],
              ['link'],
              [{ list: 'ordered' }, { list: 'bullet' }]
            ]
          }
        }"
        :help="$t('form_fields.options.customization.help_hint')"
        :help-position="field.help_position"
      />
      <OptionSelectorInput
        v-model="field.help_position"
        name="help_position"
        class="mt-4 w-2/3"
        :form="field"
        :label="$t('form_fields.options.customization.help_position_label')"
        seamless
        :options="[
          { name: 'below_input', label: $t('form_fields.options.customization.help_position_below')},
          { name: 'above_input', label: $t('form_fields.options.customization.help_position_above')},
        ]"
        :multiple="false"
        :columns="2"
        @update:model-value="onFieldHelpPositionChange"
      />

      <template v-if="['text', 'rich_text', 'number', 'url', 'email'].includes(field.type)">
        <text-input
          name="max_char_limit"
          native-type="number"
          :min="1"
          :form="field"
          :label="$t('form_fields.options.customization.max_char_limit')"
          :required="false"
          class="mt-3"
          @update:model-value="onFieldMaxCharLimitChange"
        />
        <toggle-switch-input
          v-if="field.max_char_limit"
          name="show_char_limit"
          :form="field"
          class="mt-3"
          :label="$t('form_fields.options.customization.show_char_limit')"
        />
      </template>
    </div>

    <!--  Advanced Options   -->
    <div
      v-if="field.type === 'text'"
      class="px-4"
    >
      <EditorSectionHeader
        icon="i-heroicons-bars-3-bottom-left"
        :title="$t('form_fields.options.advanced.title')"
      />
      
      <toggle-switch-input
        :form="field"
        name="generates_uuid"
        :label="$t('form_fields.options.advanced.generates_uuid')"
        :help="$t('form_fields.options.advanced.generates_uuid_help')"
        @update:model-value="onFieldGenUIdChange"
      />
      <toggle-switch-input
        :form="field"
        name="generates_auto_increment_id"
        :label="$t('form_fields.options.advanced.generates_auto_increment_id')"
        :help="$t('form_fields.options.advanced.generates_auto_increment_id_help')"
        @update:model-value="onFieldGenAutoIdChange"
      />
    </div>

  <!--  (moved above for focused mode)  -->
  </div>
</template>

<script>
import timezones from '~/data/timezones.json'
import countryCodes from '~/data/country_codes.json'
import CountryFlag from 'vue-country-flag-next'
import MatrixFieldOptions from './MatrixFieldOptions.vue'
import PaymentFieldOptions from './PaymentFieldOptions.vue'
import AiPdfFieldOptions from './AiPdfFieldOptions.vue'
import HiddenRequiredDisabled from './HiddenRequiredDisabled.vue'
import EditorSectionHeader from '~/components/open/forms/components/form-components/EditorSectionHeader.vue'
import ProTag from '~/components/app/ProTag.vue'
import { format } from 'date-fns'
import { default as _has } from 'lodash/has'
import blocksTypes from '~/data/blocks_types.json'
import BlockMediaOptions from '~/components/open/forms/components/media/BlockMediaOptions.vue'

export default {
  name: 'FieldOptions',
  components: { CountryFlag, MatrixFieldOptions, HiddenRequiredDisabled, EditorSectionHeader, PaymentFieldOptions, AiPdfFieldOptions, ProTag, BlockMediaOptions },
  props: {
    field: {
      type: Object,
      required: false
    },
    form: {
      type: Object,
      required: false
    }
  },
  setup() {
    const { current: currentWorkspace } = useCurrentWorkspace()
    return { currentWorkspace }
  },
  data() {
    return {
      typesWithoutPlaceholder: ['date', 'checkbox', 'files', 'payment', 'matrix', 'signature', 'barcode', 'scale', 'slider', 'rating', 'ai_pdf'],
      allCountries: countryCodes
    }
  },

  computed: {
    barcodeDecodersOptions() {
      return [
        { name: this.$t('form_fields.options.barcode.decoder_qr'), value: 'qr_reader' },
        { name: this.$t('form_fields.options.barcode.decoder_ean_13'), value: 'ean_reader' },
        { name: this.$t('form_fields.options.barcode.decoder_ean_8'), value: 'ean_8_reader' },
        { name: this.$t('form_fields.options.barcode.decoder_upc_a'), value: 'upc_reader' },
        { name: this.$t('form_fields.options.barcode.decoder_upc_e'), value: 'upc_e_reader' },
        { name: this.$t('form_fields.options.barcode.decoder_code_128'), value: 'code_128_reader' },
        { name: this.$t('form_fields.options.barcode.decoder_code_39'), value: 'code_39_reader' }
      ]
    },
    isFocused() {
      return this.form?.presentation_style === 'focused'
    },
    isFocusedSelectorActive() {
      // Focused selector is active when in focused mode AND not explicitly disabled
      return this.isFocused && this.field.use_focused_selector !== false
    },
    hasPlaceholder() {
      return !this.typesWithoutPlaceholder.includes(this.field.type)
    },
    focusedCheckboxStyleOptions() {
      return [
        { name: this.$t('form_fields.options.checkbox.style_focused_toggle'), value: 'focused_toggle' },
        { name: this.$t('form_fields.options.checkbox.style_toggle_switch'), value: 'toggle_switch' },
        { name: this.$t('form_fields.options.checkbox.style_classic'), value: 'checkbox' }
      ]
    },
    mbLimit() {
      return  (this.form?.workspace && this.form?.workspace.max_file_size) ? this.form?.workspace?.max_file_size : 10
    },
    optionsText() {
      return this.field[this.field.type].options.map(option => option.name).join('\n')
    },
    prefillSelectsOptions() {
      if (!['select', 'multi_select'].includes(this.field.type)) return {}

      return this.field[this.field.type].options.map(option => {
        return {
          name: option.name,
          value: option.id
        }
      })
    },
    selectionOptionsCount() {
      if (!['select', 'multi_select'].includes(this.field.type)) return 0
      return Array.isArray(this.field[this.field.type]?.options) ? this.field[this.field.type].options.length : 0
    },
    shouldEnableSelectSearch() {
      return ['select', 'multi_select'].includes(this.field.type) && this.selectionOptionsCount > 5
    },
    timezonesOptions() {
      if (this.field.type !== 'date') return []
      return timezones.map((timezone) => {
        return {
          name: timezone.text,
          value: timezone.utc[0]
        }
      })
    },
    dateFormatOptions () {
      const date = new Date()
      return ['dd/MM/yyyy', 'MM-dd-yyyy'].map(dateFormat => {
        return {
          name: format(date, dateFormat),
          value: dateFormat
        }
      })
    },
    timeFormatOptions() {
      return [{ name: '13:00', value: '24', },
      { name: '01:00 PM', value: '12', },]
    },
    displayBasedOnAdvanced() {
      if (this.field.generates_uuid || this.field.generates_auto_increment_id) {
        return false
      }
      return true
    },
  },

  watch: {
    'field.width': {
      handler(val) {
        if (val === undefined || val === null) {
          this.field.width = 'full'
        }
      },
      immediate: true
    },
    'field.align': {
      handler(val) {
        if (val === undefined || val === null) {
          this.field.align = 'left'
        }
      },
      immediate: true
    },
    'field.type': {
      handler() {
        this.setDefaultFieldValues()
      },
      immediate: true
    },
    isFocused: {
      handler(val) {
        // When switching to focused mode for checkbox, set default style if not set
        if (val && this.field.type === 'checkbox' && !this.field.focused_checkbox_style) {
          this.field.focused_checkbox_style = 'focused_toggle'
          this.field.use_focused_toggle = true
        }
      },
      immediate: true
    },
    isFocusedSelectorActive: {
      handler(val) {
        // When focused selector becomes active, ensure conflicting options are disabled
        if (val && ['select', 'multi_select'].includes(this.field.type)) {
          this.field.without_dropdown = false
          this.field.allow_creation = false
        }
      },
      immediate: true
    }
  },

  created() {
    if (this.field?.width === undefined || this.field?.width === null) {
      this.field.width = 'full'
    }
  },

  mounted() {
    this.setDefaultFieldValues()
  },

  methods: {
    onFieldDateRangeChange(val) {
      this.field.date_range = val
      if (this.field.date_range) {
        this.field.prefill_today = false
      }
    },
    onFieldGenUIdChange(val) {
      this.field.generates_uuid = val
      if (this.field.generates_uuid) {
        this.field.generates_auto_increment_id = false
        this.field.hidden = true
      }
    },
    onFieldGenAutoIdChange(val) {
      this.field.generates_auto_increment_id = val
      if (this.field.generates_auto_increment_id) {
        this.field.generates_uuid = false
        this.field.hidden = true
      }
    },
    onFieldOptionsChange(val) {
      const vals = (val) ? val.trim().split('\n') : []
      const tmpOpts = vals.map(name => {
        return {
          name: name,
          id: name
        }
      })
      this.field[this.field.type] = { options: tmpOpts }
    },
    onFieldPrefillTodayChange(val) {
      this.field.prefill_today = val
      if (this.field.prefill_today) {
        this.field.prefill = null
        this.field.date_range = false
        this.field.disable_future_dates = false
        this.field.disable_past_dates = false
      } else {
        this.field.prefill = this.field.prefill ?? null
      }
    },
    onFieldAllowCreationChange(val) {
      this.field.allow_creation = val
      if (this.field.allow_creation) {
        this.field.without_dropdown = false
      }
    },
    onFieldWithoutDropdownChange(val) {
      this.field.without_dropdown = val
      if (this.field.without_dropdown) {
        this.field.allow_creation = false
        this.field.use_focused_selector = false
      }
    },
    onFieldUseDropdownInFocusedChange(val) {
      // Inverted logic: when "use dropdown instead" is ON, disable focused selector
      this.field.use_focused_selector = !val
      if (!this.field.use_focused_selector) {
        // When disabling focused selector (using dropdown instead), no need to disable other options
        // User can choose dropdown with creation or without_dropdown
      } else {
        // When enabling focused selector, force disable conflicting options
        this.field.without_dropdown = false
        this.field.allow_creation = false
      }
    },
    onFieldDisablePastDatesChange(val) {
      this.field.disable_past_dates = val
      if (this.field.disable_past_dates) {
        this.field.disable_future_dates = false
        this.field.prefill_today = false
      }
    },
    onFieldDisableFutureDatesChange(val) {
      this.field.disable_future_dates = val
      if (this.field.disable_future_dates) {
        this.field.disable_past_dates = false
        this.field.prefill_today = false
      }
    },
    onFieldHelpPositionChange(val) {
      if (!val) {
        this.field.help_position = 'below_input'
      }
    },
    onFieldMultiLinesChange(val) {
      this.field.multi_lines = val
      if (this.field.multi_lines) {
        this.field.secret_input = false
      }
    },
    onFieldSecretInputChange(val) {
      this.field.secret_input = val
      if (this.field.secret_input) {
        this.field.multi_lines = false
      }
    },
    selectAllCountries() {
      this.field.unavailable_countries = this.allCountries.map(item => {
        return item.code
      })
    },
    setDefaultFieldValues() {
      const defaultFieldValues = {
        files: {
          max_file_size: Math.min((this.field.max_file_size ?? this.mbLimit), this.mbLimit)
        },
        date: {
          date_format: this.dateFormatOptions[0].value,
          time_format: this.timeFormatOptions[0].value
        }
      }

      // Apply type-specific defaults from blocks_types.json if available
      if (this.field.type in blocksTypes && blocksTypes[this.field.type]?.default_values) {
        Object.keys(blocksTypes[this.field.type].default_values).forEach(key => {
          if (!_has(this.field, key)) {
            this.field[key] = blocksTypes[this.field.type].default_values[key]
          }
        })
      }

      // Apply additional defaults from defaultFieldValues if needed
      if (this.field.type in defaultFieldValues) {
        Object.keys(defaultFieldValues[this.field.type]).forEach(key => {
          if (!_has(this.field, key)) {
            this.field[key] = defaultFieldValues[this.field.type][key]
          }
        })
      }

      // Ensure critical defaults for specific types
      if (this.field.type === "rating" && !this.field.rating_max_value) {
        this.field.rating_max_value = 5
      } else if (this.field.type === "scale" && (!this.field.scale_min_value || !this.field.scale_max_value || !this.field.scale_step_value)) {
        this.field.scale_min_value = 1
        this.field.scale_max_value = 5
        this.field.scale_step_value = 1
      } else if (this.field.type === "slider" && (!this.field.slider_min_value || !this.field.slider_max_value || !this.field.slider_step_value)) {
        this.field.slider_min_value = 0
        this.field.slider_max_value = 50
        this.field.slider_step_value = 1
      } else if (["select", "multi_select"].includes(this.field.type) && !this.field[this.field.type]?.options) {
        this.field[this.field.type] = { options: [] }
      } else if (this.field.type === "checkbox" && this.isFocused && !this.field.focused_checkbox_style) {
        // Default to focused toggle in focused mode
        this.field.focused_checkbox_style = 'focused_toggle'
        this.field.use_focused_toggle = true
      }
    },
    updateMatrixField(newField) {
      this.field = newField
    },
    onFieldMaxCharLimitChange(val) {
      this.field.max_char_limit = val
      if(!this.field.max_char_limit) {
        this.field.show_char_limit = false
      }
    },
    onFieldMinSelectionChange(val) {
      this.field.min_selection = val ? parseInt(val) : null
    },
    onFieldMaxSelectionChange(val) {
      this.field.max_selection = val ? parseInt(val) : null
    },
    clearMinMaxSelection() {
      this.field.min_selection = null
      this.field.max_selection = null
    },
    onFieldFocusedCheckboxStyleChange(val) {
      this.field.focused_checkbox_style = val
      // Update field flags based on selection
      if (val === 'focused_toggle') {
        this.field.use_focused_toggle = true
        this.field.use_toggle_switch = false
      } else if (val === 'toggle_switch') {
        this.field.use_focused_toggle = false
        this.field.use_toggle_switch = true
      } else {
        this.field.use_focused_toggle = false
        this.field.use_toggle_switch = false
      }
    }
  }
}
</script>
