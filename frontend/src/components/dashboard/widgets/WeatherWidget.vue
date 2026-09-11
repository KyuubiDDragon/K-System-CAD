<template>
  <div class="weather-widget">
    <div v-if="loading" class="text-center pa-4">
      <v-progress-circular indeterminate size="32" />
    </div>
    <!--
        Ohne Temperatur gibt es nichts zu zeigen. Vorher wurde hier der Satz
        gezeichnet, den die Schnittstelle mitschickte - auf Englisch.
    -->
    <div v-else-if="!weather || weather.temperature === null" class="no-data pa-4 text-center">
      <v-icon size="48" color="grey">mdi-weather-cloudy-alert</v-icon>
      <p class="text-body-2 mt-2">{{ $t('dashboard.widget.weather.noData') }}</p>
    </div>
    <div v-else class="weather-content pa-2">
      <div class="current-weather text-center mb-3">
        <v-icon :icon="getWeatherIcon(weather.condition)" size="64" :color="getWeatherColor(weather.condition)" />
        <div class="temperature text-h3">{{ weather.temperature }}°</div>
        <div class="condition text-body-1">{{ weather.condition }}</div>
      </div>
      <v-divider class="my-2" />
      <div class="forecast">
        <div v-for="day in weather.forecast" :key="day.date" class="forecast-day d-flex align-center justify-space-between mb-1">
          <span class="text-caption">{{ formatDay(day.date) }}</span>
          <v-icon :icon="getWeatherIcon(day.condition)" size="small" />
          <span class="text-caption">{{ day.temp_high }}° / {{ day.temp_low }}°</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { apiClientAuth } from '@/api'

interface Props {
  widgetId: string
  config: any
}

defineProps<Props>()

const loading = ref(true)
const weather = ref<any>(null)

async function loadWeather() {
  loading.value = true
  try {
    const response = await apiClientAuth.get('/weather/?action=getCurrentWeather')
    weather.value = response.data
  } catch (err) {
    console.error('Failed to load weather:', err)
  } finally {
    loading.value = false
  }
}

function getWeatherIcon(condition: string): string {
  const icons: Record<string, string> = {
    sunny: 'mdi-weather-sunny',
    cloudy: 'mdi-weather-cloudy',
    rainy: 'mdi-weather-rainy',
    stormy: 'mdi-weather-lightning',
    snowy: 'mdi-weather-snowy',
  }
  return icons[condition?.toLowerCase()] || 'mdi-weather-partly-cloudy'
}

function getWeatherColor(condition: string): string {
  const colors: Record<string, string> = {
    sunny: 'yellow',
    cloudy: 'grey',
    rainy: 'blue',
    stormy: 'purple',
    snowy: 'light-blue',
  }
  return colors[condition?.toLowerCase()] || 'grey'
}

function formatDay(dateString: string): string {
  const date = new Date(dateString)
  return date.toLocaleDateString('en', { weekday: 'short' })
}

onMounted(() => loadWeather())
defineExpose({ refresh: loadWeather })
</script>

<style scoped lang="scss">
.weather-widget {
  height: 100%;
  overflow: hidden;
}

.temperature {
  font-weight: 300;
}

.condition {
  text-transform: capitalize;
}

.forecast-day {
  padding: 4px 0;
}
</style>
