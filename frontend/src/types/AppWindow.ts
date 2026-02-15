/**
 * AppWindow Type Definition
 *
 * Extracted from DesktopView.vue for type-safe Desktop integration
 * Used in provide/inject pattern for shortcut navigation
 *
 * Version: 2.2 (K-Systems Integration)
 * Date: 2025-01-26
 */

/**
 * Desktop Window Interface
 *
 * Represents a window in Desktop Mode
 * Based on DesktopView.vue:321-329
 */
export interface AppWindow {
  /** Unique window ID */
  id: string;

  /** App identifier (optional, for duplicate detection) */
  appId?: string;

  /** Route path for the window content */
  route: string;

  /** Window title */
  title: string;

  /** Optional subtitle */
  subtitle?: string;

  /** Material Design Icon name */
  icon: string;

  /** Vuetify color name */
  color?: string;

  /** Window X position (pixels) */
  x: number;

  /** Window Y position (pixels) */
  y: number;

  /** Window width (pixels) */
  width: number;

  /** Window height (pixels) */
  height: number;

  /** Z-index for stacking order */
  zIndex: number;

  /** Is window minimized? */
  minimized: boolean;

  /** Is window maximized? */
  maximized: boolean;

  /** Is this a desktop-specific app? */
  isDesktopApp?: boolean;
}

/**
 * App definition (for desktop icons)
 *
 * Based on DesktopView.vue App interface
 */
export interface App {
  id: string;
  title: string;
  subtitle?: string;
  icon: string;
  color?: string;
  route?: string;
  isGroup?: boolean;
  items?: App[];
  isDesktopApp?: boolean;
}
