export interface Training {
  id: number;
  catId: number;
  name: string;
  cat_name: string;
  cat_short: string;
  color: string;
  sort_order: number;
}

export interface TrainingAssign {
  instructor: string;
  date: string;
  employeename: string;
  employeeid: number;
  servicenumber: number;
  training_id: number;
  training_name: string;
  cat_short: string;
  cat_name: string;
  cat_id: number;
}

export interface Employee {
  id: number;
  name: string;
  servicenumber: number;
  rank_id: number;
  rank_name: string;
  trainings: string[]; // Hinzufügen der trainings-Eigenschaft
}

export interface EmployeeOnly {
  id: number;
  name: string;
  servicenumber: number;
}

export interface Rank {
  id: number;
  name: string;
  sort_order: number;
}


export interface NewTraining {
  trainingId: number[];
  employees: number[];
  date: string;
  notes: string;
}

export interface Category {
  id: number;
  name: string;
  trainings: Training[];
  headers: any[];
  employees: any[];
 }