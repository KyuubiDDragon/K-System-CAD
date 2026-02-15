export interface Template {
    id: number;
    category_id: number;
    name: string;
    description: string;
    icon: string;
    text: string;
    sort_order: number;
    recipient: string;
    subject: string;
  }
  
  export interface Category {
    id: number;
    name: string;
    icon: string;
    sort_order: number;
    templates: Template[];
  }
  
  export interface TemplateField {
    id: number;
    template_id: number;
    field_name: string;
    field_label: string;
    field_description: string;
    field_type: string;
    is_required: boolean;
    is_multiple: boolean;
    field_value: FieldValue; // Ändern Sie den Typ zu FieldValue
    options?: string[];
  }
  
  export interface Field {
    id: number;
    template_id: number | null;
    field_name: string;
    field_label: string;
    is_multiple: boolean;
    is_required: boolean;
    field_type: string;
    field_description: string;
    field_value: FieldValue; // Ändern Sie den Typ zu FieldValue
  }
  
  export type TemplateFieldValue = {
    field_id: number;
    value: string | string[];
  };
  

  
  export interface ExtractedField {
    field_name: string;
    field_label:string
    is_multiple: boolean;
    is_required: boolean;
    field_type: string;
    field_description: string;
    field_value: FieldValue; // Fügen Sie dieses Feld hinzu
  };

  export type FieldValue = string | string[];