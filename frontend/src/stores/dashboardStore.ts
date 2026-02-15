/**
 * Dashboard Store
 * Manages dashboard layouts, widgets, and edit mode
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Ref } from 'vue'
import { apiClientAuth } from '@/api'

export interface WidgetLayout {
  i: string  // widget ID
  x: number  // x position
  y: number  // y position
  w: number  // width in grid units
  h: number  // height in grid units
  minW?: number
  minH?: number
  maxW?: number
  maxH?: number
  static?: boolean  // if true, widget cannot be moved/resized
}

export interface WidgetConfig {
  type: string
  title: string
  [key: string]: any  // additional widget-specific config
}

export interface WidgetData {
  [key: string]: any  // runtime data for widget
}

export interface DashboardTemplate {
  key: string
  name: string
  description: string
  icon: string
  category: string
  layout: WidgetLayout[]
  widgets: Record<string, WidgetConfig>
}

export interface AvailableWidget {
  key: string
  name: string
  description: string
  icon: string
  category: string
  minSize: { w: number; h: number }
  defaultSize: { w: number; h: number }
  realtime: boolean
  requiredPermissions: string[]
}

export const useDashboardStore = defineStore('dashboard', () => {
  // State
  const layout: Ref<WidgetLayout[]> = ref([])
  const widgets: Ref<Record<string, WidgetConfig>> = ref({})
  const widgetData: Ref<Record<string, WidgetData>> = ref({})
  const currentTemplate: Ref<string> = ref('custom')
  const isCustom: Ref<boolean> = ref(false)
  const editMode: Ref<boolean> = ref(false)
  const loading: Ref<boolean> = ref(false)
  const saving: Ref<boolean> = ref(false)

  // Available widgets and templates
  const availableWidgets: Ref<AvailableWidget[]> = ref([])
  const availableTemplates: Ref<DashboardTemplate[]> = ref([])

  // Computed
  const hasLayout = computed(() => layout.value.length > 0)
  const widgetCount = computed(() => layout.value.length)
  const isEditing = computed(() => editMode.value)

  /**
   * Load dashboard layout from backend
   */
  async function loadLayout() {
    loading.value = true
    try {
      const response = await apiClientAuth.get('/dashboard/?action=getLayout')

      if (response.data.success) {
        layout.value = response.data.layout || []
        widgets.value = response.data.widgets || {}
        currentTemplate.value = response.data.template || 'custom'
        isCustom.value = response.data.isCustom || false
      }
    } catch (error) {
      console.error('Failed to load dashboard layout:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  /**
   * Save current layout to backend
   */
  async function saveLayout() {
    saving.value = true
    try {
      const response = await apiClientAuth.post('/dashboard/?action=saveLayout', {
        layout: layout.value,
        widgets: widgets.value,
        template: currentTemplate.value
      })

      if (response.data.success) {
        isCustom.value = true
        return response.data
      }
    } catch (error) {
      console.error('Failed to save dashboard layout:', error)
      throw error
    } finally {
      saving.value = false
    }
  }

  /**
   * Export dashboard layout
   */
  async function exportLayout() {
    try {
      const response = await apiClientAuth.get('/dashboard/?action=exportLayout')

      if (response.data.success) {
        const exportData = response.data.exportData

        // Create JSON file and trigger download
        const blob = new Blob([JSON.stringify(exportData, null, 2)], {
          type: 'application/json'
        })
        const url = URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = `dashboard-layout-${Date.now()}.json`
        link.click()
        URL.revokeObjectURL(url)

        return response.data.exportHash
      }
    } catch (error) {
      console.error('Failed to export dashboard layout:', error)
      throw error
    }
  }

  /**
   * Import dashboard layout from file or hash
   */
  async function importLayout(importData: any) {
    try {
      const response = await apiClientAuth.post('/dashboard/?action=importLayout', importData)

      if (response.data.success) {
        // Reload layout after import
        await loadLayout()
        return true
      }
    } catch (error) {
      console.error('Failed to import dashboard layout:', error)
      throw error
    }
  }

  /**
   * Load available templates
   */
  async function loadTemplates() {
    try {
      const response = await apiClientAuth.get('/dashboard/?action=getTemplates')

      if (response.data.success) {
        availableTemplates.value = response.data.templates || []
      }
    } catch (error) {
      console.error('Failed to load templates:', error)
      throw error
    }
  }

  /**
   * Apply a template to dashboard
   */
  async function applyTemplate(templateKey: string) {
    loading.value = true
    try {
      const response = await apiClientAuth.post('/dashboard/?action=applyTemplate', {
        templateKey
      })

      if (response.data.success) {
        layout.value = response.data.layout || []
        widgets.value = response.data.widgets || {}
        currentTemplate.value = templateKey
        isCustom.value = false
      }
    } catch (error) {
      console.error('Failed to apply template:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  /**
   * Load available widgets
   */
  async function loadAvailableWidgets() {
    try {
      const response = await apiClientAuth.get('/dashboard/?action=getAvailableWidgets')

      if (response.data.success) {
        availableWidgets.value = response.data.widgets || []
      }
    } catch (error) {
      console.error('Failed to load available widgets:', error)
      throw error
    }
  }

  /**
   * Add widget to dashboard
   */
  function addWidget(widgetKey: string, config?: Partial<WidgetConfig>) {
    const available = availableWidgets.value.find(w => w.key === widgetKey)

    if (!available) {
      console.error('Widget not found:', widgetKey)
      return
    }

    // Find available position
    const maxY = layout.value.length > 0
      ? Math.max(...layout.value.map(item => item.y + item.h))
      : 0

    // Create widget ID (unique)
    const widgetId = `${widgetKey}-${Date.now()}`

    // Add to layout
    layout.value.push({
      i: widgetId,
      x: 0,
      y: maxY,
      w: available.defaultSize.w,
      h: available.defaultSize.h,
      minW: available.minSize.w,
      minH: available.minSize.h
    })

    // Add widget config
    widgets.value[widgetId] = {
      type: widgetKey,
      title: config?.title || available.name,
      ...config
    }
  }

  /**
   * Remove widget from dashboard
   */
  function removeWidget(widgetId: string) {
    layout.value = layout.value.filter(item => item.i !== widgetId)
    delete widgets.value[widgetId]
    delete widgetData.value[widgetId]
  }

  /**
   * Update widget layout (position/size)
   */
  function updateLayout(newLayout: WidgetLayout[]) {
    layout.value = newLayout
  }

  /**
   * Update widget configuration
   */
  function updateWidgetConfig(widgetId: string, config: Partial<WidgetConfig>) {
    if (widgets.value[widgetId]) {
      widgets.value[widgetId] = {
        ...widgets.value[widgetId],
        ...config
      }
    }
  }

  /**
   * Update widget data (runtime data)
   */
  function updateWidgetData(widgetId: string, data: any) {
    widgetData.value[widgetId] = {
      ...widgetData.value[widgetId],
      ...data
    }
  }

  /**
   * Toggle edit mode
   */
  function toggleEditMode() {
    editMode.value = !editMode.value
  }

  /**
   * Enable edit mode
   */
  function enableEditMode() {
    editMode.value = true
  }

  /**
   * Disable edit mode
   */
  function disableEditMode() {
    editMode.value = false
  }

  /**
   * Reset dashboard to default
   */
  async function resetDashboard() {
    try {
      const response = await apiClientAuth.delete('/dashboard/?action=deleteLayout')

      if (response.data.success) {
        // Reload default layout
        await loadLayout()
      }
    } catch (error) {
      console.error('Failed to reset dashboard:', error)
      throw error
    }
  }

  /**
   * Get widget by ID
   */
  function getWidget(widgetId: string): WidgetConfig | undefined {
    return widgets.value[widgetId]
  }

  /**
   * Get widget data by ID
   */
  function getWidgetData(widgetId: string): any {
    return widgetData.value[widgetId]
  }

  /**
   * Check if widget exists
   */
  function hasWidget(widgetId: string): boolean {
    return !!widgets.value[widgetId]
  }

  /**
   * Get widgets by type
   */
  function getWidgetsByType(type: string): string[] {
    return Object.keys(widgets.value).filter(id => widgets.value[id].type === type)
  }

  return {
    // State
    layout,
    widgets,
    widgetData,
    currentTemplate,
    isCustom,
    editMode,
    loading,
    saving,
    availableWidgets,
    availableTemplates,

    // Computed
    hasLayout,
    widgetCount,
    isEditing,

    // Actions
    loadLayout,
    saveLayout,
    exportLayout,
    importLayout,
    loadTemplates,
    applyTemplate,
    loadAvailableWidgets,
    addWidget,
    removeWidget,
    updateLayout,
    updateWidgetConfig,
    updateWidgetData,
    toggleEditMode,
    enableEditMode,
    disableEditMode,
    resetDashboard,
    getWidget,
    getWidgetData,
    hasWidget,
    getWidgetsByType
  }
})
