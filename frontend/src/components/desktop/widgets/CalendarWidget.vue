<template>
  <widget-container
    :id="widgetId"
    :title="$t('widgets.calendar.title')"
    icon="mdi-calendar"
    color="purple"
    :position="position"
    :min-width="300"
    :min-height="350"
    :max-width="() => Math.min(500, window.innerWidth - 120)"
    :max-height="600"
    @update:position="$emit('update:position', $event)"
    @close="$emit('close')"
    @focus="$emit('focus')"
  >
    <div class="calendar-widget">
      <!-- Loading State -->
      <div v-if="isLoading" class="text-center py-8">
        <v-progress-circular indeterminate color="primary" />
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-4">
        <v-icon size="48" color="error">mdi-calendar-alert</v-icon>
        <p class="text-error mt-2">{{ error }}</p>
        <v-btn size="small" @click="fetchEvents" class="mt-2">
          {{ $t('common.retry') }}
        </v-btn>
      </div>

      <!-- Calendar Content -->
      <div v-else class="calendar-content">
        <!-- Today's Date -->
        <div class="today-header">
          <h3 class="today-date">{{ formatTodayDate() }}</h3>
          <v-chip size="small" color="primary" variant="tonal">
            {{ $t('widgets.calendar.today') }}
          </v-chip>
        </div>

        <v-divider class="my-3" />

        <!-- Events List -->
        <div class="events-container">
          <div v-if="todayEvents.length === 0" class="no-events">
            <v-icon size="48" color="grey">mdi-calendar-blank</v-icon>
            <p class="text-grey mt-2">{{ $t('widgets.calendar.noEvents') }}</p>
          </div>

          <div v-else class="events-list">
            <!-- Show first 5 events -->
            <div 
              v-for="event in displayedEvents" 
              :key="event.id"
              class="event-item"
              :class="{ 'all-day': event.allDay }"
            >
              <div class="event-time">
                <v-icon 
                  v-if="event.allDay" 
                  size="16" 
                  color="primary"
                >
                  mdi-calendar-blank
                </v-icon>
                <span v-else class="time-text">
                  {{ formatEventTime(event.start) }}
                </span>
              </div>
              
              <div class="event-details">
                <h4 class="event-title">{{ event.title }}</h4>
                <p v-if="event.location" class="event-location">
                  <v-icon size="12">mdi-map-marker</v-icon>
                  {{ event.location }}
                </p>
              </div>

              <div 
                class="event-color" 
                :style="{ backgroundColor: event.color || '#3b82f6' }"
              />
            </div>

            <!-- Show more button -->
            <div v-if="todayEvents.length > 5" class="view-more">
              <v-btn 
                size="small" 
                variant="text" 
                color="primary"
                @click="viewFullCalendar"
              >
                {{ $t('widgets.calendar.viewMore', { count: todayEvents.length - 5 }) }}
              </v-btn>
            </div>
          </div>
        </div>

        <!-- Calendar Action -->
        <div class="calendar-action">
          <v-btn 
            block 
            color="primary" 
            variant="tonal"
            prepend-icon="mdi-calendar"
            @click="viewFullCalendar"
          >
            {{ $t('widgets.calendar.viewCalendar') }}
          </v-btn>
        </div>
      </div>
    </div>
  </widget-container>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import WidgetContainer from './WidgetContainer.vue';
import { apiClientAuth } from '@/api';

interface CalendarEvent {
  id: number;
  title: string;
  start: string;
  end: string;
  allDay: boolean;
  location?: string;
  color?: string;
  description?: string;
}

interface Props {
  position?: { x: number; y: number; width: number; height: number };
}

const props = withDefaults(defineProps<Props>(), {
  position: () => ({ x: 20, y: 120, width: 350, height: 450 })
});

const emit = defineEmits<{
  'update:position': [position: any];
  'close': [];
  'focus': [];
}>();

const { t, locale } = useI18n();
const router = useRouter();

// State
const widgetId = `calendar-widget-${Date.now()}`;
const isLoading = ref(true);
const error = ref<string | null>(null);
const todayEvents = ref<CalendarEvent[]>([]);

// Auto-refresh timer
let refreshInterval: number | null = null;

// Computed
const displayedEvents = computed(() => {
  return todayEvents.value.slice(0, 5);
});

// Methods
const formatTodayDate = (): string => {
  const today = new Date();
  return today.toLocaleDateString(locale.value, { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  });
};

const formatEventTime = (dateStr: string): string => {
  const date = new Date(dateStr);
  return date.toLocaleTimeString(locale.value, { 
    hour: '2-digit', 
    minute: '2-digit' 
  });
};

const fetchEvents = async () => {
  isLoading.value = true;
  error.value = null;
  
  try {
    // Fetch today's events from K-Systems backend
    const response = await apiClientAuth.get('/calendar/', {
      params: { action: 'getEventsToday' }
    });
    
    if (response.data && Array.isArray(response.data)) {
      // Process K-Systems events
      todayEvents.value = response.data.map(event => ({
        id: event.id,
        title: event.title || event.name || 'Unnamed Event',
        start: event.start || event.datetime || event.date,
        end: event.end || event.datetime || event.date,
        allDay: event.allDay || event.all_day || false,
        location: event.location || '',
        color: event.color || '#3b82f6',
        description: event.description || ''
      }));
      
      // Sort events by start time
      todayEvents.value.sort((a, b) => {
        // All-day events first
        if (a.allDay && !b.allDay) return -1;
        if (!a.allDay && b.allDay) return 1;
        
        // Then by start time
        return new Date(a.start).getTime() - new Date(b.start).getTime();
      });
    } else {
      // No events today
      todayEvents.value = [];
    }
  } catch (err) {
    console.error('Calendar fetch error:', err);
    error.value = t('widgets.calendar.fetchError');
    
    // Use mock data as fallback
    todayEvents.value = [
      {
        id: 1,
        title: 'Team Meeting',
        start: new Date().toISOString(),
        end: new Date(Date.now() + 3600000).toISOString(),
        allDay: false,
        location: 'Conference Room A',
        color: '#3b82f6'
      },
      {
        id: 2,
        title: 'Training Session',
        start: new Date(Date.now() + 7200000).toISOString(),
        end: new Date(Date.now() + 10800000).toISOString(),
        allDay: false,
        location: 'Training Center',
        color: '#10b981'
      },
      {
        id: 3,
        title: 'Inspection Day',
        start: new Date().toISOString(),
        end: new Date().toISOString(),
        allDay: true,
        color: '#f59e0b'
      }
    ];
  } finally {
    isLoading.value = false;
  }
};

const viewFullCalendar = () => {
  // Check if we're in desktop mode
  if (document.documentElement.classList.contains('desktop-mode')) {
    // Emit event to open calendar app in desktop
    window.dispatchEvent(new CustomEvent('open-desktop-app', {
      detail: {
        id: 'calendar',
        title: t('tabs.calendar'),
        icon: 'mdi-calendar',
        route: '/calendar',
        color: '#8b5cf6'
      }
    }));
  } else {
    // Normal routing
    router.push('/calendar');
  }
};

// Lifecycle
onMounted(() => {
  fetchEvents();
  
  // Refresh every 5 minutes
  refreshInterval = window.setInterval(() => {
    fetchEvents();
  }, 300000);
});

onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval);
  }
});
</script>

<style scoped lang="scss">
.calendar-widget {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.calendar-content {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.today-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.today-date {
  font-size: 18px;
  font-weight: 500;
  color: var(--v-theme-on-surface);
}

.events-container {
  flex: 1;
  overflow-y: auto;
  min-height: 0;
}

.no-events {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  text-align: center;
  padding: 32px;
}

.events-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.event-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px;
  border-radius: 8px;
  background: rgba(var(--v-theme-on-surface), 0.04);
  transition: all 0.2s;
  position: relative;
  overflow: hidden;
  
  &:hover {
    background: rgba(var(--v-theme-on-surface), 0.08);
    transform: translateX(4px);
  }
  
  &.all-day {
    background: rgba(var(--v-theme-primary), 0.08);
  }
}

.event-time {
  flex-shrink: 0;
  width: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  
  .time-text {
    font-size: 13px;
    font-weight: 500;
    color: rgba(var(--v-theme-on-surface), 0.7);
  }
}

.event-details {
  flex: 1;
  min-width: 0;
}

.event-title {
  font-size: 14px;
  font-weight: 500;
  margin-bottom: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.event-location {
  font-size: 12px;
  color: rgba(var(--v-theme-on-surface), 0.6);
  display: flex;
  align-items: center;
  gap: 4px;
  
  .v-icon {
    opacity: 0.6;
  }
}

.event-color {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 3px;
}

.view-more {
  margin-top: 8px;
  text-align: center;
}

.calendar-action {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}

// Scrollbar styling
.events-container {
  &::-webkit-scrollbar {
    width: 4px;
  }
  
  &::-webkit-scrollbar-track {
    background: transparent;
  }
  
  &::-webkit-scrollbar-thumb {
    background: rgba(var(--v-theme-on-surface), 0.2);
    border-radius: 2px;
    
    &:hover {
      background: rgba(var(--v-theme-on-surface), 0.3);
    }
  }
}
</style>