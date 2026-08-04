<template>
  <div 
    v-motion
    :initial="{ opacity: 0, height: 0 }"
    :enter="{ opacity: 1, height: 52, transition: { duration: 300, ease: 'easeOut' } }"
    :leave="{ opacity: 0, height: 0, transition: { duration: 300, ease: 'easeIn' } }"
    class="overflow-hidden w-full"
  >
    <div 
      v-motion
      :initial="{ opacity: 0, scale: 0.95 }"
      :enter="{ opacity: 1, scale: 1, transition: { duration: 300 } }"
      class="flex items-center gap-3 bg-blue-50 px-4 py-3 rounded-lg w-full"
    >
      <div 
        v-motion
        class="w-7 h-7 rounded-full bg-gradient-to-r from-blue-500 to-blue-300 flex items-center justify-center text-base"
        :initial="{ rotate: 0 }"
        :enter="{ 
          rotate: [-8, 8, -8, 8, 0],
          transition: { 
            repeat: Infinity,
            duration: 1500,
            ease: 'easeInOut',
            times: [0, 0.25, 0.5, 0.75, 1]
          }
        }"
      >
        {{ currentEmoji }}
      </div>
      <span 
        :key="currentMessage"
        v-motion
        :initial="{ opacity: 0, y: 5 }"
        :enter="{ opacity: 1, y: 0, transition: { duration: 200 } }"
        class="text-sm text-neutral-700 font-medium"
      >
        {{ currentMessage }}
      </span>
      <span class="text-xs text-neutral-500 ml-auto tabular-nums">{{ elapsedTime }}s</span>
    </div>
  </div>
</template>

<script setup>
const { t } = useI18n()

const messages = [
  { text: t("form_editor.ai_loading.starting_magic"), emoji: "✨" },
  { text: t("form_editor.ai_loading.analyzing_requirements"), emoji: "🤔" },
  { text: t("form_editor.ai_loading.designing_layout"), emoji: "📐" },
  { text: t("form_editor.ai_loading.adding_fields"), emoji: "📝" },
  { text: t("form_editor.ai_loading.fine_tuning_validation"), emoji: "🎯" },
  { text: t("form_editor.ai_loading.optimizing_ux"), emoji: "💫" },
  { text: t("form_editor.ai_loading.adding_smart_features"), emoji: "🧠" },
  { text: t("form_editor.ai_loading.polishing_design"), emoji: "✨" },
  { text: t("form_editor.ai_loading.running_final_checks"), emoji: "🔍" },
  { text: t("form_editor.ai_loading.almost_ready"), emoji: "🚀" },
]

const currentMessage = ref(messages[0].text)
const currentEmoji = ref(messages[0].emoji)
const messageIndex = ref(0)
const startTime = ref(Date.now())
const elapsedTime = ref(0)

// Update message every 2.5 seconds
const interval = setInterval(() => {
  const nextIndex = (messageIndex.value + 1) % messages.length
  messageIndex.value = nextIndex
  currentMessage.value = messages[nextIndex].text
  currentEmoji.value = messages[nextIndex].emoji
}, 2500)

// Update elapsed time every second
const timeInterval = setInterval(() => {
  elapsedTime.value = Math.floor((Date.now() - startTime.value) / 1000)
}, 1000)

onUnmounted(() => {
  clearInterval(interval)
  clearInterval(timeInterval)
})
</script>

<style>
.animate-shimmer {
  animation: shimmer 2s infinite linear;
}

@keyframes shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}
</style> 