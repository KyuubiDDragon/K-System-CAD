<template>
  <div class="desktop-widget" :class="{ 'widget-weather': type === 'weather', ['widget-weather-' + getWeatherType(weatherIcon)]: type === 'weather' }">
    <div class="widget-header" :style="{ borderColor: `${color}40` }">
      <div class="widget-icon-container" :style="{ background: type === 'weather' ? getWeatherColor(weatherIcon) : color }">
        <v-icon :icon="icon" color="white" size="18" class="widget-icon"></v-icon>
      </div>
      <span class="widget-title">{{ title }}</span>
      <div v-if="type === 'weather' && subLabel && subLabel !== 'Los Santos'" class="widget-label-badge">{{ subLabel }}</div>
    </div>
    <div class="widget-content">
      <slot>
        <div v-if="type === 'statistic'" class="statistic-content">
          <span class="statistic-value">{{ value }}</span>
          <span class="statistic-label">{{ label }}</span>
        </div>
        <div v-else-if="type === 'weather'" class="weather-content">
          <div class="weather-main">
            <v-icon 
              :icon="weatherIcon" 
              :size="weatherIcon === 'mdi-weather-alert' || weatherIcon === 'mdi-fire-alert' ? 40 : 48" 
              :color="getWeatherIconColor(weatherIcon)" 
              class="weather-icon"
              :class="{'warning-icon': weatherIcon === 'mdi-weather-alert' || weatherIcon === 'mdi-fire-alert'}"
            ></v-icon>
            <span class="weather-temp">{{ value }}°C</span>
          </div>
          <div 
            class="weather-info" 
            :class="{
              'weather-warning': isWeatherWarning(weatherIcon, label) && !label.toLowerCase().includes('wildfire'),
              'weather-wildfire': label.toLowerCase().includes('wildfire') || label.toLowerCase().includes('fire')
            }"
          >
            <div class="weather-condition">{{ label }}</div>
            <div class="weather-location">{{ subLabel }}</div>
          </div>
        </div>
        <div v-else-if="type === 'activity'" class="activity-content">
          <div v-if="activities && activities.length > 0" class="activity-list">
            <div v-for="(activity, index) in activities" :key="index" class="activity-item">
              <div class="activity-icon-container" :style="{ background: activity.color || color }">
                <v-icon :icon="activity.icon || 'mdi-bell'" size="14" color="white"></v-icon>
              </div>
              <div class="activity-details">
                <div class="activity-title">{{ activity.title }}</div>
                <div class="activity-time">{{ activity.time }}</div>
              </div>
            </div>
          </div>
          <div v-else class="activity-empty">
            {{ t('desktop.noActivities') }}
          </div>
        </div>
      </slot>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

interface Activity {
  title: string;
  time: string;
  icon?: string;
  color?: string;
}

interface Props {
  title?: string
  icon?: string
  color?: string
  label?: string
  subLabel?: string
  type?: string
  weatherIcon?: string
}

const props = withDefaults(defineProps<Props>(), {
  icon: 'mdi-chart-line',
  color: '#3b82f6',
  label: '',
  subLabel: '',
  type: 'statistic',
    validator: (value: string) => ['statistic', 'weather', 'activity', 'custom'].includes(value),
  weatherIcon: 'mdi-weather-partly-cloudy'
});

const { t } = useI18n();

// Function to determine weather type for CSS class
const getWeatherType = (icon: string): string => {
  if (!icon) return 'default';
  
  if (icon === 'mdi-fire-alert') return 'wildfire';
  if (icon === 'mdi-weather-alert') return 'warning';
  if (icon === 'mdi-weather-lightning' || icon === 'mdi-weather-lightning-rainy') return 'stormy';
  if (icon === 'mdi-weather-rainy' || icon === 'mdi-weather-snowy-rainy') return 'rainy';
  if (icon === 'mdi-weather-snowy') return 'snowy';
  if (icon === 'mdi-weather-sunny') return 'sunny';
  if (icon === 'mdi-weather-cloudy') return 'cloudy';
  if (icon === 'mdi-weather-fog') return 'foggy';
  if (icon === 'mdi-weather-windy') return 'windy';
  
  return 'default';
};

// Function to get weather-specific color
const getWeatherColor = (icon: string): string => {
  if (!icon) return '#3b82f6';
  
  const colorMap: { [key: string]: string } = {
    'mdi-fire-alert': '#ef4444', // Bright red for wildfire
    'mdi-weather-alert': '#dc2626', // Red for warnings
    'mdi-weather-lightning': '#9333ea', // Purple for lightning
    'mdi-weather-lightning-rainy': '#7e22ce', // Dark purple for lightning rain
    'mdi-weather-rainy': '#0ea5e9', // Sky blue for rain
    'mdi-weather-snowy-rainy': '#0284c7', // Blue for snow/rain mix
    'mdi-weather-snowy': '#38bdf8', // Light blue for snow
    'mdi-weather-sunny': '#f59e0b', // Amber for sun
    'mdi-weather-partly-cloudy': '#0ea5e9', // Sky blue for partly cloudy
    'mdi-weather-cloudy': '#6b7280', // Gray for clouds
    'mdi-weather-fog': '#94a3b8', // Gray-blue for fog
    'mdi-weather-windy': '#64748b', // Blue-gray for wind
  };
  
  return colorMap[icon] || '#3b82f6';
};

// Function for weather icon specific colors
const getWeatherIconColor = (icon: string): string => {
  if (icon === 'mdi-fire-alert') return '#fef2f2'; // White-red tint for wildfire icon
  if (icon === 'mdi-weather-alert') return '#fef2f2'; // White-red tint for warning icon
  return 'white'; // Default color
};

// Check if the weather condition is a warning
const isWeatherWarning = (icon: string, condition: string): boolean => {
  if (!icon || !condition) return false;
  
  if (icon === 'mdi-fire-alert') return true;
  if (icon === 'mdi-weather-alert') return true;
  
  // Check text-based warnings
  const warningText = condition.toLowerCase();
  return warningText.includes('wildfire') || 
         warningText.includes('warnung') || 
         warningText.includes('stark') || 
         (warningText !== 'keine warnungen' && warningText !== '-' && warningText.includes('stufe'));
};
</script>

<style scoped>
.desktop-widget {
  background-color: rgba(var(--desktop-bg-dark-1), calc(var(--glass-bg-opacity) + 0.05));
  border-radius: var(--border-radius-md);
  backdrop-filter: blur(calc(var(--glass-blur) + 3px));
  box-shadow: var(--shadow-medium);
  overflow: hidden;
  width: var(--desktop-widget-width);
  border: 1px solid rgba(255, 255, 255, 0.08);
  transition: all var(--animation-duration-normal) var(--animation-easing);
  animation: widget-appear 0.5s var(--animation-easing);
  position: relative;
}

.desktop-widget:hover {
  transform: var(--button-hover-translate);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3), 0 0 15px rgba(var(--primary-rgb), 0.15);
  border-color: rgba(255, 255, 255, 0.15);
}

/* Weather type specific styles */
.widget-weather-wildfire {
  border-color: rgba(239, 68, 68, 0.4);
}

.widget-weather-wildfire:hover {
  box-shadow: 0 10px 30px rgba(239, 68, 68, 0.3), 0 0 15px rgba(239, 68, 68, 0.25);
}

.widget-weather-warning {
  border-color: rgba(220, 38, 38, 0.3);
}

.widget-weather-warning:hover {
  box-shadow: 0 10px 30px rgba(220, 38, 38, 0.2), 0 0 15px rgba(220, 38, 38, 0.15);
}

.widget-weather-stormy {
  border-color: rgba(147, 51, 234, 0.3);
}

.widget-weather-stormy:hover {
  box-shadow: 0 10px 30px rgba(147, 51, 234, 0.2), 0 0 15px rgba(147, 51, 234, 0.15);
}

.widget-weather-rainy {
  border-color: rgba(14, 165, 233, 0.3);
}

.widget-weather-rainy:hover {
  box-shadow: 0 10px 30px rgba(14, 165, 233, 0.2), 0 0 15px rgba(14, 165, 233, 0.15);
}

.widget-weather-snowy {
  border-color: rgba(56, 189, 248, 0.3);
}

.widget-weather-snowy:hover {
  box-shadow: 0 10px 30px rgba(56, 189, 248, 0.2), 0 0 15px rgba(56, 189, 248, 0.15);
}

.widget-weather-sunny {
  border-color: rgba(245, 158, 11, 0.3);
}

.widget-weather-sunny:hover {
  box-shadow: 0 10px 30px rgba(245, 158, 11, 0.2), 0 0 15px rgba(245, 158, 11, 0.15);
}

.widget-weather:hover {
  box-shadow: 0 10px 30px rgba(14, 165, 233, 0.2), 0 0 15px rgba(14, 165, 233, 0.15);
}

.widget-header {
  display: flex;
  align-items: center;
  padding: 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  font-weight: 500;
  font-size: 14px;
  position: relative;
  overflow: hidden;
  height: 48px;
  background: rgba(var(--desktop-bg-dark-2), 0.4);
}

.widget-icon-container {
  height: 100%;
  width: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  transition: all var(--animation-duration-normal) var(--animation-easing);
}

.widget-icon-container::after {
  content: '';
  position: absolute;
  right: 0;
  top: 15%;
  height: 70%;
  width: 1px;
  background: rgba(255, 255, 255, 0.15);
}

.desktop-widget:hover .widget-icon-container {
  width: 55px;
}

.widget-icon {
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
  transform-origin: center;
  transition: transform 0.2s ease;
}

.desktop-widget:hover .widget-icon {
  transform: scale(1.15);
}

.widget-title {
  padding: 0 16px;
  letter-spacing: 0.5px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
}

.widget-label-badge {
  font-size: 11px;
  background-color: rgba(255, 255, 255, 0.1);
  padding: 2px 8px;
  border-radius: 10px;
  margin-right: 10px;
}

.widget-content {
  padding: 20px;
  min-height: 100px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  position: relative;
  overflow: hidden;
  background: linear-gradient(180deg, 
    rgba(var(--desktop-bg-dark-1), 0.4) 0%, 
    rgba(var(--desktop-bg-dark-2), 0.6) 100%
  );
}

.widget-content::before {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 50%;
  background: radial-gradient(
    circle at center bottom, 
    rgba(var(--primary-rgb), 0.05),
    transparent 70%
  );
  pointer-events: none;
}

.statistic-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  position: relative;
  z-index: 2;
}

.statistic-value {
  font-size: 38px;
  font-weight: 700;
  color: var(--desktop-text);
  margin-bottom: 10px;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
  position: relative;
  display: inline-block;
}

.statistic-value::after {
  content: '';
  position: absolute;
  width: 30%;
  height: 3px;
  background: var(--desktop-accent-blue);
  bottom: -5px;
  left: 35%;
  border-radius: 2px;
  opacity: 0.7;
}

.statistic-label {
  font-size: 15px;
  color: var(--desktop-text-secondary);
  font-weight: 500;
  margin-top: 5px;
}

.weather-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  z-index: 2;
}

.weather-main {
  display: flex;
  align-items: center;
  margin-bottom: 12px;
  justify-content: center;
  width: 100%;
  position: relative;
}

.weather-icon {
  filter: drop-shadow(0 3px 8px rgba(0, 0, 0, 0.3));
  transform-origin: center;
  transition: transform 0.3s ease;
  animation: weather-icon-float 3s ease-in-out infinite;
}

.desktop-widget:hover .weather-icon {
  transform: scale(1.1);
  animation-play-state: paused;
}

.weather-temp {
  font-size: 32px;
  font-weight: 700;
  color: var(--desktop-text);
  margin-left: 15px;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.weather-info {
  font-size: 14px;
  color: var(--desktop-text-secondary);
  text-align: center;
  background-color: rgba(0, 0, 0, 0.15);
  padding: 8px 12px;
  border-radius: var(--border-radius-sm);
  width: 100%;
  margin-top: 5px;
  backdrop-filter: blur(2px);
  transition: all 0.3s ease;
}

.weather-warning {
  background-color: rgba(220, 38, 38, 0.2);
  border-left: 3px solid #dc2626;
  animation: weather-warning-pulse 2s ease-in-out infinite;
}

.weather-wildfire {
  background-color: rgba(239, 68, 68, 0.25);
  border-left: 3px solid #ef4444;
  animation: weather-wildfire-pulse 1.5s ease-in-out infinite;
}

.warning-icon {
  animation: warning-icon-pulse 1.5s ease-in-out infinite;
}

.weather-condition {
  font-weight: 600;
  margin-bottom: 2px;
}

.weather-location {
  font-size: 13px;
}

@keyframes widget-appear {
  from {
    opacity: 0;
    transform: translateY(15px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes weather-icon-float {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-5px);
  }
}

@keyframes weather-warning-pulse {
  0%, 100% {
    background-color: rgba(220, 38, 38, 0.2);
  }
  50% {
    background-color: rgba(220, 38, 38, 0.3);
  }
}

@keyframes weather-wildfire-pulse {
  0%, 100% {
    background-color: rgba(239, 68, 68, 0.25);
  }
  50% {
    background-color: rgba(239, 68, 68, 0.35);
  }
}

@keyframes warning-icon-pulse {
  0%, 100% {
    opacity: 0.9;
    transform: scale(1);
  }
  50% {
    opacity: 1;
    transform: scale(1.05);
  }
}

.activity-content {
  display: flex;
  flex-direction: column;
  width: 100%;
  position: relative;
  z-index: 2;
}

.activity-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  width: 100%;
}

.activity-item {
  display: flex;
  align-items: center;
  padding: 5px;
  border-radius: var(--border-radius-sm);
  background-color: rgba(255, 255, 255, 0.05);
  transition: all var(--animation-duration-fast) var(--animation-easing);
}

.activity-item:hover {
  background-color: rgba(255, 255, 255, 0.1);
  transform: translateX(3px);
}

.activity-icon-container {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 10px;
  flex-shrink: 0;
}

.activity-details {
  flex: 1;
  min-width: 0;
}

.activity-title {
  font-size: 13px;
  font-weight: 500;
  color: var(--desktop-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.activity-time {
  font-size: 11px;
  color: var(--desktop-text-secondary);
}

.activity-empty {
  color: var(--desktop-text-muted);
  font-size: 13px;
  font-style: italic;
  text-align: center;
  padding: 20px 0;
}
</style> 