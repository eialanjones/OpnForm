<template>
  <div class="px-4 pb-4">
    <EditorSectionHeader
      icon="heroicons:paint-brush-16-solid"
      :title="$t('form_blocks.customization.basic_appearance')"
      :show-line="false"
    />

    <PresentationStyleSwitch />

    <select-input
      name="theme"
      class="mt-4"
      :options="[
        { name: $t('common.labels.default'), value: 'default' },
        { name: $t('form_blocks.customization.themes.notion'), value: 'notion' },
        { name: $t('form_blocks.customization.themes.simple'), value: 'simple' },
        { name: $t('form_blocks.customization.themes.minimal'), value: 'minimal' },
        { name: $t('form_blocks.customization.themes.transparent'), value: 'transparent' }
      ]"
      :form="form"
      :label="$t('form_blocks.customization.form_theme_label')"
    />

    <color-input
      name="color"
      :form="form"
      :label="$t('form_blocks.customization.accent_color_label')"
      class="my-4"
    >
      <template #label>
        <InputLabel label="">{{ $t('form_blocks.customization.accent_color_label') }} - <a
          href="#" class="text-blue-500"
          @click.prevent="form.color = DEFAULT_COLOR"
        >{{ $t('common.actions.reset') }}</a></InputLabel>
      </template>
    </color-input>

    <OptionSelectorInput
      v-model="form.dark_mode"
      :form="form"
      name="dark_mode"
      :label="$t('form_blocks.customization.color_mode_label')"
      :options="[
        { name: 'auto', label: $t('form_blocks.customization.color_modes.system'), icon: 'i-heroicons-computer-desktop' },
        { name: 'light', label: $t('form_blocks.customization.color_modes.light'), icon: 'i-heroicons-sun' },
        { name: 'dark', label: $t('form_blocks.customization.color_modes.dark'), icon: 'i-heroicons-moon' },
      ]"
      :multiple="false"
      :columns="3"
      class="mb-4"
    />

    <EditorSectionHeader
      icon="octicon:typography-16"
      :title="$t('form_blocks.customization.text_and_language')"
    />
    <div class="grid grid-cols-2 gap-4">
      <div class="flex-grow my-1" v-if="useFeatureFlag('services.google.fonts')">
        <label class="text-neutral-700 font-semibold text-xs mb-0.5 block">{{ $t('form_blocks.customization.font_family_label') }}</label>
        <UButton
          color="neutral"
          variant="outline"
          block
          @click="showGoogleFontPicker = true"
        >
          <span :style="{ 'font-family': (form.font_family ? form.font_family + ' !important' : null) }">
            {{ form.font_family || $t('common.labels.default') }}
          </span>
        </UButton>
        <GoogleFontPicker
          :show="showGoogleFontPicker"
          :font="form.font_family || null"
          @close="showGoogleFontPicker = false"
          @apply="onApplyFont"
        />
      </div>

      <div class="flex-grow">
        <select-input
          name="language"
          searchable
          :options="availableLocales"
          :form="form"
          :label="$t('form_blocks.customization.language_label')"
        />
      </div>
    </div>

    <ToggleSwitchInput
      name="layout_rtl"
      :form="form"
      :label="$t('form_blocks.customization.rtl_label')"
    />
    
    <toggle-switch-input
      name="uppercase_labels"
      :form="form"
      :label="$t('form_blocks.customization.uppercase_labels_label')"
    />

    <EditorSectionHeader
      icon="heroicons:rectangle-stack-16-solid"
      :title="$t('form_blocks.customization.layout_and_sizing')"
    />
    <div class="grid grid-cols-2 gap-4">
      <OptionSelectorInput
        seamless
        :label="$t('form_blocks.customization.input_size_label')"
        v-model="form.size"
        :form="form"
        name="size"
        :options="[
          { name: 'sm', label: $t('form_blocks.customization.sizes.small') },
          { name: 'md', label: $t('form_blocks.customization.sizes.medium') },
          { name: 'lg', label: $t('form_blocks.customization.sizes.large') },
        ]"
        :multiple="false"
        :columns="3"
        class="mb-4"
      />
      <OptionSelectorInput
        v-if="form.theme !== 'transparent'"
        :label="$t('form_blocks.customization.input_roundness_label')"
        v-model="form.border_radius"
        seamless
        :form="form"
        name="border_radius"
        :options="[
          { name: 'none', icon: 'i-tabler-border-corner-square' },
          { name: 'small', icon: 'i-tabler-border-corner-rounded' },
          { name: 'full', icon: 'i-tabler-border-corner-pill' },
        ]"
        :multiple="false"
        :columns="3"
        class="mb-4"
      />
    </div>

    <OptionSelectorInput
      v-model="form.width"
      :label="$t('form_blocks.customization.form_width_label')"
      :form="form"
      name="width"
      seamless
      v-if="!isFocused"
      :options="[
        { name: 'centered', label: $t('form_blocks.customization.widths.centered') },
        { name: 'full', label: $t('form_blocks.customization.widths.full') },
      ]"
      :multiple="false"
      :columns="2"
      class="mb-4 w-2/3"
    />

    <EditorSectionHeader
      icon="heroicons:tag-16-solid"
      :title="$t('form_blocks.customization.branding')"
    />
    <div class="grid grid-cols-2 gap-4">
      <image-input
        name="logo_picture"
        :form="form"
        :label="$t('form_blocks.customization.logo_label')"
        :required="false"
      />

      <ImageWithSettings :form="form" name="cover_picture" :label="isFocused ? $t('form_blocks.customization.background_label') : $t('form_blocks.customization.cover_label')" kind="cover" />
    </div>

    <toggle-switch-input
      name="no_branding"
      :form="form"
      class="mt-4"
      @update:model-value="onChangeNoBranding"
    >
      <template #label>
        <InputLabel
          :label="$t('form_blocks.customization.hide_branding_label')"
          :native-for="'no_branding'"
          class="text-sm font-medium!"
        />
        <pro-tag
          :upgrade-modal-title="$t('form_blocks.customization.hide_branding_pro_title')"
          class="-mt-1"
        />
      </template>
    </toggle-switch-input>

    <EditorSectionHeader
      icon="heroicons:cog-6-tooth-16-solid"
      :title="$t('form_blocks.customization.advanced_options')"
    />

    <toggle-switch-input
      v-if="isFocused"
      name="settings.navigation_arrows"
      :form="form"
      class="mt-2"
      :label="$t('form_blocks.customization.navigation_arrows_label')"
    />
    <toggle-switch-input
      name="show_progress_bar"
      :form="form"
      :label="$t('form_blocks.customization.progress_bar_label')"
      :help="
        form.show_progress_bar
          ? $t('form_blocks.customization.progress_bar_help')
          : ''
      "
    />
    <toggle-switch-input
      name="transparent_background"
      :form="form"
      :label="$t('form_blocks.customization.transparent_background_label')"
      :help="$t('form_blocks.customization.transparent_background_help')"
    />
    <toggle-switch-input
      name="confetti_on_submission"
      :form="form"
      :label="$t('form_blocks.customization.confetti_label')"
      @update:model-value="onChangeConfettiOnSubmission"
    />
    <ToggleSwitchInput
      name="auto_focus"
      :form="form"
      :label="$t('form_blocks.customization.auto_focus_label')"
    />
  </div>
</template>

<script setup>
import EditorSectionHeader from "./EditorSectionHeader.vue"
import { useWorkingFormStore } from "../../../../../stores/working_form"
import GoogleFontPicker from "../../../editors/GoogleFontPicker.vue"
import ProTag from "~/components/app/ProTag.vue"
import { DEFAULT_COLOR, ensureSettingsObject } from "@/composables/forms/initForm"
import PresentationStyleSwitch from "./PresentationStyleSwitch.vue"
import ImageWithSettings from "../media/ImageWithSettings.vue"


const { t } = useI18n()
const workingFormStore = useWorkingFormStore()
const { openSubscriptionModal } = useAppModals()
const form = storeToRefs(workingFormStore).content
const isMounted = ref(false)
const confetti = useConfetti()
const showGoogleFontPicker = ref(false)
const { $i18n } = useNuxtApp()

const { data: user } = useAuth().user()
const { current: workspace } = useCurrentWorkspace()

const isPro = computed(() => {
  if (!useFeatureFlag('billing.enabled')) return true
  if (!user.value || !workspace.value) return false
  return workspace.value.is_pro
})

const isFocused = computed(() => form.value?.presentation_style === 'focused')

const availableLocales = computed(() => {
  return $i18n.locales?.value.map(locale => ({ name: locale.name, value: locale.code })) ?? []
})

onMounted(() => {
  isMounted.value = true
  
  // Ensure settings is a plain, writable object (avoid writing into readonly proxies)
  ensureSettingsObject(form.value)
  
  // Set default value for navigation_arrows in focused mode if not defined
  if (isFocused.value && form.value.settings.navigation_arrows === undefined) {
    form.value.settings.navigation_arrows = true
  }
})

const onChangeConfettiOnSubmission = (val) => {
  if (isMounted.value && val) {
    confetti.play()
  }
}

const onChangeNoBranding = (val) => {
  if (!isPro.value && val) {
    openSubscriptionModal({ modal_title: t('form_blocks.customization.hide_branding_pro_title') })
    setTimeout(() => {
      form.value.no_branding = false
    }, 300)
  } 
}

const onApplyFont = (val) => {
  form.value.font_family = val
  showGoogleFontPicker.value = false
}
</script>
