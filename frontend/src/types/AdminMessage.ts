export interface User {
  id: number;
  username: string;
}

export interface GroupMember {
  user_id: number;
  username: string;
}

export interface Group {
  id: number;
  name: string;
  created_at: string;
  is_member: boolean;
  sort_order: number;
  members: GroupMember[];
}

export interface EmployeeCompany {
  id: number;
  name: string;
  sort_order: number;
}

export interface EmployeeDepartment {
  id: number;
  name: string;
  sort_order: number;
}