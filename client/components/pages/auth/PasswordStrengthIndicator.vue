<template>
  <div class="mt-2 space-y-2">
    <!-- Strength Bar -->
    <div class="flex items-center gap-2">
      <span class="text-xs font-medium text-neutral-600">{{ $t('auth.password_strength.label') }}</span>
      <div class="flex-1 bg-neutral-200 rounded-full h-2 max-w-32">
        <div 
          class="h-2 rounded-full transition-all duration-300"
          :class="strengthBarClass"
          :style="{ width: strengthPercentage + '%' }"
        ></div>
      </div>
      <span class="text-xs font-medium" :class="strengthTextClass">
        {{ strengthText }}
      </span>
    </div>
    
    <!-- Password Rules -->
    <div class="space-y-1">
      <div class="grid grid-cols-2 gap-1">
        <div 
          v-for="rule in passwordRulesConfig" 
          :key="rule.key"
          class="flex items-center gap-1"
        >
          <Icon 
            :name="passwordRules[rule.key] ? 'heroicons:check-circle' : 'heroicons:x-circle'"
            :class="passwordRules[rule.key] ? 'text-green-500' : 'text-neutral-400'"
            class="w-3 h-3"
          />
          <span class="text-xs" :class="passwordRules[rule.key] ? 'text-green-600' : 'text-neutral-500'">
            {{ $t(rule.messageKey) }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// Props
const props = defineProps({
  password: {
    type: String,
    default: ''
  }
})

const { t } = useI18n()

// Password rules configuration
const passwordRulesConfig = [
  {
    key: 'minLength',
    messageKey: 'auth.password_strength.rules.min_length',
    validator: (password) => password.length >= 8
  },
  {
    key: 'hasLetter',
    messageKey: 'auth.password_strength.rules.letter',
    validator: (password) => /[A-Za-z]/.test(password)
  },
  {
    key: 'hasNumber',
    messageKey: 'auth.password_strength.rules.number',
    validator: (password) => /[0-9]/.test(password)
  },
  {
    key: 'hasSpecial',
    messageKey: 'auth.password_strength.rules.special',
    validator: (password) => /[@$!%*#?&]/.test(password)
  }
]

// Password strength validation
const passwordRules = computed(() => {
  const password = props.password || ''
  const rules = {}
  
  passwordRulesConfig.forEach(rule => {
    rules[rule.key] = rule.validator(password)
  })
  
  return rules
})

// Calculate password strength
const passwordStrength = computed(() => {
  const rules = passwordRules.value
  const passedRules = Object.values(rules).filter(Boolean).length
  return passedRules
})

// Strength percentage for progress bar
const strengthPercentage = computed(() => {
  return (passwordStrength.value / passwordRulesConfig.length) * 100
})

// Strength configuration
const strengthConfig = [
  { threshold: 0, textKey: 'auth.password_strength.levels.very_weak', barClass: 'bg-neutral-300', textClass: 'text-neutral-500' },
  { threshold: 1, textKey: 'auth.password_strength.levels.weak', barClass: 'bg-red-500', textClass: 'text-red-600' },
  { threshold: 2, textKey: 'auth.password_strength.levels.fair', barClass: 'bg-orange-500', textClass: 'text-orange-600' },
  { threshold: 3, textKey: 'auth.password_strength.levels.good', barClass: 'bg-yellow-500', textClass: 'text-yellow-600' },
  { threshold: passwordRulesConfig.length, textKey: 'auth.password_strength.levels.strong', barClass: 'bg-green-500', textClass: 'text-green-600' }
]

// Strength bar styling
const strengthBarClass = computed(() => {
  const strength = passwordStrength.value
  const config = strengthConfig.find(config => strength <= config.threshold) || strengthConfig[strengthConfig.length - 1]
  return config.barClass
})

// Strength text and styling
const strengthText = computed(() => {
  const strength = passwordStrength.value
  const config = strengthConfig.find(config => strength <= config.threshold) || strengthConfig[strengthConfig.length - 1]
  return t(config.textKey)
})

const strengthTextClass = computed(() => {
  const strength = passwordStrength.value
  const config = strengthConfig.find(config => strength <= config.threshold) || strengthConfig[strengthConfig.length - 1]
  return config.textClass
})
</script>
