<template>
  <div>
    <TrackClick
      name="share_embed_form_popup_click"
      :properties="{
        form_id: form.id,
        form_slug: form.slug,
      }"
    >
      <UButton
        variant="outline"
        color="neutral"
        icon="i-heroicons-chat-bubble-bottom-center-text"
        @click="onOpenClick"
      >
        {{ $t('form_pages.embed_popup.trigger') }}
      </UButton>
    </TrackClick>

    <UModal
      v-model:open="isModalOpen"
      :ui="{ content: 'sm:max-w-2xl' }"
      :title="$t('form_pages.embed_popup.modal_title')"
      :content="{
        onPointerDownOutside: (event) => { if (event.target?.closest('.nf-main')) {return event.preventDefault()}}
      }"
    >
      <template #body>
        <h3 class="text-xl font-semibold mb-2">
          {{ $t('form_pages.embed_popup.demo_heading') }}
        </h3>
        <p class="pb-6">
          {{ $t('form_pages.embed_popup.demo_intro') }}
          <span class="font-semibold text-blue-800">{{ $t('form_pages.embed_popup.demo_cta', { position: positionWord }) }}</span>.
        </p>

        <h3 class="border-t text-xl font-semibold mb-2 pt-6">
          {{ $t('form_pages.shared.how_it_works') }}
        </h3>
        <p v-html="$t('form_pages.embed_popup.paste_snippet')" />

        <div class="border border-blue-300 bg-blue-50 dark:bg-notion-dark-light rounded-md p-4 mb-5 w-full mx-auto mt-4 select-all">
          <div class="flex items-center">
            <p class="select-all text-blue-500 flex-grow break-all">
              {{ embedPopupCode }}
            </p>
            <div
              class="hover:bg-blue-100 rounded-sm transition-colors cursor-pointer"
              @click="copyToClipboard"
            >
                <Icon 
                  name="heroicons:clipboard-document" 
                  class="h-6 w-6 text-blue-500" 
                />
            </div>
          </div>
        </div>

        <div class="border-t my-4" />
        <VForm size="sm">
          <h3 class="text-xl font-semibold mb-2">
            {{ $t('form_pages.embed_popup.customization_heading') }}
          </h3>

          <ColorInput
            v-model="advancedOptions.bgcolor"
            name="bgcolor"
            :label="$t('form_pages.embed_popup.bgcolor_label')"
          />
          <TextInput
            v-model="advancedOptions.emoji"
            name="emoji"
            class="mt-4 max-w-xs"
            :label="$t('form_pages.embed_popup.emoji_label')"
            :max-char-limit="2"
          />
          <FlatSelectInput
            v-model="advancedOptions.position"
            name="position"
            class="mt-4 max-w-xs"
            :label="$t('form_pages.embed_popup.position_label')"
            :options="[
              { name: $t('form_pages.embed_popup.position_bottom_right'), value: 'right' },
              { name: $t('form_pages.embed_popup.position_bottom_left'), value: 'left' },
            ]"
          />
          <TextInput
            v-model="advancedOptions.width"
            name="width"
            class="mt-4 max-w-xs"
            :label="$t('form_pages.embed_popup.width_label')"
            native-type="number"
          />
        </VForm>
      </template>
    </UModal>
  </div>
</template>

<script setup>
import { ref, defineProps, computed } from "vue"
import { appUrl } from "~/lib/utils.js"
import TrackClick from "~/components/global/TrackClick.vue"

const { copy } = useClipboard()
const { t } = useI18n()
const crisp = useCrisp()
const props = defineProps({
  form: { type: Object, required: true },
})

const embedScriptUrl = "/widgets/embed-min.js"
const showEmbedFormAsPopupModal = ref(false)

// Modal state
const isModalOpen = computed({
  get() {
    return showEmbedFormAsPopupModal.value
  },
  set(value) {
    if (!value) {
      onClose()
    }
  }
})

const advancedOptions = ref({
  emoji: "💬",
  position: "right",
  bgcolor: "#FE7D22",
  width: "500",
})

const positionWord = computed(() => {
  return advancedOptions.value.position === "left"
    ? t('form_pages.embed_popup.position_word_left')
    : t('form_pages.embed_popup.position_word_right')
})

const shareUrl = computed(() => {
  return props.form.share_url
})
const embedPopupCode = computed(() => {
  const nfData = {
    formurl: shareUrl.value,
    emoji: advancedOptions.value.emoji,
    position: advancedOptions.value.position,
    bgcolor: advancedOptions.value.bgcolor,
    width: advancedOptions.value.width,
  }
  previewPopup(nfData)
  return (
    "<script async data-nf='" +
    JSON.stringify(nfData) +
    "' src='" +
    appUrl(embedScriptUrl) +
    "'></scrip" +
    "t>"
  )
})

onMounted(() => {
  advancedOptions.value.bgcolor = props.form.color
})

const onClose = () => {
  removePreview()
  crisp.showChat()
  showEmbedFormAsPopupModal.value = false
}
const onOpenClick = () => {
  const style = props.form?.presentation_style || 'classic'
  if (style === 'focused') {
    useAlert().warning(t('form_pages.embed_popup.focused_not_supported'))
    return
  }
  showEmbedFormAsPopupModal.value = true
}
const copyToClipboard = () => {
  if (import.meta.server) return
  copy(embedPopupCode.value)
  useAlert().success(t('common.states.copied'))
}
const removePreview = () => {
  if (import.meta.server) return
  const oldP = document.head.querySelector("#nf-popup-preview")
  if (oldP) {
    oldP.remove()
  }
  const oldM = document.body.querySelector(".nf-main")
  if (oldM) {
    oldM.remove()
  }
}
const previewPopup = (nfData) => {
  if (import.meta.server) return
  if (!showEmbedFormAsPopupModal.value) {
    return
  }

  // Remove old preview, if there
  removePreview()

  // Hide crisp
  crisp.hideChat()

  // Add new preview
  const el = document.createElement("script")
  el.id = "nf-popup-preview"
  el.async = true
  el.src = embedScriptUrl
  el.setAttribute("data-nf", JSON.stringify(nfData))
  document.head.appendChild(el)
}
</script>
