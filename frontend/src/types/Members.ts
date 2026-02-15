export interface Employee {
    id: number;
    name: string;
    phonenumber: string;
    birthdate: string;
    servicenumber: string;
    entrydate: string;
    leavedate: string;
    bankaccount: string;
    mail: string;
    sidejob: string;
    rankId: number;
    rank: string;
    last_promotion: string;
    personalid: string;
    companies: Company[];
    departments: Department[];
    licenses: License[];
    vacations: Vacation[];
    promotions: Promotion[];
    is_terminated: boolean;
    jobrole_id: number;
    jobrole_name: string;
    notes: string;
  }

  export interface JobTypes{
    id: number;
    name: string;
  }
  
  export interface License {
    id: string;
    type: string;
    license: string;
  }
  
  export interface Promotion {
    id: string;
    reason: string;
    start: string;
    end: string;
    employee: number;
    reported: boolean;
    other:string;
  }

  export interface Vacation {
    id: number;
    reason: string;
    start: string;
    end: string;
    reported: boolean;
    other: string;
    employee_id: number; // Assuming vacation has an employee_id to reference the employee
}
  
  export interface Rank {
    id: number;
    name: string;
    description: string;
    rankImage: string;
    sort_order: number;
    department: string;
    jobrole_id: number;
    employees: Employee[];
  }
  
  export interface ApiResponse {
    ranks: Rank[];
  }
  
  export interface Company {
    id: string;
    name: string;
  }
  
  export interface Department {
    id: string;
    name: string;
  }
  
  export interface EmployeeFormProps {
    editMode: boolean;
    hideEditButton: boolean;
    initialEmployee: Record<string, any>;
    companies: Company[];
    departments: Department[];
    ranks: Rank[];
    licenses: License[];
  }

  export interface EmployeeCompany{
    id: number;
    name: string;
    sort_order: number;
  }

  export interface EmployeeDepartment{
    id: number;
    name: string;
    sort_order: number;
  }

  export interface EmployeeTraining{
    id: number;
    catId: number;
    name: string;
    sort_order: number;
    is_deleted: boolean;
    cat_name: string;
    cat_short: string;
  }

  export interface EmployeeTrainingCat{
    id: number;
    name: string;
    short: string;
    sort_order: number;
    is_deleted: boolean;
  }