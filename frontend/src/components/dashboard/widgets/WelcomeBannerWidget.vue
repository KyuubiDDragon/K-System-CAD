<template>
  <div class="welcome-banner">
    <div class="welcome-content">
      <div class="welcome-text">
        <h2 class="text-h5 mb-2">
          {{ $t('dashboard.welcome.greeting', { name: username }) }}
        </h2>
        <p class="text-body-2 text-grey">
          {{ currentDate }}
        </p>
      </div>

      <v-avatar
        v-if="config.showAvatar !== false"
        size="64"
        color="primary"
        class="welcome-avatar"
      >
        <span class="text-h4">{{ userInitial }}</span>
      </v-avatar>
    </div>

    <!-- Quick Stats -->
    <div v-if="config.showStats && stats.length > 0" class="quick-stats mt-4">
      <div
        v-for="stat in stats"
        :key="stat.label"
        class="stat-item"
      >
        <div class="stat-value text-h6">{{ stat.value }}</div>
        <div class="stat-label text-caption text-grey">{{ stat.label }}</div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useI18n } from 'vue-i18n'

interface Props {
  widgetId: string
  config: {
    showAvatar?: boolean
    showStats?: boolean
    statsToShow?: string[]
  }
}

const props = defineProps<Props>()
const authStore = useAuthStore()
const { t, d } = useI18n()

const stats = ref<Array<{ label: string; value: number | string }>>([])

// User data
const username = computed(() => {
  const name = authStore.user?.name || authStore.user?.username || 'User'
  return name.split(' ')[0] // First name only
})

const userInitial = computed(() => {
  return username.value.charAt(0).toUpperCase()
})

// Current date formatted in German locale
const currentDate = computed(() => {
  const now = new Date()
  return d(now, 'long') // Uses i18n date formatting
})

/**
 * Load quick stats if enabled
 */
async function loadStats() {
  if (!props.config.showStats) {
    return
  }

  // Load stats from various endpoints
  // This is a simplified version - expand based on what stats you want to show
  const statsToLoad = props.config.statsToShow || ['messages', 'todos', 'calendar']

  const loadedStats: Array<{ label: string; value: number | string }> = []

  // Example stats - you can expand this
  if (statsToLoad.includes('messages')) {
    // Load unread message count
    loadedStats.push({
      label: t('dashboard.stats.messages'),
      value: '—' // Placeholder
    })
  }

  if (statsToLoad.includes('todos')) {
    // Load open todos count
    loadedStats.push({
      label: t('dashboard.stats.todos'),
      value: '—' // Placeholder
    })
  }

  if (statsToLoad.includes('calendar')) {
    // Load today's events count
    loadedStats.push({
      label: t('dashboard.stats.events'),
      value: '—' // Placeholder
    })
  }

  stats.value = loadedStats
}

onMounted(() => {
  loadStats()
})
</script>

<style scoped lang="scss">
.welcome-banner {
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 1rem;
}

.welcome-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.welcome-text {
  flex: 1;
  min-width: 0;

  h2 {
    font-weight: 600;
  }
}

.welcome-avatar {
  flex-shrink: 0;
  border: 3px solid rgba(var(--v-theme-primary), 0.2);
}

.quick-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
  gap: 1rem;
  padding-top: 1rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.stat-item {
  text-align: center;
  padding: 0.5rem;
  background: rgba(255, 255, 255, 0.02);
  border-radius: 8px;
  transition: background 0.2s ease;

  &:hover {
    background: rgba(255, 255, 255, 0.05);
  }
}

.stat-value {
  font-weight: 700;
  color: rgba(var(--v-theme-primary));
}

.stat-label {
  margin-top: 0.25rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

// Responsive
@media (max-width: 600px) {
  .welcome-content {
    flex-direction: column;
    text-align: center;
  }

  .welcome-avatar {
    order: -1;
  }

  .quick-stats {
    grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
    gap: 0.5rem;
  }
}
</style>
