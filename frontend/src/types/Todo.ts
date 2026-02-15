export interface TodoList {
    id: number;
    name: string;
    parent_list_id?: number; // Fügen Sie diese Zeile hinzu
  }
  
  export interface Todo {
    id: number;
    title: string;
    short_description?: string;
    description?: string;
    importance: string;
    due_date: string;
    list_id: number;
    created_at?: string;
    updated_at?: string;
    completed: boolean;
    completed_checkboxes_count: number;
    all_checkboxes_count: number;
    checkboxes?: Checkbox[]; // Fügen Sie diese Zeile hinzu
    assigned_users?: number[]; // Fügen Sie diese Zeile hinzu
    is_global: number;
  }
  export interface TodoOverview{
    id: number;
    list_id: number;
    due_date: string;
    completed: number;
    TodoPoint: string;
    SubTodo: string;
    importance: string;
    MainTodo: string;
  }
  
  export interface Checkbox {
    id: number;
    todo_id: number;
    title: string;
    completed: boolean;
    created_at?: string;
    updated_at?: string;
  }
  