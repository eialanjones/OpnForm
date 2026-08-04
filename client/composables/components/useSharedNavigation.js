import opnformConfig from "~/opnform.config.js"

export const useSharedNavigation = () => {
  const crisp = useCrisp()
  const { t } = useI18n()

  const isSelfHosted = computed(() => useFeatureFlag('self_hosted'))

  // Default button configuration
  const defaultButtonProps = {
    variant: 'ghost',
    activeVariant: 'soft', 
    color: 'neutral',
    block: true,
  }

  // Helper function to apply defaults to navigation items
  const createNavItem = (item) => {
    const baseItem = {
      ...defaultButtonProps,
      ...item
    }
    
    // Add custom classes to darken ghost/soft variants for better visibility on neutral-100 background
    const customClasses = ['group']
    
    // For ghost variant (default), darken hover state
    if (baseItem.variant === 'ghost' && baseItem.color === 'neutral') {
      customClasses.push('hover:bg-neutral-200/80')
      baseItem.ui = {
        ...baseItem.ui,
        leadingIcon: 'text-neutral-400 group-hover:text-neutral-500'
      }
    }
    
    // For soft variant (active state), darken background
    if (baseItem.active && baseItem.activeVariant === 'soft' && baseItem.color === 'neutral') {
      customClasses.push('bg-neutral-200/90 text-neutral-800')
    }
    
    // For primary color buttons, ensure good contrast
    if (baseItem.color === 'primary') {
      if (baseItem.variant === 'ghost') {
        customClasses.push('hover:bg-primary-100/80')
      }
      if (baseItem.active && baseItem.activeVariant === 'soft') {
        customClasses.push('data-[active=true]:bg-primary-100/90')
      }
    }
    
    return {
      ...baseItem,
      class: customClasses.length > 0 ? customClasses.join(' ') : undefined
    }
  }

  // Shared navigation sections
  const sharedNavigationSections = computed(() => [
    // Help section
    {
      name: t('runtime.navigation.help'),
      items: [
        createNavItem({
          label: t('runtime.navigation.help_center'),
          icon: 'i-heroicons-question-mark-circle',
          to: opnformConfig.links.help_url,
          target: '_blank'
        }),
        createNavItem({
          label: t('runtime.navigation.api_docs'),
          icon: 'i-heroicons-code-bracket',
          to: opnformConfig.links.api_docs,
          target: '_blank'
        }),
        ...(isSelfHosted.value || !crisp ? [] : [createNavItem({
          label: t('runtime.navigation.contact_support'),
          icon: 'i-heroicons-chat-bubble-left-right',
          onClick: () => crisp.openChat()
        })])
      ]
    }
  ])

  return {
    sharedNavigationSections,
    createNavItem,
    defaultButtonProps
  }
} 