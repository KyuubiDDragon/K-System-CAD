/**
 * Widget Registry Composable
 * Central registry for all available dashboard widgets with their configurations
 */

import type { Component } from 'vue'
import { useModulePermission } from '@/composables/useModulePermission'
import { usePermissionCheck } from '@/composables/usePermissionCheck'

export interface WidgetSize {
  w: number  // width in grid units (12-column grid)
  h: number  // height in grid units
}

export interface WidgetDefinition {
  key: string
  name: string
  description: string
  icon: string
  category: 'personal' | 'communication' | 'information' | 'analytics' | 'operational' | 'administration'
  component: () => Promise<Component>
  requiredPermissions: string[]
  minSize: WidgetSize
  defaultSize: WidgetSize
  maxSize?: WidgetSize
  configSchema?: any  // JSON Schema for widget settings (future use)
  realtime: boolean
  configurable: boolean
}

export const WIDGET_REGISTRY: Record<string, WidgetDefinition> = {
  // ==================== PERSONAL WIDGETS ====================

  'welcome-banner': {
    key: 'welcome-banner',
    name: 'dashboard.widget.names.welcome-banner',
    description: 'dashboard.widget.descriptions.welcome-banner',
    icon: 'mdi-home',
    category: 'personal',
    component: () => import('@/components/dashboard/widgets/WelcomeBannerWidget.vue'),
    requiredPermissions: ['CAN_LOGIN'],
    minSize: { w: 6, h: 3 },
    defaultSize: { w: 12, h: 3 },
    realtime: false,
    configurable: true,
    configSchema: {
      type: 'object',
      properties: {
        showAvatar: { type: 'boolean', default: true, label: 'Show Avatar' },
        showStats: { type: 'boolean', default: true, label: 'Show Quick Stats' }
      }
    }
  },

  'my-vacations': {
    key: 'my-vacations',
    name: 'dashboard.widget.names.my-vacations',
    description: 'dashboard.widget.descriptions.my-vacations',
    icon: 'mdi-beach',
    category: 'personal',
    component: () => import('@/components/dashboard/widgets/MyVacationsWidget.vue'),
    requiredPermissions: ['CAN_LOGIN'],
    minSize: { w: 4, h: 5 },
    defaultSize: { w: 6, h: 6 },
    realtime: true,
    configurable: true,
    configSchema: {
      type: 'object',
      properties: {
        showHistory: { type: 'boolean', default: false, label: 'Show Past Vacations' },
        maxItems: { type: 'number', default: 5, min: 1, max: 20, label: 'Maximum Items' }
      }
    }
  },

  'todo-list': {
    key: 'todo-list',
    name: 'dashboard.widget.names.todo-list',
    description: 'dashboard.widget.descriptions.todo-list',
    icon: 'mdi-checkbox-marked-circle-outline',
    category: 'personal',
    component: () => import('@/components/dashboard/widgets/TodoListWidget.vue'),
    requiredPermissions: ['READ_TODO'],
    minSize: { w: 4, h: 5 },
    defaultSize: { w: 6, h: 6 },
    realtime: true,
    configurable: true,
    configSchema: {
      type: 'object',
      properties: {
        showCompleted: { type: 'boolean', default: false, label: 'Show Completed Tasks' },
        maxItems: { type: 'number', default: 10, min: 5, max: 50, label: 'Maximum Items' },
        sortBy: { type: 'string', enum: ['date', 'priority', 'alphabet'], default: 'date', label: 'Sort By' }
      }
    }
  },

  // ==================== COMMUNICATION WIDGETS ====================

  'messages-feed': {
    key: 'messages-feed',
    name: 'dashboard.widget.names.messages-feed',
    description: 'dashboard.widget.descriptions.messages-feed',
    icon: 'mdi-message-text',
    category: 'communication',
    component: () => import('@/components/dashboard/widgets/MessagesFeedWidget.vue'),
    requiredPermissions: ['CAN_LOGIN'],
    minSize: { w: 4, h: 5 },
    defaultSize: { w: 6, h: 6 },
    realtime: true,
    configurable: true,
    configSchema: {
      type: 'object',
      properties: {
        maxItems: { type: 'number', default: 10, min: 5, max: 50, label: 'Maximum Messages' },
        unreadOnly: { type: 'boolean', default: false, label: 'Unread Only' }
      }
    }
  },

  'blackboard-feed': {
    key: 'blackboard-feed',
    name: 'dashboard.widget.names.blackboard-feed',
    description: 'dashboard.widget.descriptions.blackboard-feed',
    icon: 'mdi-bulletin-board',
    category: 'communication',
    component: () => import('@/components/dashboard/widgets/BlackboardFeedWidget.vue'),
    requiredPermissions: ['READ_BLACKBOARD_AREA'],
    minSize: { w: 4, h: 5 },
    defaultSize: { w: 8, h: 5 },
    realtime: true,
    configurable: true,
    configSchema: {
      type: 'object',
      properties: {
        boardType: {
          type: 'string',
          enum: ['all', 'global'],
          enumLabels: ['dashboard.boardTypes.all', 'dashboard.boardTypes.global'],
          default: 'all',
          label: 'Board Type',
          description: '"all" = all areas (except global), "global" = cross-authority only'
        },
        area_id: {
          type: 'number',
          min: 1,
          label: 'Area ID (Optional)',
          description: 'Specific blackboard area ID. If set, Board Type will be ignored.'
        },
        maxItems: { type: 'number', default: 5, min: 1, max: 20, label: 'Maximum Items' },
        pinnedOnly: { type: 'boolean', default: false, label: 'Pinned Only' }
      }
    }
  },

  // ==================== INFORMATION WIDGETS ====================

  'calendar-widget': {
    key: 'calendar-widget',
    name: 'dashboard.widget.names.calendar-widget',
    description: 'dashboard.widget.descriptions.calendar-widget',
    icon: 'mdi-calendar',
    category: 'information',
    component: () => import('@/components/dashboard/widgets/CalendarWidget.vue'),
    requiredPermissions: ['READ_CALENDAR'],
    minSize: { w: 4, h: 5 },
    defaultSize: { w: 6, h: 6 },
    realtime: true,
    configurable: true,
    configSchema: {
      type: 'object',
      properties: {
        daysAhead: { type: 'number', default: 7, min: 1, max: 90, label: 'Days Ahead' },
        viewMode: { type: 'string', enum: ['list', 'calendar'], default: 'list', label: 'View Mode' }
      }
    }
  },

  'weather-widget': {
    key: 'weather-widget',
    name: 'dashboard.widget.names.weather-widget',
    description: 'dashboard.widget.descriptions.weather-widget',
    icon: 'mdi-weather-partly-cloudy',
    category: 'information',
    component: () => import('@/components/dashboard/widgets/WeatherWidget.vue'),
    requiredPermissions: ['CAN_LOGIN'],
    minSize: { w: 3, h: 4 },
    defaultSize: { w: 4, h: 5 },
    realtime: false,
    configurable: true,
    configSchema: {
      type: 'object',
      properties: {
        showForecast: { type: 'boolean', default: true, label: 'Show 7-Day Forecast' }
      }
    }
  },

  'active-users': {
    key: 'active-users',
    name: 'dashboard.widget.names.active-users',
    description: 'dashboard.widget.descriptions.active-users',
    icon: 'mdi-account-multiple',
    category: 'information',
    component: () => import('@/components/dashboard/widgets/ActiveUsersWidget.vue'),
    requiredPermissions: ['READ_EMPLOYEE'],
    minSize: { w: 3, h: 4 },
    defaultSize: { w: 4, h: 5 },
    realtime: true,
    configurable: false
  },

  'recent-documents': {
    key: 'recent-documents',
    name: 'dashboard.widget.names.recent-documents',
    description: 'dashboard.widget.descriptions.recent-documents',
    icon: 'mdi-file-document-multiple',
    category: 'information',
    component: () => import('@/components/dashboard/widgets/RecentDocumentsWidget.vue'),
    requiredPermissions: ['READ_DOCUMENT'],
    minSize: { w: 4, h: 4 },
    defaultSize: { w: 6, h: 5 },
    realtime: true,
    configurable: true
  },

  // ==================== ANALYTICS WIDGETS ====================

  'employee-overview': {
    key: 'employee-overview',
    name: 'dashboard.widget.names.employee-overview',
    description: 'dashboard.widget.descriptions.employee-overview',
    icon: 'mdi-account-group',
    category: 'analytics',
    component: () => import('@/components/dashboard/widgets/EmployeeOverviewWidget.vue'),
    requiredPermissions: ['READ_EMPLOYEE'],
    minSize: { w: 4, h: 5 },
    defaultSize: { w: 6, h: 6 },
    realtime: true,
    configurable: true
  },

  'report-analytics': {
    key: 'report-analytics',
    name: 'dashboard.widget.names.report-analytics',
    description: 'dashboard.widget.descriptions.report-analytics',
    icon: 'mdi-chart-line',
    category: 'analytics',
    component: () => import('@/components/dashboard/widgets/ReportAnalyticsWidget.vue'),
    requiredPermissions: ['READ_REPORT'],
    minSize: { w: 6, h: 4 },
    defaultSize: { w: 8, h: 5 },
    realtime: true,
    configurable: true,
    configSchema: {
      type: 'object',
      properties: {
        period: { type: 'number', default: 30, enum: [7, 30, 90, 365], label: 'Time Period (days)' },
        chartType: { type: 'string', enum: ['bar', 'line', 'donut', 'pie'], default: 'bar', label: 'Chart Type' },
        groupBy: { type: 'string', enum: ['status', 'category', 'priority'], default: 'status', label: 'Group By' }
      }
    }
  },

  'open-reports': {
    key: 'open-reports',
    name: 'dashboard.widget.names.open-reports',
    description: 'dashboard.widget.descriptions.open-reports',
    icon: 'mdi-file-document-alert',
    category: 'analytics',
    component: () => import('@/components/dashboard/widgets/OpenReportsWidget.vue'),
    requiredPermissions: ['READ_REPORT'],
    minSize: { w: 3, h: 4 },
    defaultSize: { w: 4, h: 5 },
    realtime: true,
    configurable: true
  },

  'vacation-calendar': {
    key: 'vacation-calendar',
    name: 'dashboard.widget.names.vacation-calendar',
    description: 'dashboard.widget.descriptions.vacation-calendar',
    icon: 'mdi-calendar-account',
    category: 'analytics',
    component: () => import('@/components/dashboard/widgets/VacationCalendarWidget.vue'),
    requiredPermissions: ['READ_EMPLOYEE'],
    minSize: { w: 6, h: 5 },
    defaultSize: { w: 6, h: 6 },
    realtime: true,
    configurable: true
  },

  // ==================== OPERATIONAL WIDGETS ====================

  'quick-dispatch': {
    key: 'quick-dispatch',
    name: 'dashboard.widget.names.quick-dispatch',
    description: 'dashboard.widget.descriptions.quick-dispatch',
    icon: 'mdi-truck-fast',
    category: 'operational',
    component: () => import('@/components/dashboard/widgets/QuickDispatchWidget.vue'),
    requiredPermissions: ['READ_DISPATCH'],
    minSize: { w: 8, h: 3 },
    defaultSize: { w: 12, h: 4 },
    realtime: true,
    configurable: true
  },

  'my-crew': {
    key: 'my-crew',
    name: 'dashboard.widget.names.my-crew',
    description: 'dashboard.widget.descriptions.my-crew',
    icon: 'mdi-shield-account',
    category: 'operational',
    component: () => import('@/components/dashboard/widgets/MyCrewWidget.vue'),
    requiredPermissions: ['CAN_LOGIN'],
    minSize: { w: 4, h: 5 },
    defaultSize: { w: 6, h: 6 },
    realtime: true,
    configurable: false
  },

  'vehicle-status': {
    key: 'vehicle-status',
    name: 'dashboard.widget.names.vehicle-status',
    description: 'dashboard.widget.descriptions.vehicle-status',
    icon: 'mdi-car-multiple',
    category: 'operational',
    component: () => import('@/components/dashboard/widgets/VehicleStatusWidget.vue'),
    requiredPermissions: ['READ_VEHICLE'],
    minSize: { w: 4, h: 4 },
    defaultSize: { w: 4, h: 5 },
    realtime: true,
    configurable: true
  },

  // ==================== ADMINISTRATION WIDGETS ====================

  'system-health': {
    key: 'system-health',
    name: 'dashboard.widget.names.system-health',
    description: 'dashboard.widget.descriptions.system-health',
    icon: 'mdi-heart-pulse',
    category: 'administration',
    component: () => import('@/components/dashboard/widgets/SystemHealthWidget.vue'),
    requiredPermissions: ['SYSTEM_ADMIN'],
    minSize: { w: 8, h: 3 },
    defaultSize: { w: 12, h: 4 },
    realtime: true,
    configurable: true
  },

  'user-activity': {
    key: 'user-activity',
    name: 'dashboard.widget.names.user-activity',
    description: 'dashboard.widget.descriptions.user-activity',
    icon: 'mdi-account-clock',
    category: 'administration',
    component: () => import('@/components/dashboard/widgets/UserActivityWidget.vue'),
    requiredPermissions: ['ADMIN_READ_USERS'],
    minSize: { w: 4, h: 5 },
    defaultSize: { w: 6, h: 6 },
    realtime: true,
    configurable: true
  },

  'recent-logs': {
    key: 'recent-logs',
    name: 'dashboard.widget.names.recent-logs',
    description: 'dashboard.widget.descriptions.recent-logs',
    icon: 'mdi-text-box-multiple',
    category: 'administration',
    component: () => import('@/components/dashboard/widgets/RecentLogsWidget.vue'),
    requiredPermissions: ['ADMIN_READ_USERS'],
    minSize: { w: 6, h: 5 },
    defaultSize: { w: 6, h: 6 },
    realtime: true,
    configurable: true
  }
}

/**
 * Get widget definition by key
 */
export function getWidgetDefinition(key: string): WidgetDefinition | undefined {
  return WIDGET_REGISTRY[key]
}

/**
 * Get all widget definitions
 */
export function getAllWidgetDefinitions(): WidgetDefinition[] {
  return Object.values(WIDGET_REGISTRY)
}

/**
 * Get widgets by category
 */
export function getWidgetsByCategory(category: string): WidgetDefinition[] {
  return Object.values(WIDGET_REGISTRY).filter(w => w.category === category)
}

/**
 * Get widgets available to current user based on their permissions
 * Uses the new permission system that works with both legacy and bitmask formats
 */
export function getAvailableWidgets(): WidgetDefinition[] {
  const { hasAllPermissions } = useModulePermission()
  const { hasPermission } = usePermissionCheck()

  return Object.values(WIDGET_REGISTRY).filter(widget => {
    // Check if user has ALL_PERMISSIONS (super admin)
    if (hasAllPermissions.value) {
      return true
    }

    // Check if user has all required permissions using the new permission check
    // This works with both legacy string permissions and bitmask format
    return widget.requiredPermissions.every(perm =>
      hasPermission(perm)
    )
  })
}

/**
 * Get widget categories
 */
export function getWidgetCategories(): Array<{ key: string; label: string; icon: string }> {
  return [
    { key: 'personal', label: 'Personal', icon: 'mdi-account' },
    { key: 'communication', label: 'Communication', icon: 'mdi-message' },
    { key: 'information', label: 'Information', icon: 'mdi-information' },
    { key: 'analytics', label: 'Analytics', icon: 'mdi-chart-line' },
    { key: 'operational', label: 'Operational', icon: 'mdi-cog' },
    { key: 'administration', label: 'Administration', icon: 'mdi-shield-crown' }
  ]
}

/**
 * Composable to use widget registry
 */
export function useWidgetRegistry() {
  return {
    WIDGET_REGISTRY,
    getWidgetDefinition,
    getAllWidgetDefinitions,
    getWidgetsByCategory,
    getAvailableWidgets,
    getWidgetCategories
  }
}
