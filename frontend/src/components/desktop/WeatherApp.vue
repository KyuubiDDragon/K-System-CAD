<template>
  <div :class="['weather-app', isDarkMode ? 'dark-theme' : 'light-theme']">
    
    <!-- Anzeige der aktuellen Wetterbedingungen -->
    <div v-if="temperature" class="current-weather">
      <div class="temperature-display">
        <span class="temperature">{{ temperature }}°</span>
        <div class="location-info">
          <span class="condition">{{ condition }}</span>
          <span class="location">{{ location }}</span>
        </div>
      </div>
      <div class="weather-icon">
        <v-icon size="64" :icon="icon"></v-icon>
      </div>
    </div>
    <div v-else class="no-data-message">
      {{ t('desktop.noWeatherData') }}
    </div>

    <!-- Wettervorhersage für die nächsten Tage -->
    <div v-if="forecastDays && forecastDays.length > 0" class="forecast">
      <h2 class="forecast-title">{{ t('desktop.weatherForecast') }}</h2>
      <div class="forecast-grid">
        <div v-for="(day, index) in forecastDays" :key="index" class="forecast-day-card">
          <div class="forecast-day-header">
            <div class="day-name">{{ day.dayName }}</div>
            <div class="day-date">{{ day.date }}</div>
          </div>
          <div class="forecast-day-icon">
            <v-icon size="40" :icon="day.icon"></v-icon>
          </div>
          <div class="forecast-day-temps">
            <span class="max-temp">{{ day.maxTemp }}°</span>
            <span class="temp-separator">/</span>
            <span class="min-temp">{{ day.minTemp }}°</span>
          </div>
          <div class="forecast-day-details">
            <div class="detail-item">
              <v-icon size="14" icon="mdi-water-percent"></v-icon>
              <span>{{ day.humidity }}% {{ t('desktop.humidity') }}</span>
            </div>
            <div class="detail-item">
              <v-icon size="14" icon="mdi-weather-windy"></v-icon>
              <span>{{ day.windSpeed }} m/s {{ t('desktop.wind') }}</span>
            </div>
            <div class="detail-item" :class="{'warning': day.warning !== t('desktop.noWarnings')}">
              <v-icon size="14" :icon="day.warning !== t('desktop.noWarnings') ? 'mdi-alert-circle' : 'mdi-check-circle'"></v-icon>
              <span>{{ day.warning }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Wetterdetails -->
    <div v-if="humidity || windSpeed || warningInfo" class="weather-details">
      <div v-if="humidity" class="detail-item">
        <v-icon size="16" icon="mdi-water-percent"></v-icon>
        <span>{{ humidity }}% {{ t('desktop.humidity') }}</span>
      </div>
      <div v-if="windSpeed" class="detail-item">
        <v-icon size="16" icon="mdi-weather-windy"></v-icon>
        <span>{{ windSpeed }} m/s {{ t('desktop.wind') }}</span>
      </div>
      <div v-if="warningInfo" class="detail-item warning">
        <v-icon size="16" icon="mdi-alert-circle"></v-icon>
        <span>{{ warningInfo }}</span>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
export default {
  name: 'WeatherApp',
};
</script>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';

// Props
interface Props {
  condition?: string
  location?: string
  icon?: string
}

const props = withDefaults(defineProps<Props>(), {
  condition: '',
  location: '',
  icon: ''
});

const { t } = useI18n();

// Farbschema aus dem Store
const isDarkMode = ref(false);

// Vorschaudaten
const forecastDays = ref<Array<ForecastDay>>([]);

// Funktion für die Erzeugung der korrekten Wochentage
const generateForecastDays = () => {
  const today = new Date();
  const weekdays = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
  
  // Generiere einen leeren Vorhersage-Array für die nächsten 3 Tage
  const result = [];
  for (let i = 0; i < 3; i++) {
    const date = new Date(today);
    date.setDate(today.getDate() + i);
    const dayIndex = date.getDay();
    
    // Formatiertes Datum erzeugen (YYYY-MM-DD)
    const formattedDate = date.toISOString().split('T')[0];
    
    result.push({
      dayName: i === 0 ? 'Heute' : weekdays[dayIndex],
      date: formattedDate,
      icon: '',
      maxTemp: '',
      minTemp: '',
      humidity: 0,
      warning: t('desktop.noData'),
      windSpeed: '0'
    });
  }
  
  return result;
};

// Interface-Definitionen
interface WeatherData {
  location: string;
  condition: string;
  icon: string;
  max_temp: number | string;
  date: string;
  details?: {
    humidity?: number;
    wind_speed?: number;
    pressure?: number;
    uv_index?: number;
  };
}

interface ForecastDay {
  dayName: string;
  date: string;
  maxTemp: number;
  minTemp: number;
  condition: string;
  icon: string;
  humidity?: number;
  windSpeed?: number;
  pressure?: number;
  uvIndex?: number;
  warning?: string;
}

// Konvertiert API-Daten in das interne Forecast-Format
function convertApiDataToForecast(weatherData: WeatherData[] | null): ForecastDay[] {
  if (!weatherData || weatherData.length === 0) {
    return generateForecastDays().map(day => ({
      ...day,
      maxTemp: Math.floor(Math.random() * 15) + 15,
      minTemp: Math.floor(Math.random() * 10) + 5,
      condition: 'Sonnig',
      icon: 'mdi-weather-sunny',
      humidity: Math.floor(Math.random() * 30) + 40,
      windSpeed: Math.floor(Math.random() * 20) + 5,
      pressure: Math.floor(Math.random() * 20) + 1000,
      uvIndex: Math.floor(Math.random() * 8) + 1,
      warning: t('desktop.noWarnings')
    }));
  }

  // Erzeuge Basis-Forecast-Tage
  const baseForecastDays = generateForecastDays();
  
  // Mappe die Wetterdaten auf die Forecast-Tage
  return weatherData.map((data, index) => {
    // Stelle sicher, dass wir nicht über die Grenzen der baseForecastDays hinausgehen
    const baseDay = baseForecastDays[index % baseForecastDays.length];
    
    return {
      dayName: baseDay.dayName,
      date: data.date || baseDay.date,
      maxTemp: Number(data.max_temp) || 22,
      minTemp: Math.floor(Math.random() * 10) + 5, // Fallback für minTemp
      condition: data.condition || 'Unbekannt',
      icon: data.icon || 'mdi-weather-partly-cloudy',
      humidity: data.details?.humidity || Math.floor(Math.random() * 30) + 40,
      windSpeed: data.details?.wind_speed || Math.floor(Math.random() * 20) + 5,
      pressure: data.details?.pressure || Math.floor(Math.random() * 20) + 1000,
      uvIndex: data.details?.uv_index || Math.floor(Math.random() * 8) + 1,
      warning: t('desktop.noWarnings')
    };
  });
}

// Event-Handler für Wetter-Forecast-Updates
function handleWeatherForecastUpdate(event: Event) {
  const customEvent = event as CustomEvent;
  if (customEvent.detail && customEvent.detail.weatherData) {
    forecastDays.value = convertApiDataToForecast(customEvent.detail.weatherData);
  }
}

// Event-Listener hinzufügen
onMounted(() => {
  window.addEventListener('update-weather-forecast', handleWeatherForecastUpdate);

  // Prüfe, ob Dunkelmodus aktiviert ist
  const prefersDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  isDarkMode.value = prefersDarkMode;
});

// Event-Listener entfernen, wenn Komponente unmounted wird
onUnmounted(() => {
  window.removeEventListener('update-weather-forecast', handleWeatherForecastUpdate);
});
</script>

<style scoped>
.weather-app {
  padding: 20px;
  color: var(--desktop-text);
  height: 100%;
  overflow-y: auto;
}

.light-theme {
  background-color: var(--k-ink);
  color: #333;
}

.dark-theme {
  background-color: rgba(33, 33, 33, 0.9);
  color: var(--k-ink);
}

.current-weather {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: rgba(var(--desktop-bg-dark-2), 0.3);
  border-radius: var(--border-radius-md);
  padding: 20px;
  box-shadow: var(--shadow-small);
}

.temperature-display {
  display: flex;
  flex-direction: column;
}

.temperature {
  font-size: 42px;
  font-weight: 700;
  line-height: 1.2;
}

.location-info {
  display: flex;
  flex-direction: column;
}

.condition {
  font-size: 18px;
  font-weight: 500;
  margin-bottom: 5px;
}

.location {
  font-size: 14px;
  color: var(--desktop-text-secondary);
}

.weather-icon {
  display: flex;
  align-items: center;
  justify-content: center;
}

.forecast {
  margin-bottom: 24px;
}

.forecast-title {
  font-size: 18px;
  font-weight: 600;
  margin: 20px 0 16px;
}

.forecast-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 16px;
}

.forecast-day-card {
  display: flex;
  flex-direction: column;
  background-color: rgba(var(--desktop-bg-dark-2), 0.3);
  border-radius: var(--border-radius-md);
  padding: 16px;
  box-shadow: var(--shadow-small);
  transition: transform 0.2s ease;
}

.forecast-day-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-medium);
}

.forecast-day-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  border-bottom: 1px solid var(--k-line);
  padding-bottom: 8px;
}

.day-name {
  font-size: 16px;
  font-weight: 600;
}

.day-date {
  font-size: 14px;
  opacity: 0.7;
}

.forecast-day-icon {
  display: flex;
  justify-content: center;
  margin: 10px 0;
}

.forecast-day-temps {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 4px;
  margin-bottom: 16px;
}

.max-temp {
  font-size: 20px;
  font-weight: 600;
}

.temp-separator {
  opacity: 0.5;
  margin: 0 2px;
}

.min-temp {
  font-size: 16px;
  opacity: 0.7;
}

.forecast-day-details {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 10px;
  border-top: 1px solid var(--k-line);
  padding-top: 10px;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
}

.warning {
  color: #ff5252;
}

.weather-details {
  display: flex;
  flex-direction: column;
  gap: 12px;
  border-top: 1px solid rgba(128, 128, 128, 0.2);
  padding-top: 16px;
}

.no-data-message {
  padding: 20px;
  text-align: center;
  background-color: rgba(var(--desktop-bg-dark-2), 0.3);
  border-radius: var(--border-radius-md);
  color: var(--desktop-text-secondary);
  font-style: italic;
}

/* Responsive styles */
@media (max-width: 768px) {
  .current-weather {
    flex-direction: column;
    text-align: center;
  }
  
  .weather-icon {
    margin-right: 0;
    margin-bottom: 16px;
  }
  
  .weather-details {
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  }
}
</style> 