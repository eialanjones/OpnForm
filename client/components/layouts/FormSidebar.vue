<template>
  <BaseSidebar>

    <template #mobile-header>
      <h1 class="sm:hidden text-base font-medium text-neutral-500 tracking-wider px-2 truncate">
        {{ loading ? $t('common.states.loading') : form?.title }}
      </h1>
    </template>

    <!-- Navigation Slot -->
    <template #navigation>
      <div 
        v-for="(section, index) in navigationSections" 
        :key="section.name || 'main'"
        :class="[
          index !== navigationSections.length - 1 ? 'mb-6' : '',
          // Push shared sections to bottom
          index === 1 ? 'mt-auto' : ''
        ]"
      >
        <!-- Section Title (if exists) -->
        <h3 
          v-if="section.name"
          class="text-xs font-medium text-neutral-400 tracking-wider mb-2 px-3"
        >
          {{ section.name }}
        </h3>
        
        <!-- Section Items -->
        <NavigationList
          :items="section.items"
          :loading="loading"
          :skeleton-filter="(item) => section.name === null && item.label !== $t('app_shell.form_sidebar.dashboard')"
          tracking-name="sidebar_nav_click"
          :tracking-properties="(item) => ({ label: item.label, form_id: form?.id })"
        />
      </div>
    </template>
  </BaseSidebar>
</template>

<script setup>
import BaseSidebar from "~/components/layouts/BaseSidebar.vue"
import NavigationList from "~/components/global/NavigationList.vue"
import { useSharedNavigation } from "~/composables/components/useSharedNavigation"

const props = defineProps({
  form: {
    type: Object,
    required: false
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const { t } = useI18n()
const route = useRoute()
const { sharedNavigationSections, createNavItem } = useSharedNavigation()

const { current: workspace } = useCurrentWorkspace()

// Check if current route matches a prefix
function isActiveRoute(routeName) {
  if (!routeName) return false
  return route.name === routeName
}

// Form navigation items
const formNavigationItems = computed(() => [
  // Dashboard back button - always show
  createNavItem({
    label: t('app_shell.form_sidebar.dashboard'),
    icon: 'i-heroicons-arrow-left',
    to: { name: 'home' },
    active: false
  }),
  // Show skeleton placeholders when loading, otherwise show real items
  ...(props.loading ? [
    // These will be rendered as skeletons via the template
    createNavItem({
      label: t('app_shell.form_sidebar.submissions'),
      icon: 'i-heroicons-document-text',
      to: '#',
      active: false
    }),
    createNavItem({
      label: t('app_shell.form_sidebar.integrations'),
      icon: 'i-heroicons-puzzle-piece',
      to: '#',
      active: false
    }),
    createNavItem({
      label: t('app_shell.form_sidebar.analytics'),
      icon: 'i-heroicons-chart-bar',
      to: '#',
      active: false
    }),
    createNavItem({
      label: t('common.actions.share'),
      icon: 'i-heroicons-share',
      to: '#',
      active: false
    })
  ] : [
    createNavItem({
      label: t('app_shell.form_sidebar.submissions'),
      icon: 'i-heroicons-document-text',
      to: { name: 'forms-slug-show-submissions', params: { slug: props.form?.slug } },
      active: isActiveRoute('forms-slug-show-submissions')
    }),
    // Hide integrations for read-only workspaces
    ...(workspace.value?.is_readonly ? [] : [createNavItem({
      label: t('app_shell.form_sidebar.integrations'),
      icon: 'i-heroicons-puzzle-piece',
      to: { name: 'forms-slug-show-integrations', params: { slug: props.form?.slug } },
      active: isActiveRoute('forms-slug-show-integrations')
    })]),
    createNavItem({
      label: t('app_shell.form_sidebar.analytics'),
      icon: 'i-heroicons-chart-bar',
      to: { name: 'forms-slug-show-stats', params: { slug: props.form?.slug } },
      active: isActiveRoute('forms-slug-show-stats')
    }),
    createNavItem({
      label: t('common.actions.share'),
      icon: 'i-heroicons-share',
      to: { name: 'forms-slug-show-share', params: { slug: props.form?.slug } },
      active: isActiveRoute('forms-slug-show-share')
    })
  ])
])

// Navigation sections structure
const navigationSections = computed(() => [
  // Section 1: Form navigation (no name)
  {
    name: null,
    items: formNavigationItems.value
  },
  // Add shared navigation sections
  ...sharedNavigationSections.value
])
</script> 