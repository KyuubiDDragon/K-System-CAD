/**
 * Resource Navigation Composable
 *
 * Handles navigation for shortcuts in both Desktop and Sidebar modes
 * v2.2 Feature: Uses provide/inject pattern for Desktop integration
 *
 * Version: 2.2 (K-Systems Integration)
 * Date: 2025-01-26
 */

import { inject } from 'vue';
import { useRouter } from 'vue-router';
import { useUIStore } from '@/stores/ui';
import type { Shortcut, ShortcutType } from '@/types/Shortcut';
import type { DesktopViewContext } from '@/types/Desktop';

/**
 * Use Resource Navigation
 *
 * Provides methods to navigate to shortcuts in Desktop or Sidebar mode
 *
 * Desktop Mode: Opens as window via DesktopView provide/inject
 * Sidebar Mode: Navigates via Vue Router
 */
export function useResourceNavigation() {
  const router = useRouter();
  const uiStore = useUIStore();

  // v2.2: Inject DesktopView context (null if not in Desktop mode)
  const desktopView = inject<DesktopViewContext | null>('desktopView', null);

  /**
   * Get route path for a shortcut
   *
   * Maps shortcut types to routes
   */
  const getRouteForShortcut = (shortcut: Shortcut): string => {
    const routeMap: Record<ShortcutType, (s: Shortcut) => string> = {
      document: (s) => {
        const areaKey = (s.metadata as any)?.areaKey;
        return areaKey
          ? `/documentarea/${areaKey}?id=${s.resource_id}`
          : `/document?id=${s.resource_id}`;
      },
      person_file: (s) => `/person?id=${s.resource_id}`,
      company_file: (s) => `/company?id=${s.resource_id}`,
      apartment_file: (s) => `/apartment?id=${s.resource_id}`,
      document_category: (s) => {
        const areaKey = (s.metadata as any)?.areaKey;
        return areaKey
          ? `/documentarea/${areaKey}?category=${s.resource_id}`
          : `/document?category=${s.resource_id}`;
      },
      document_area: (s) => {
        const areaKey = (s.metadata as any)?.areaKey;
        return areaKey ? `/documentarea/${areaKey}` : `/document`;
      },
      report: (s) => `/report?id=${s.resource_id}`,
      employee: (s) => `/employee?id=${s.resource_id}`,
      vehicle: (s) => `/vehicleFile?id=${s.resource_id}`,
      training: (s) => `/admin/trainings?id=${s.resource_id}`,
      route: (s) => (s.metadata as any)?.route || s.resource_id.toString(),
      // NEW: Blackboard shortcuts
      blackboard_global: () => '/blackboard/global',
      blackboard_area: (s) => {
        const areaKey = (s.metadata as any)?.area_key;
        return `/blackboard/area/${s.resource_id}${areaKey ? `?area_key=${areaKey}` : ''}`;
      },
    };

    return routeMap[shortcut.type]?.(shortcut) || '/';
  };

  /**
   * Get icon for shortcut type
   *
   * Provides fallback icons if shortcut.icon is not set
   */
  const getIconForShortcut = (shortcut: Shortcut): string => {
    const iconMap: Record<ShortcutType, string> = {
      document: 'mdi-file-document',
      person_file: 'mdi-account',
      company_file: 'mdi-domain',
      apartment_file: 'mdi-home',
      document_category: 'mdi-folder',
      document_area: 'mdi-folder-multiple',
      report: 'mdi-file-chart',
      employee: 'mdi-account-tie',
      vehicle: 'mdi-car',
      training: 'mdi-school',
      route: 'mdi-map-marker',
      // NEW: Blackboard icons
      blackboard_global: 'mdi-bulletin-board',
      blackboard_area: 'mdi-bulletin-board',
    };

    return shortcut.icon || iconMap[shortcut.type] || 'mdi-star';
  };

  /**
   * Navigate to a shortcut
   *
   * v2.2: Desktop mode uses provide/inject for direct openWindows access
   *
   * @param shortcut The shortcut to navigate to
   * @returns Promise with success/error
   */
  const navigateToShortcut = async (
    shortcut: Shortcut
  ): Promise<{ success: boolean; error?: any }> => {
    try {
      if (uiStore.isDesktopMode) {
        // =================================================================
        // DESKTOP MODE: v2.2 Integration with DesktopView
        // =================================================================

        // Check if DesktopView context is available
        if (!desktopView) {
          console.error('❌ DesktopView context not available!');
          console.warn('⚠️ Make sure DesktopView.vue provides desktopView context');
          return { success: false, error: 'Desktop context missing' };
        }

        const route = getRouteForShortcut(shortcut);

        // Check if window is already open
        const existingWindow = desktopView.openWindows.value.find(
          (w) => w.route === route
        );

        if (existingWindow) {
          console.log('🔄 Window already exists, focusing:', existingWindow.title);

          // Restore if minimized
          if (existingWindow.minimized) {
            existingWindow.minimized = false;
          }

          // Focus the window
          desktopView.focusWindow(existingWindow.id);

          return { success: true };
        }

        // Create new window (like in DesktopView.vue handleIconClick)
        const newWindow = {
          id: `shortcut-${shortcut.type}-${shortcut.resource_id}-${Date.now()}`,
          appId: `${shortcut.type}-${shortcut.resource_id}`,
          route,
          title: shortcut.title,
          subtitle: shortcut.subtitle || '',
          icon: getIconForShortcut(shortcut),
          color: shortcut.color || 'primary',
          x: 100 + desktopView.openWindows.value.length * 30, // Cascade positioning
          y: 80 + desktopView.openWindows.value.length * 30,
          width: 900,
          height: 600,
          zIndex: desktopView.lastZIndex.value + 1,
          minimized: false,
          maximized: false,
          isDesktopApp: false,
        };

        // Push to openWindows array
        desktopView.openWindows.value.push(newWindow);

        // Increment z-index
        desktopView.lastZIndex.value++;

        // Focus the new window
        desktopView.focusWindow(newWindow.id);

        console.log('✅ Opened shortcut in Desktop mode:', newWindow.title);

        return { success: true };
      } else {
        // =================================================================
        // SIDEBAR MODE: Standard Vue Router navigation
        // =================================================================

        const route = getRouteForShortcut(shortcut);
        await router.push(route);

        console.log('✅ Navigated to shortcut route:', route);

        return { success: true };
      }
    } catch (error) {
      console.error(
        `❌ Navigation failed for ${shortcut.type}:${shortcut.resource_id}`,
        error
      );
      return { success: false, error };
    }
  };

  return {
    navigateToShortcut,
    getRouteForShortcut,
    getIconForShortcut,
  };
}
