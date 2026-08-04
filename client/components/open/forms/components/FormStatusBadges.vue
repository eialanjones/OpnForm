<template>
  <div 
    v-if="shouldDisplayBadges" 
    class="flex items-center flex-wrap gap-1"
  >
    <!-- Draft Badge -->
    <UTooltip v-if="form.visibility === 'draft'" :text="$t('form_editor.status_badges.draft_tooltip')">
      <UBadge
        color="warning"
        variant="subtle"
        icon="i-heroicons-pencil-square"
        :size="size"
      >
        {{ $t('form_editor.status_badges.draft') }}
      </UBadge>
    </UTooltip>

    <!-- Closed Badge -->
    <UTooltip v-else-if="form.visibility === 'closed'" :text="$t('form_editor.status_badges.closed_tooltip')">
      <UBadge
        color="neutral"
        variant="subtle"
        icon="i-heroicons-lock-closed"
        :size="size"
      >
        {{ $t('form_editor.status_badges.closed') }}
      </UBadge>
    </UTooltip>

    <!-- Time Limited Badge -->
     <template v-else-if="form.closes_at">
      <UTooltip v-if="!form.is_closed" :text="$t('form_editor.status_badges.will_close_on', { date: closesDate })">
        <UBadge
          color="warning"
          variant="subtle"
          icon="i-heroicons-clock"
          :size="size"
        >
          {{ $t('form_editor.status_badges.time_limited') }}
        </UBadge>
      </UTooltip>
      <UTooltip v-else :text="$t('form_editor.status_badges.closed_on', { date: closesDate })">
        <UBadge
          color="neutral"
          variant="subtle"
          icon="i-heroicons-clock"
          :size="size"
        >
          {{ $t('form_editor.status_badges.closed') }}
        </UBadge>
      </UTooltip>
  </template>

    <!-- Submission Limited Badge -->
    <template v-else-if="form.max_submissions_count > 0">
      <UTooltip
        v-if="!form.max_number_of_submissions_reached"
        :text="$t('form_editor.status_badges.limited_to', { count: form.max_submissions_count })"
      >
        <UBadge
          color="warning"
          variant="subtle"
          icon="i-heroicons-chart-bar"
          :size="size"
        >
          {{ $t('form_editor.status_badges.submission_limited') }}
        </UBadge>
      </UTooltip>
      <UTooltip
        v-else
        :text="$t('form_editor.status_badges.max_reached', { count: form.max_submissions_count })"
      >
        <UBadge
          color="neutral"
          variant="subtle"
          icon="i-heroicons-lock-closed"
          :size="size"
        >
          {{ $t('form_editor.status_badges.limit_reached') }}
        </UBadge>
      </UTooltip>
    </template>
    
    <!-- Tags Badges -->
    <UBadge
      v-if="withTags"
      v-for="tag in form.tags"
      :key="tag"
      color="neutral"
      variant="outline"
      class="capitalize"
      :size="size"
    >
      {{ tag }}
    </UBadge>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  form: {
    type: Object,
    required: true
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['xs', 'sm', 'md', 'lg'].includes(value)
  },
  withTags: {
    type: Boolean,
    default: true
  }
})

const closesDate = computed(() => {
  if (props.form && props.form.closes_at) {
    try {
      const dateObj = new Date(props.form.closes_at)
      return dateObj.getFullYear() + '-' +
        String(dateObj.getMonth() + 1).padStart(2, '0') + '-' +
        String(dateObj.getDate()).padStart(2, '0') + ' ' +
        String(dateObj.getHours()).padStart(2, '0') + ':' +
        String(dateObj.getMinutes()).padStart(2, '0')
    } catch (e) {
      console.error(e)
      return null
    }
  }
  return null
})

// Conditional to determine if badges should be displayed
const shouldDisplayBadges = computed(() => {
  return ['draft', 'closed'].includes(props.form.visibility) || 
         (props.form.tags && props.form.tags.length > 0) || 
         props.form.closes_at || 
         (props.form.max_submissions_count > 0)
})
</script> 