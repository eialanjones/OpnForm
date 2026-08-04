<template>
  <UDropdownMenu :items="shareItems" :content="{ align: 'end' }" arrow>
    <TrackClick
      name="social_share_button_click"
      :properties="{form_id: form.id, form_slug: form.slug}"
    >
      <UTooltip :text="$t('common.actions.share')">
        <UButton
          variant="outline"
          color="neutral"
          icon="i-heroicons-share"
        />
      </UTooltip>
    </TrackClick>
  </UDropdownMenu>
</template>

<script>
import TrackClick from '~/components/global/TrackClick.vue'

export default {
  name: "SocialShareButton",
  components: { TrackClick },
  props: {
    form: {
      type: Object,
      required: true,
    },
    shareUrl: {
      type: String,
      required: true,
    },
  },

  setup() {
    const { t } = useI18n()
    return { t }
  },

  computed: {
    shareItems() {
      return [
        [{
          label: this.t('form_pages.social_share.via_email'),
          icon: 'i-heroicons-envelope',
          onSelect: () => this.shareViaEmail()
        }, {
          label: this.t('form_pages.social_share.on_twitter'),
          icon: 'i-simple-icons-twitter',
          onSelect: () => this.shareOnTwitter()
        }, {
          label: this.t('form_pages.social_share.on_linkedin'),
          icon: 'i-simple-icons-linkedin',
          onSelect: () => this.shareOnLinkedIn()
        }]
      ]
    }
  },

  methods: {
    shareViaEmail() {
      const { logEvent } = useAmplitude()
      logEvent('share_form_email', {
        form_id: this.form.id,
        form_slug: this.form.slug,
        method: 'email'
      })
      
      const subject = encodeURIComponent(this.t('form_pages.social_share.share_text', { title: this.form.title }))
      const body = encodeURIComponent(this.t('form_pages.social_share.email_body', { url: this.shareUrl }))
      window.open(`mailto:?subject=${subject}&body=${body}`)
    },

    shareOnTwitter() {
      const { logEvent } = useAmplitude()
      logEvent('share_form_social', {
        form_id: this.form.id,
        form_slug: this.form.slug,
        method: 'twitter'
      })
      
      const text = encodeURIComponent(this.t('form_pages.social_share.share_text', { title: this.form.title }))
      const url = encodeURIComponent(this.shareUrl)
      window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`, '_blank')
    },

    shareOnLinkedIn() {
      const { logEvent } = useAmplitude()
      logEvent('share_form_social', {
        form_id: this.form.id,
        form_slug: this.form.slug,
        method: 'linkedin'
      })
      
      const url = encodeURIComponent(this.shareUrl)
      window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${url}`, '_blank')
    },
  },
}
</script> 