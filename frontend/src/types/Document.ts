export type ViewType = 'document' | 'spreadsheet' | 'both';
export type DefaultView = 'document' | 'spreadsheet';

export interface Document {
    id: number;
    category_id: number;
    title: string;
    content: string;
    view_type: ViewType;
    spreadsheet_data?: any; // Univer workbook data as JSON
    default_view: DefaultView;
    sort_order: number;
    notes: string;
    updated_at: string;
    updated_at_user?: string;
    created_at: string;
    creator: number;
    creator_name: string;
    site?: string;
  }
  
export interface Category {
    id: number;
    parent_id: number | null;
    name: string;
    description: string;
    sort_order: number;
    subcategories: Category[];
    documents: Document[];
  }
  