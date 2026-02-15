// Dokumentenbereich
export interface DocArea {
  id: number;
  key: string;
  name: string;
  description: string | null;
  icon: string;
  is_system: boolean;
  is_active: boolean;
  sort_order: number;
  permissions?: {
    can_read: boolean;
    can_write: boolean;
    can_delete: boolean;
  };
}

// Aktualisierte Category-Definition
export interface Category {
  id: number;
  parent_id: number | null;
  name: string;
  description: string;
  sort_order: number;
  area_id?: number; // Neu: Referenz zum Dokumentenbereich
  subcategories?: Category[];
  documents?: Document[];
}

// Die bestehende Document-Definition ergänzen
export interface Document {
  id: number;
  category_id: number;
  title: string;
  content: string;
  sort_order: number;
  notes?: string;
  creator: number;
  creator_name?: string;
  updated_at: string;
  updated_at_user?: string;
  created_at: string;
  site?: string; // Für Abwärtskompatibilität
  area_id?: number; // Neu: Optional direkter Verweis auf Bereich
}

// Stelle sicher, dass der Role-Typ existiert oder füge ihn hinzu
export interface Role {
  id: number;
  name: string;
  description?: string;
}

// Blackboard Area Types
export interface BlackboardArea {
  id: number;
  authority_id: number;
  key: string;
  name: string;
  description: string | null;
  icon: string;
  is_active: boolean;
  sort_order: number;
  created_at: string;
  updated_at: string;
}

export interface BlackboardAreaPermission {
  role_id: number;
  role_name: string;
  can_read: boolean;
  can_write: boolean;
  can_delete: boolean;
}

// Re-export all types for convenience
export * from './Authority';
export * from './User';
export * from './Application';
export * from './Calendar';
export * from './Company';
export * from './Crew';
export * from './Invoice';
export * from './Map';
export * from './Members';
export * from './Message';
export * from './Person';
export * from './Report';
export * from './Roles';
export * from './Settings';
export * from './Templates';
export * from './Todo';
export * from './Training';
export * from './Vehicle';
export * from './AdminMessage';
export * from './Apartment';
export * from './Document';
export * from './FileManager'; 