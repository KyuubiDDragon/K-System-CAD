/**
 * Desktop View Context Type
 *
 * Type-safe interface for DesktopView provide/inject pattern
 * Enables shortcuts to open windows in Desktop mode
 *
 * Version: 2.2 (K-Systems Integration)
 * Date: 2025-01-26
 */

import type { Ref } from 'vue';
import type { AppWindow } from './AppWindow';

/**
 * Desktop View Context
 *
 * Provided by DesktopView.vue via provide('desktopView', ...)
 * Injected by useResourceNavigation via inject<DesktopViewContext>('desktopView')
 *
 * Usage in DesktopView.vue:
 * ```typescript
 * import { provide } from 'vue';
 * import type { DesktopViewContext } from '@/types/Desktop';
 *
 * provide<DesktopViewContext>('desktopView', {
 *   openWindows,
 *   lastZIndex,
 *   focusWindow,
 * });
 * ```
 *
 * Usage in useResourceNavigation:
 * ```typescript
 * import { inject } from 'vue';
 * import type { DesktopViewContext } from '@/types/Desktop';
 *
 * const desktopView = inject<DesktopViewContext>('desktopView', null);
 *
 * if (desktopView) {
 *   desktopView.openWindows.value.push(newWindow);
 *   desktopView.focusWindow(newWindow.id);
 * }
 * ```
 */
export interface DesktopViewContext {
  /**
   * Array of all opened desktop windows
   * Push new windows here to open them
   */
  openWindows: Ref<AppWindow[]>;

  /**
   * Current highest z-index
   * Increment when creating new windows
   */
  lastZIndex: Ref<number>;

  /**
   * Focus a window and bring it to the foreground
   * @param windowId - The ID of the window to focus
   */
  focusWindow: (windowId: string) => void;
}

/**
 * Type guard to check if DesktopViewContext is available
 */
export function isDesktopViewContext(value: unknown): value is DesktopViewContext {
  return (
    value !== null &&
    typeof value === 'object' &&
    'openWindows' in value &&
    'lastZIndex' in value &&
    'focusWindow' in value
  );
}
