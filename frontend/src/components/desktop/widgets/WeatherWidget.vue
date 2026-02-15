<template>
  <widget-container
    :id="widgetId"
    :title="$t('widgets.weather.title')"
    icon="mdi-weather-partly-cloudy"
    color="blue"
    :position="position"
    :min-width="320"
    :min-height="400"
    :max-width="() => Math.min(450, window.innerWidth - 120)"
    :max-height="600"
    @update:position="$emit('update:position', $event)"
    @close="$emit('close')"
    @focus="$emit('focus')"
  >
    <div class="weather-widget">
      <!-- Loading State -->
      <div v-if="isLoading" class="text-center py-8">
        <v-progress-circular indeterminate color="primary" />
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-4">
        <v-icon size="48" color="error">mdi-weather-tornado</v-icon>
        <p class="text-error mt-2">{{ error }}</p>
        <v-btn size="small" @click="fetchWeather" class="mt-2">
          {{ $t('common.retry') }}
        </v-btn>
      </div>

      <!-- Weather Data -->
      <div v-else class="weather-content">
        <!-- Current Weather -->
        <div class="current-weather">
          <div class="weather-main">
            <v-icon :size="64" :color="weatherIconColor">
              {{ weatherIcon }}
            </v-icon>
            <div class="temperature">
              {{ Math.round(weatherData.temp) }}°C
            </div>
          </div>
          
          <div class="weather-info">
            <h3 class="location">{{ weatherData.location }}</h3>
            <p class="condition">{{ weatherData.condition }}</p>
            <p class="feels-like">
              {{ $t('widgets.weather.feelsLike') }}: {{ Math.round(weatherData.feelsLike) }}°C
            </p>
          </div>
        </div>

        <v-divider class="my-3" />

        <!-- Weather Details -->
        <div class="weather-details">
          <div class="detail-item">
            <v-icon size="20">mdi-water-percent</v-icon>
            <span>{{ weatherData.humidity }}%</span>
          </div>
          <div class="detail-item">
            <v-icon size="20">mdi-weather-windy</v-icon>
            <span>{{ weatherData.windSpeed }} km/h</span>
          </div>
          <div class="detail-item">
            <v-icon size="20">mdi-gauge</v-icon>
            <span>{{ weatherData.pressure }} hPa</span>
          </div>
          <div class="detail-item">
            <v-icon size="20">mdi-eye</v-icon>
            <span>{{ weatherData.visibility }} km</span>
          </div>
        </div>

        <v-divider class="my-3" />

        <!-- 5-Day Forecast -->
        <div class="forecast">
          <h4 class="text-subtitle-2 mb-2">{{ $t('widgets.weather.forecast') }}</h4>
          <div class="forecast-days">
            <div 
              v-for="day in forecast" 
              :key="day.date"
              class="forecast-day"
            >
              <p class="day-name">{{ formatDay(day.date) }}</p>
              <v-icon :size="24" :color="getWeatherColor(day.condition)">
                {{ getWeatherIcon(day.condition) }}
              </v-icon>
              <p class="day-temp">{{ Math.round(day.maxTemp) }}°</p>
              <p class="day-temp-min">{{ Math.round(day.minTemp) }}°</p>
            </div>
          </div>
        </div>

        <!-- Last Update -->
        <div class="last-update">
          <v-icon size="12">mdi-update</v-icon>
          <span>{{ formatLastUpdate(lastUpdate) }}</span>
        </div>
      </div>
    </div>
  </widget-container>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import WidgetContainer from './WidgetContainer.vue';
import { apiClientAuth } from '@/api';

interface WeatherData {
  temp: number;
  feelsLike: number;
  condition: string;
  location: string;
  humidity: number;
  windSpeed: number;
  pressure: number;
  visibility: number;
  icon: string;
}

interface ForecastDay {
  date: string;
  condition: string;
  maxTemp: number;
  minTemp: number;
}

interface Props {
  position?: { x: number; y: number; width: number; height: number };
}

const props = withDefaults(defineProps<Props>(), {
  position: () => ({ x: 20, y: 80, width: 280, height: 400 })
});

const emit = defineEmits<{
  'update:position': [position: any];
  'close': [];
  'focus': [];
}>();

const { t, locale } = useI18n();

// State
const widgetId = `weather-widget-${Date.now()}`;
const isLoading = ref(true);
const error = ref<string | null>(null);
const weatherData = ref<WeatherData>({
  temp: 0,
  feelsLike: 0,
  condition: '',
  location: 'Los Santos',
  humidity: 0,
  windSpeed: 0,
  pressure: 0,
  visibility: 0,
  icon: 'mdi-weather-partly-cloudy'
});
const forecast = ref<ForecastDay[]>([]);
const lastUpdate = ref<Date>(new Date());

// Auto-refresh timer
let refreshInterval: number | null = null;

// Computed
const weatherIcon = computed(() => {
  return weatherData.value.icon || getWeatherIcon(weatherData.value.condition);
});

const weatherIconColor = computed(() => {
  return getWeatherColor(weatherData.value.condition);
});

// Methods
const getWeatherIcon = (condition: string): string => {
  const conditionLower = condition.toLowerCase();
  
  if (conditionLower.includes('sun') || conditionLower.includes('clear')) {
    return 'mdi-weather-sunny';
  } else if (conditionLower.includes('cloud')) {
    return 'mdi-weather-cloudy';
  } else if (conditionLower.includes('rain')) {
    return 'mdi-weather-rainy';
  } else if (conditionLower.includes('storm') || conditionLower.includes('thunder')) {
    return 'mdi-weather-lightning';
  } else if (conditionLower.includes('snow')) {
    return 'mdi-weather-snowy';
  } else if (conditionLower.includes('fog') || conditionLower.includes('mist')) {
    return 'mdi-weather-fog';
  } else if (conditionLower.includes('wind')) {
    return 'mdi-weather-windy';
  } else if (conditionLower.includes('night')) {
    return 'mdi-weather-night';
  }
  
  return 'mdi-weather-partly-cloudy';
};

const getWeatherColor = (condition: string): string => {
  const conditionLower = condition.toLowerCase();
  
  if (conditionLower.includes('sun') || conditionLower.includes('clear')) {
    return 'orange';
  } else if (conditionLower.includes('cloud')) {
    return 'grey';
  } else if (conditionLower.includes('rain')) {
    return 'blue';
  } else if (conditionLower.includes('storm') || conditionLower.includes('thunder')) {
    return 'purple';
  } else if (conditionLower.includes('snow')) {
    return 'light-blue';
  }
  
  return 'blue-grey';
};

const formatDay = (dateStr: string): string => {
  const date = new Date(dateStr);
  const today = new Date();
  const tomorrow = new Date(today);
  tomorrow.setDate(tomorrow.getDate() + 1);
  
  if (date.toDateString() === today.toDateString()) {
    return t('common.today');
  } else if (date.toDateString() === tomorrow.toDateString()) {
    return t('common.tomorrow');
  }
  
  // Return short day name based on locale
  return date.toLocaleDateString(locale.value, { weekday: 'short' });
};

const formatLastUpdate = (date: Date): string => {
  const now = new Date();
  const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / 60000);
  
  if (diffInMinutes < 1) {
    return t('common.justNow');
  } else if (diffInMinutes < 60) {
    return t('common.minutesAgo', { count: diffInMinutes });
  } else {
    const hours = Math.floor(diffInMinutes / 60);
    return t('common.hoursAgo', { count: hours });
  }
};

const fetchWeather = async () => {
  isLoading.value = true;
  error.value = null;
  
  try {
    // Fetch weather data from K-Systems backend
    const response = await apiClientAuth.get('/weather/', {
      params: { action: 'getWeather' }
    });
    
    if (response.data && Array.isArray(response.data)) {
      // K-Systems weather data is an array of days
      const weatherDays = response.data;
      
      if (weatherDays.length > 0) {
        // Use first day as current weather
        const today = weatherDays[0];
        
        // Calculate average temp for current display
        const avgTemp = Math.round((today.max_temp + today.min_temp) / 2);
        
        weatherData.value = {
          temp: avgTemp,
          feelsLike: avgTemp - 2, // Approximate feels like
          condition: today.condition || 'Partly Cloudy',
          location: 'Los Santos', // Fixed location for K-Systems
          humidity: today.humidity || 65,
          windSpeed: today.wind_speed || 15,
          pressure: 1013, // Default pressure
          visibility: 10, // Default visibility
          icon: getWeatherIcon(today.condition || 'Partly Cloudy')
        };
        
        // Map forecast from weather data (max 5 days)
        forecast.value = weatherDays.slice(0, 5).map(day => ({
          date: day.date,
          condition: day.condition || 'Partly Cloudy',
          maxTemp: Math.round(day.max_temp),
          minTemp: Math.round(day.min_temp)
        }));
        
        lastUpdate.value = new Date();
      } else {
        // No weather data available, use defaults
        throw new Error('No weather data available');
      }
    } else {
      throw new Error('Invalid weather response format');
    }
  } catch (err) {
    console.error('Weather fetch error:', err);
    error.value = t('widgets.weather.fetchError');
    
    // Use mock data as fallback
    weatherData.value = {
      temp: 22,
      feelsLike: 20,
      condition: 'Partly Cloudy',
      location: 'Los Santos',
      humidity: 65,
      windSpeed: 15,
      pressure: 1013,
      visibility: 10,
      icon: 'mdi-weather-partly-cloudy'
    };
  } finally {
    isLoading.value = false;
  }
};

// Lifecycle
onMounted(() => {
  fetchWeather();
  
  // Refresh every 10 minutes
  refreshInterval = window.setInterval(() => {
    fetchWeather();
  }, 600000);
});

onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval);
  }
});
</script>

<style scoped lang="scss">
.weather-widget {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.weather-content {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.current-weather {
  text-align: center;
  padding: 16px 0;
}

.weather-main {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  margin-bottom: 16px;
}

.temperature {
  font-size: 48px;
  font-weight: 300;
  line-height: 1;
}

.weather-info {
  .location {
    font-size: 18px;
    font-weight: 500;
    margin-bottom: 4px;
  }
  
  .condition {
    font-size: 16px;
    color: rgba(var(--v-theme-on-surface), 0.7);
    margin-bottom: 4px;
  }
  
  .feels-like {
    font-size: 14px;
    color: rgba(var(--v-theme-on-surface), 0.6);
  }
}

.weather-details {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  padding: 8px 0;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: rgba(var(--v-theme-on-surface), 0.8);
  
  .v-icon {
    color: rgba(var(--v-theme-on-surface), 0.6);
  }
}

.forecast {
  flex: 1;
  min-height: 0;
}

.forecast-days {
  display: flex;
  justify-content: space-between;
  gap: 8px;
}

.forecast-day {
  flex: 1;
  text-align: center;
  padding: 8px 4px;
  border-radius: 8px;
  background: rgba(var(--v-theme-on-surface), 0.04);
  transition: background 0.2s;
  
  &:hover {
    background: rgba(var(--v-theme-on-surface), 0.08);
  }
  
  .day-name {
    font-size: 12px;
    font-weight: 500;
    margin-bottom: 4px;
  }
  
  .day-temp {
    font-size: 14px;
    font-weight: 500;
    margin-top: 4px;
  }
  
  .day-temp-min {
    font-size: 12px;
    color: rgba(var(--v-theme-on-surface), 0.6);
  }
}

.last-update {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  padding-top: 8px;
  margin-top: auto;
  font-size: 11px;
  color: rgba(var(--v-theme-on-surface), 0.5);
}
</style>