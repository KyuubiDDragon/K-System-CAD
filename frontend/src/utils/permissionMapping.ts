/**
 * Permission Mapping Utilities
 * Maps legacy permission strings to new module/action format
 *
 * This file provides utilities to convert between old string-based permissions
 * (e.g., 'READ_EMPLOYEE') and new module-based permissions (e.g., { module: 'employee', action: 'read' })
 */

export interface ModulePermission {
  module: string;
  action: string;
}

/**
 * Maps legacy permission strings to module/action pairs
 *
 * Format: { 'LEGACY_STRING': { module: 'module_name', action: 'action_type' } }
 */
export const LEGACY_TO_MODULE_MAP: Record<string, ModulePermission> = {
  // Employee
  'READ_EMPLOYEE': { module: 'employee', action: 'read' },
  'WRITE_EMPLOYEE': { module: 'employee', action: 'write' },
  'DELETE_EMPLOYEE': { module: 'employee', action: 'delete' },
  'ADMIN_READ_EMPLOYEE': { module: 'employee', action: 'admin' },

  // Company
  'READ_COMPANY': { module: 'company', action: 'read' },
  'WRITE_COMPANY': { module: 'company', action: 'write' },
  'DELETE_COMPANY': { module: 'company', action: 'delete' },

  // Company Type
  'READ_COMPANYTYPE': { module: 'company.type', action: 'read' },
  'WRITE_COMPANYTYPE': { module: 'company.type', action: 'write' },
  'DELETE_COMPANYTYPE': { module: 'company.type', action: 'delete' },

  // Company Websites
  'READ_COMPANY_WEBSITES': { module: 'company.websites', action: 'read' },
  'WRITE_COMPANY_WEBSITES': { module: 'company.websites', action: 'write' },

  // Calendar
  'READ_CALENDAR': { module: 'calendar', action: 'read' },
  'WRITE_CALENDAR': { module: 'calendar', action: 'write' },
  'DELETE_CALENDAR': { module: 'calendar', action: 'delete' },

  // Document
  'READ_DOCUMENT': { module: 'document', action: 'read' },
  'WRITE_DOCUMENT': { module: 'document', action: 'write' },
  'DELETE_DOCUMENT': { module: 'document', action: 'delete' },
  'READ_DOCUMENT_DOCUMENT': { module: 'document.document', action: 'read' },
  'READ_DOCUMENT_GLOBAL': { module: 'document.global', action: 'read' },
  'READ_DOCUMENT_TRAINING': { module: 'document.training', action: 'read' },
  'READ_DOCUMENT_ADMINISTRATION': { module: 'document.administration', action: 'read' },
  'READ_DOCUMENT_DEPARTMENT': { module: 'document.department', action: 'read' },
  'ADMIN_DOCUMENT_AREAS': { module: 'document.areas', action: 'admin' },

  // Report
  'READ_REPORT': { module: 'report', action: 'read' },
  'WRITE_REPORT': { module: 'report', action: 'write' },
  'DELETE_REPORT': { module: 'report', action: 'delete' },
  'READ_REPORTDEPARTMENT': { module: 'report.department', action: 'read' },
  'READ_REPORTTEMPLATE': { module: 'report.template', action: 'read' },
  'READ_REPORT_STATUS': { module: 'report.status', action: 'read' },
  'READ_REPORTCODE': { module: 'report.code', action: 'read' },
  'READ_REPORT_ADDITIONALS': { module: 'report.additionals', action: 'read' },
  'ADMIN_REPORTS': { module: 'report', action: 'admin' },

  // Blackboard
  'READ_BLACKBOARD': { module: 'blackboard', action: 'read' },
  'WRITE_BLACKBOARD': { module: 'blackboard', action: 'write' },
  'DELETE_BLACKBOARD': { module: 'blackboard', action: 'delete' },
  'READ_BLACKBOARD_GLOBAL': { module: 'blackboard.global', action: 'read' },
  'READ_BLACKBOARD_AREA': { module: 'blackboard.area', action: 'read' },
  'ADMIN_BLACKBOARD_AREAS': { module: 'blackboard.areas', action: 'admin' },

  // Whiteboard
  'READ_WHITEBOARD': { module: 'whiteboard', action: 'read' },
  'WRITE_WHITEBOARD': { module: 'whiteboard', action: 'write' },

  // Dispatch
  'READ_DISPATCH': { module: 'dispatch', action: 'read' },
  'WRITE_DISPATCH': { module: 'dispatch', action: 'write' },
  'DELETE_DISPATCH': { module: 'dispatch', action: 'delete' },

  // Vehicle
  'READ_VEHICLE': { module: 'dispatch.vehicle', action: 'read' },
  'WRITE_VEHICLE': { module: 'dispatch.vehicle', action: 'write' },
  'DELETE_VEHICLE': { module: 'dispatch.vehicle', action: 'delete' },

  // Crew
  'READ_CREW': { module: 'dispatch.crew', action: 'read' },
  'WRITE_CREW': { module: 'dispatch.crew', action: 'write' },
  'DELETE_CREW': { module: 'dispatch.crew', action: 'delete' },

  // Application
  'READ_APPLICATION': { module: 'application', action: 'read' },
  'WRITE_APPLICATION': { module: 'application', action: 'write' },
  'ADMIN_READ_APPLICATION': { module: 'application', action: 'admin' },

  // Account
  'READ_ACCOUNT': { module: 'account', action: 'read' },
  'WRITE_ACCOUNT': { module: 'account', action: 'write' },

  // Invoice
  'READ_INVOICE': { module: 'invoice', action: 'read' },
  'WRITE_INVOICE': { module: 'invoice', action: 'write' },
  'DELETE_INVOICE': { module: 'invoice', action: 'delete' },
  'READ_INVOICEITEMS': { module: 'invoice.items', action: 'read' },
  'WRITE_INVOICEITEMS': { module: 'invoice.items', action: 'write' },

  // File Manager
  'READ_FILEMANAGER': { module: 'filemanager', action: 'read' },
  'WRITE_FILEMANAGER': { module: 'filemanager', action: 'write' },

  // Training
  'READ_TRAINING': { module: 'training', action: 'read' },
  'WRITE_TRAINING': { module: 'training', action: 'write' },
  'DELETE_TRAINING': { module: 'training', action: 'delete' },
  'READ_TEST': { module: 'training.test', action: 'read' },
  'ADMIN_READ_TRAINING': { module: 'training', action: 'admin' },

  // Map
  'READ_MAP': { module: 'map', action: 'read' },
  'WRITE_MAP': { module: 'map', action: 'write' },
  'READ_MAP_GLOBAL': { module: 'map.global', action: 'read' },
  'ADMIN_READ_MAP': { module: 'map', action: 'admin' },

  // Template
  'READ_TEMPLATE': { module: 'template', action: 'read' },
  'WRITE_TEMPLATE': { module: 'template', action: 'write' },

  // Todo
  'READ_TODO': { module: 'todo', action: 'read' },
  'WRITE_TODO': { module: 'todo', action: 'write' },
  'DELETE_TODO': { module: 'todo', action: 'delete' },

  // Mail
  'READ_MAIL': { module: 'mail', action: 'read' },
  'WRITE_MAIL': { module: 'mail', action: 'write' },
  'ADMIN_MAIL': { module: 'mail', action: 'admin' },

  // Person File
  'READ_PERSON_FILE': { module: 'person.file', action: 'read' },
  'WRITE_PERSON_FILE': { module: 'person.file', action: 'write' },

  // Vehicle File
  'READ_VEHICLE_FILE': { module: 'vehicle.file', action: 'read' },
  'WRITE_VEHICLE_FILE': { module: 'vehicle.file', action: 'write' },

  // Apartment File
  'READ_APARTMENT_FILE': { module: 'apartment.file', action: 'read' },
  'WRITE_APARTMENT_FILE': { module: 'apartment.file', action: 'write' },

  // Fireprotection
  'READ_FIREPROTECTION': { module: 'fireprotection', action: 'read' },
  'WRITE_FIREPROTECTION': { module: 'fireprotection', action: 'write' },

  // Cheatsheet
  'READ_CHEATSHEET': { module: 'cheatsheet', action: 'read' },
  'ADMIN_CHEATSHEET': { module: 'cheatsheet', action: 'admin' },

  // Messages
  'READ_MESSAGES': { module: 'messaging', action: 'read' },
  'WRITE_MESSAGES': { module: 'messaging', action: 'write' },
  'DELETE_MESSAGES': { module: 'messaging', action: 'delete' },

  // Report Sharing (mapped to write for now - may need admin)
  'SHARE_REPORT': { module: 'report', action: 'write' },

  // Admin Permissions
  'ADMIN_READ_USERS': { module: 'admin.users', action: 'read' },
  'ADMIN_WRITE_USERS': { module: 'admin.users', action: 'write' },
  'ADMIN_READ_ROLES': { module: 'admin.roles', action: 'read' },
  'ADMIN_READ_SETTINGS': { module: 'admin.settings', action: 'read' },
  'ADMIN_AUTHORITY_SETTINGS': { module: 'admin.authority', action: 'admin' },
  'ADMIN_READ_WEATHER': { module: 'admin.weather', action: 'read' },
  'ADMIN_AUTHORITY_FIELDS': { module: 'admin.authority.fields', action: 'admin' },
  'SYSTEM_ADMIN': { module: 'system', action: 'admin' },
};

/**
 * Converts a legacy permission string to module/action format
 *
 * @param legacyPermission - The legacy permission string (e.g., 'READ_EMPLOYEE')
 * @returns ModulePermission object or null if not found
 *
 * @example
 * convertLegacyToModule('READ_EMPLOYEE') → { module: 'employee', action: 'read' }
 */
export function convertLegacyToModule(legacyPermission: string): ModulePermission | null {
  return LEGACY_TO_MODULE_MAP[legacyPermission] || null;
}

/**
 * Extracts module and action from a legacy permission string
 * Falls back to parsing if not in mapping table
 *
 * @param legacyPermission - The legacy permission string
 * @returns ModulePermission object or null
 *
 * @example
 * parseLegacyPermission('READ_EMPLOYEE') → { module: 'employee', action: 'read' }
 * parseLegacyPermission('WRITE_NEW_MODULE') → { module: 'new_module', action: 'write' }
 */
export function parseLegacyPermission(legacyPermission: string): ModulePermission | null {
  // First try the mapping table
  const mapped = convertLegacyToModule(legacyPermission);
  if (mapped) {
    return mapped;
  }

  // Fallback: Parse the string
  const match = legacyPermission.match(/^(READ|WRITE|DELETE|ADMIN|VIEW|CREATE)_(.+)$/);
  if (!match) {
    return null;
  }

  const [, action, module] = match;
  return {
    module: module.toLowerCase().replace(/_/g, '.'),
    action: action.toLowerCase(),
  };
}
