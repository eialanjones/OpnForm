<template>
  <UPopover arrow>
    <TrackClick
      name="form_qr_code_click"
      :properties="{form_id: form.id, form_slug: form.slug}"
    >
      <UTooltip :text="$t('form_pages.qr_code.tooltip')">
        <UButton
          variant="outline"
          color="neutral"
          icon="i-heroicons-qr-code"
        />
      </UTooltip>
    </TrackClick>

    <template #content>
      <div class="p-4 w-80">

        <h3 class="font-semibold text-medium">
          {{ $t('form_pages.qr_code.heading') }}
        </h3>
        <p class="text-sm text-neutral-600">{{ $t('form_pages.qr_code.description') }}</p>
        <div class="flex justify-center">
          <img
            v-if="QrUrl"
            ref="qrImage"
            :src="QrUrl"
            class="max-w-full h-auto"
            :alt="$t('form_pages.qr_code.alt')"
          >
        </div>
        <div class="space-y-2">
          <TrackClick
            name="qr_code_copy_image_click"
            :properties="{form_id: form.id, form_slug: form.slug}"
          >
            <UButton
              @click="copyImage"
              :color="imageCopied ? 'success' : 'neutral'"
              :icon="imageCopied ? 'i-heroicons-check' : 'i-heroicons-document-duplicate'"
              variant="outline"
              block
            >
              {{ imageCopied ? $t('form_pages.qr_code.image_copied') : $t('form_pages.qr_code.copy_image') }}
            </UButton>
          </TrackClick>
        </div>
      </div>
    </template>
  </UPopover>
</template>

<script>
import QRCode from "qrcode"
import { useClipboard } from '@vueuse/core'
import TrackClick from '~/components/global/TrackClick.vue'

export default {
  name: "FormQrCode",
  components: { TrackClick },
  props: {
    form: { type: Object, required: true },
    extraQueryParam: { type: String, default: "" },
  },

  data() {
    return {
      QrUrl: null,
      imageCopied: false,
    }
  },

  computed: {
    shareUrl() {
      return this.extraQueryParam
        ? this.form.share_url + "?" + this.extraQueryParam
        : this.form.share_url + this.extraQueryParam
    },
  },

  setup() {
    // Use VueUse clipboard for URL copying
    const { copy: copyToClipboard, copied: urlCopied } = useClipboard({
      copiedDuring: 2000 // Reset after 2 seconds
    })

    return {
      copyToClipboard,
      urlCopied
    }
  },

  watch: {
    shareUrl() {
      this.generateQR()
    },
  },

  mounted() {
    this.generateQR()
  },

  methods: {
    generateQR() {
      QRCode.toDataURL(this.shareUrl).then((url) => {
        this.QrUrl = url
      })
    },
    
    copyImage() {
      // Convert data URL to blob
      fetch(this.QrUrl).then((response) => {
        return response.blob()
      }).then((blob) => {
        // Copy to clipboard
        return navigator.clipboard.write([
          new ClipboardItem({
            [blob.type]: blob
          })
        ])
      }).then(() => {
        // Show success state
        this.imageCopied = true
        setTimeout(() => {
          this.imageCopied = false
        }, 2000)
      }).catch((error) => {
        console.error('Failed to copy image:', error)
      })
    },
  },
}
</script>
