// types/Company.ts

export interface Company {
  id: number;
  name: string;
  email: string;
  location: string;
  ceo: string;
  co_ceo: string;
  type_id: number | null;
  type_name?: string; // Added for display purposes in the view
  contact_person: string;
  phonenumber: string;
  last_fire_protection_inspection: string | null; // Date as string or null
  fire_protection_inspection_valid_until: string | null; // Date as string or null
  contract_available: boolean;
  image?: string;
  image_file?: File | null;
  description: string;
  website?: string; // Optional field for company website
  extinguisher_count?: number; // Optional field for extinguisher count
  is_deleted?: boolean; // Optional field for deleted status
}

export interface CompanyType {
  id: number;
  name: string;
  description: string;
}

export interface Extinguisher {
  id: number;
  identifier: string;
  company_id: number;
  registration_date: string; // Assuming date as string
  last_inspection: string; // Assuming date as string
  inspected_by: string;
  location: string;
  is_deleted?: boolean; // Optional field for deleted status
}

export interface CompanyHistory {
  id: number;
  company_id: number;
  name: string;
  ceo: string;
  contact_person: string;
  phonenumber: string;
  change_date: string; // Assuming date as string
}
