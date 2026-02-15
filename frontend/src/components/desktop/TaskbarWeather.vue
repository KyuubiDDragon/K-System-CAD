<!-- Taskbar Weather Component -->
<template>
  <div class="taskbar-weather" @click="$emit('click')">
    <div class="weather-icon-container">
      <v-icon :icon="weatherIcon" 
              :color="getWeatherIconColor(weatherIcon)" 
              size="20" 
              class="weather-icon"></v-icon>
    </div>
    <div v-if="!compact" class="weather-info">
      <div class="weather-temp">{{ temperature }}°C</div>
      <div class="weather-condition">{{ condition }}</div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';

export default defineComponent({
  name: 'TaskbarWeather',
  props: {
    temperature: {
      type: [String, Number],
      default: '22'
    },
    condition: {
      type: String,
      default: 'Teilweise bewölkt'
    },
    location: {
      type: String,
      default: 'Los Santos'
    },
    weatherIcon: {
      type: String,
      default: 'mdi-weather-partly-cloudy'
    },
    compact: {
      type: Boolean,
      default: false
    }
  },
  emits: ['click'],
  setup() {
    // Function for weather icon specific colors
    const getWeatherIconColor = (icon: string): string => {
      if (!icon) return 'white';

      if (icon === 'mdi-fire-alert') return '#fef2f2'; // White-red tint for wildfire icon
      if (icon === 'mdi-weather-alert') return '#fef2f2'; // White-red tint for warning icon
      
      const colorMap: { [key: string]: string } = {
        'mdi-weather-lightning': '#d8b4fe', // Light purple for lightning
        'mdi-weather-lightning-rainy': '#d8b4fe', // Light purple for lightning rain
        'mdi-weather-rainy': '#bae6fd', // Light blue for rain
        'mdi-weather-snowy-rainy': '#bae6fd', // Light blue for snow/rain mix
        'mdi-weather-snowy': '#e0f2fe', // Very light blue for snow
        'mdi-weather-sunny': '#fde68a', // Light yellow for sun
        'mdi-weather-partly-cloudy': '#e0f2fe', // Light blue for partly cloudy
        'mdi-weather-cloudy': '#e2e8f0', // Light gray for clouds
        'mdi-weather-fog': '#f1f5f9', // Very light gray for fog
        'mdi-weather-windy': '#e2e8f0', // Light gray for wind
      };
      
      return colorMap[icon] || 'white';
    };

    return {
      getWeatherIconColor
    };
  }
});
</script>

<style scoped>
.taskbar-weather {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  height: 40px;
  padding: 0 10px;
  border-radius: var(--border-radius-sm);
  transition: all var(--animation-duration-fast) var(--animation-easing);
  color: var(--desktop-text-secondary);
}

.taskbar-weather:hover {
  background-color: rgba(var(--desktop-bg-dark-2), 0.3);
  transform: var(--button-hover-translate);
  color: var(--desktop-text);
}

.weather-icon-container {
  display: flex;
  align-items: center;
  justify-content: center;
}

.weather-icon {
  filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.2));
}

.weather-info {
  display: flex;
  flex-direction: column;
  line-height: 1.1;
  min-width: 80px;
}

.weather-temp {
  font-size: 14px;
  font-weight: 500;
  color: var(--desktop-text);
}

.weather-condition {
  font-size: 11px;
  color: var(--desktop-text-tertiary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100px;
}

.taskbar-weather:hover::after {
  content: 'Wetter-App öffnen';
  position: absolute;
  top: -30px;
  right: 0;
  background-color: rgba(var(--desktop-bg-dark-1), 0.9);
  padding: 4px 8px;
  border-radius: var(--border-radius-sm);
  font-size: 12px;
  white-space: nowrap;
  box-shadow: var(--shadow-small);
}

/* Responsive styles */
@media (max-width: 768px) {
  .weather-info {
    display: none;
  }
  
  .taskbar-weather {
    padding: 0 8px;
    justify-content: center;
    width: 36px;
  }
}
</style> 