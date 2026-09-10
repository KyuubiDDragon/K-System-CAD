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

  const loadedStats: Array<{ label: string; value: number | string }> = []

  // Die drei Kennzahlen (Nachrichten, Aufgaben, Termine) waren nie
  // implementiert: sie schoben durchgehend '—' als Platzhalter, belegten dabei
  // aber die volle Kachelhoehe. Eine Kennzahl ohne Zahl ist keine Kennzahl,
  // deshalb bleiben sie ausgeblendet, bis echte Werte geladen werden.
  //
  // Zum Aktivieren: den jeweiligen Zaehler aus der API holen und hier
  // einhaengen, zum Beispiel
  //   loadedStats.push({ label: t('dashboard.stats.messages'), value: unread })

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
  gap: 8px;
  padding-top: 10px;
  border-top: 1px solid var(--k-line);
}

.stat-item {
  text-align: center;
  padding: 6px 8px;
  background: var(--k-row-hover);
  border-radius: 6px;
  transition: background 0.2s ease;

  &:hover {
    background: var(--k-row-hover);
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
