/**
 * Shortcut Types & Validation
 *
 * Version: 2.2 (K-Systems Integration)
 * Date: 2025-01-26
 */

/**
 * Valid shortcut resource types
 * Must match backend validation in shortcuts/index.php
 */
export type ShortcutType =
  | 'document'
  | 'person_file'
  | 'company_file'
  | 'apartment_file'
  | 'document_category'
  | 'document_area'
  | 'report'
  | 'employee'
  | 'vehicle'
  | 'training'
  | 'route'
  | 'blackboard_global'   // NEW: Global blackboard
  | 'blackboard_area';    // NEW: Blackboard area

/**
 * Valid Vuetify colors for shortcuts
 * Used for XSS prevention (whitelist validation)
 */
export type ShortcutColor =
  | 'primary'
  | 'secondary'
  | 'accent'
  | 'error'
  | 'info'
  | 'success'
  | 'warning'
  | 'red'
  | 'pink'
  | 'purple'
  | 'deep-purple'
  | 'indigo'
  | 'blue'
  | 'light-blue'
  | 'cyan'
  | 'teal'
  | 'green'
  | 'light-green'
  | 'lime'
  | 'yellow'
  | 'amber'
  | 'orange'
  | 'deep-orange'
  | 'brown'
  | 'blue-grey'
  | 'grey';

/**
 * Base Shortcut Interface
 * Represents a user's quick access shortcut
 */
export interface Shortcut {
  /** Database ID */
  id: number;

  /** Resource type (document, person_file, etc.) */
  type: ShortcutType;

  /** Resource ID (e.g., document_id) */
  resource_id: number;

  /** Display title */
  title: string;

  /** Optional subtitle/description */
  subtitle?: string;

  /** Material Design Icon name (e.g., mdi-file-document) */
  icon?: string;

  /** Vuetify color name */
  color?: ShortcutColor;

  /** Manual sort order (0 = default) */
  sort_order?: number;

  /** Type-specific metadata (e.g., category info for documents) */
  metadata?: Record<string, any>;

  /** Creation timestamp */
  created_at?: string;

  /** Last update timestamp */
  updated_at?: string;
}

/**
 * Shortcut for creation (without ID)
 */
export interface ShortcutCreate {
  type: ShortcutType;
  resource_id: number;
  title: string;
  subtitle?: string;
  icon?: string;
  color?: ShortcutColor;
  metadata?: Record<string, any>;
}

/**
 * Grouped shortcuts by type
 */
export interface GroupedShortcuts {
  [key: string]: Shortcut[];
}

/**
 * Flattened item for virtual scrolling
 * Can be either a header or a shortcut
 */
export type FlattenedItem =
  | { type: 'header'; group: ShortcutType; label: string; count: number }
  | { type: 'shortcut'; data: Shortcut };

// =============================================================================
// TYPE GUARDS & VALIDATION
// =============================================================================

/**
 * Valid shortcut types array for validation
 */
const VALID_SHORTCUT_TYPES: ShortcutType[] = [
  'document',
  'person_file',
  'company_file',
  'apartment_file',
  'document_category',
  'document_area',
  'report',
  'employee',
  'vehicle',
  'training',
  'route',
  'blackboard_global',   // NEW
  'blackboard_area',     // NEW
];

/**
 * Type guard for ShortcutType
 */
export function isShortcutType(value: unknown): value is ShortcutType {
  return typeof value === 'string' && VALID_SHORTCUT_TYPES.includes(value as ShortcutType);
}

/**
 * Type guard for Shortcut
 * Basic validation - checks required fields exist with correct types
 */
export function isShortcut(value: unknown): value is Shortcut {
  if (!value || typeof value !== 'object') return false;

  const obj = value as any;

  return (
    typeof obj.id === 'number' &&
    isShortcutType(obj.type) &&
    typeof obj.resource_id === 'number' &&
    typeof obj.title === 'string' &&
    obj.title.length > 0
  );
}

/**
 * Validate shortcuts array from API response or localStorage
 */
export function validateShortcutsArray(data: unknown): data is Shortcut[] {
  if (!Array.isArray(data)) return false;
  return data.every(isShortcut);
}
