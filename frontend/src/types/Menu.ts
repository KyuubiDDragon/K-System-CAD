/**
 * Menu Item Type Definition
 * Verwendet für Desktop-Modus UND Sidebar-Modus
 */
export interface MenuItem {
  // Grundlegende Eigenschaften
  id: string;
  title: string;
  icon: string;
  color?: string;

  // Navigation
  route?: string;
  action?: 'addWidget' | 'exitDesktop' | string;

  // Hierarchie
  parent?: string;
  children?: MenuItem[];
  isGroup?: boolean;
  isSectionHeader?: boolean; // For sidebar section dividers

  // Anzeige-Optionen
  hideOnDesktop?: boolean;
  hideOnSidebar?: boolean;

  // Desktop-spezifisch
  isDesktopApp?: boolean;
  widgetType?: 'weather' | 'calendar' | 'notes';
  isPinned?: boolean;

  // Sidebar-spezifisch
  pinnable?: boolean;
  badge?: number;
  badgeColor?: string;

  // Berechtigungen & Features
  permission?: string;
  feature?: string;

  // Sortierung
  order?: number;
}

/**
 * Breadcrumb Item
 */
export interface Breadcrumb {
  title: string;
  route?: string;
  disabled?: boolean;
}

/**
 * Layout Preference
 */
export type LayoutPreference = 'desktop' | 'sidebar' | null;

/**
 * Recently Visited Item
 */
export interface RecentlyVisitedItem {
  route: string;
  title: string;
  timestamp: number;
}

/**
 * Document Area (from backend)
 */
export interface DocArea {
  id: number;
  key: string;
  name: string;
  icon: string;
  is_active: boolean;
  permissions?: {
    can_read: boolean;
    can_write: boolean;
    can_delete: boolean;
  };
}
